<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/void-requests.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function snc_detail_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = snc_current_user();
if ($user === null) snc_detail_json(401, ['ok' => false, 'message' => 'Your session has expired. Sign in again to view order details.']);
if (!in_array($user['role'] ?? '', ['admin', 'cashier'], true)) snc_detail_json(403, ['ok' => false, 'message' => 'Your role cannot view transaction details.']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    snc_detail_json(405, ['ok' => false, 'message' => 'Method not allowed.']);
}
$id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) snc_detail_json(400, ['ok' => false, 'message' => 'Choose a valid order.']);
try {
    $pdo = snc_get_db();
    $stmt = $pdo->prepare(
        'SELECT o.id, o.order_number, o.created_at, o.completed_at, o.order_type, o.table_or_note,
                o.subtotal, o.discount_amount, o.total_amount, o.payment_status, o.kitchen_status,
                u.full_name AS cashier_name
         FROM orders o LEFT JOIN users u ON u.id = o.cashier_id WHERE o.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $order = $stmt->fetch();
    if (!$order) snc_detail_json(404, ['ok' => false, 'message' => 'This order could not be found.']);
    $stmt = $pdo->prepare('SELECT item_name, quantity, unit_price, line_total FROM order_items WHERE order_id = :id ORDER BY id ASC');
    $stmt->execute([':id' => $id]);
    $items = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT method, amount_tendered, change_amount, paid_at FROM payments WHERE order_id = :id ORDER BY id ASC LIMIT 1');
    $stmt->execute([':id' => $id]);
    $payment = $stmt->fetch() ?: null;
    $void=snc_order_void_record($pdo,(int)$id);
    $request=snc_void_request_latest($pdo,(int)$id);
    snc_detail_json(200, ['ok' => true, 'order' => $order, 'items' => $items, 'payment' => $payment, 'void' => $void, 'void_request' => $request, 'can_request_void' => $user['role']==='cashier'&&$order['payment_status']==='paid'&&(!$request||$request['status']!=='pending'), 'request_csrf' => $user['role']==='cashier'?snc_csrf_token():null, 'can_void' => $user['role']==='admin'&&$order['payment_status']==='paid', 'void_csrf' => $user['role']==='admin'?snc_csrf_token():null]);
} catch (Throwable $e) {
    snc_detail_json(503, ['ok' => false, 'message' => 'Order details are unavailable. Please try again.']);
}
