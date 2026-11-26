<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');

function snc_kitchen_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    snc_kitchen_json(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

$user = snc_current_user();
if ($user === null) {
    snc_kitchen_json(401, ['ok' => false, 'message' => 'Authentication required.']);
}

if (($user['role'] ?? '') !== 'kitchen') {
    snc_kitchen_json(403, ['ok' => false, 'message' => 'Only kitchen staff can update preparation status.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!snc_verify_csrf(is_string($csrfToken) ? $csrfToken : null)) {
    snc_kitchen_json(403, ['ok' => false, 'message' => 'Session expired. Refresh and try again.']);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    snc_kitchen_json(400, ['ok' => false, 'message' => 'Invalid request payload.']);
}

$orderId = (int) ($payload['order_id'] ?? 0);
$nextStatus = (string) ($payload['next_status'] ?? '');

$allowedTransitions = [
    'preparing' => 'pending',
    'ready' => 'preparing',
    'completed' => 'ready',
];

if ($orderId <= 0 || !isset($allowedTransitions[$nextStatus])) {
    snc_kitchen_json(422, ['ok' => false, 'message' => 'Invalid order or status transition.']);
}

$expectedCurrent = $allowedTransitions[$nextStatus];

try {
    $pdo = snc_get_db();
    $completedExpr = $nextStatus === 'completed' ? ', completed_at = NOW()' : '';
    $stmt = $pdo->prepare(
        "UPDATE orders
         SET kitchen_status = :next_status{$completedExpr}
         WHERE id = :id AND payment_status = 'paid' AND kitchen_status = :expected_current"
    );
    $stmt->execute([
        ':next_status' => $nextStatus,
        ':id' => $orderId,
        ':expected_current' => $expectedCurrent,
    ]);

    if ($stmt->rowCount() === 0) {
        snc_kitchen_json(409, ['ok' => false, 'message' => 'Order status already changed or order not found.']);
    }
} catch (Throwable $e) {
    snc_kitchen_json(500, ['ok' => false, 'message' => 'Unable to update order status.']);
}

snc_kitchen_json(200, [
    'ok' => true,
    'message' => 'Order marked as ' . $nextStatus . '.',
    'order_id' => $orderId,
    'kitchen_status' => $nextStatus,
]);
