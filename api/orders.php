<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');

function snc_orders_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$user = snc_current_user();
if ($user === null) {
    snc_orders_json(401, ['ok' => false, 'message' => 'Authentication required.']);
}

$role = (string) ($user['role'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo = snc_get_db();
} catch (Throwable $e) {
    snc_orders_json(503, ['ok' => false, 'message' => 'Database unavailable.']);
}

if ($method === 'GET') {
    if (!in_array($role, ['admin', 'cashier', 'kitchen'], true)) {
        snc_orders_json(403, ['ok' => false, 'message' => 'Access denied.']);
    }

    if ($role === 'kitchen') {
        $stmt = $pdo->query(
            "SELECT id, order_number, order_type, table_or_note, kitchen_status, created_at,
                    TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
             FROM orders
             WHERE payment_status = 'paid'
               AND kitchen_status IN ('pending', 'preparing', 'ready')
             ORDER BY FIELD(kitchen_status, 'pending', 'preparing', 'ready'), created_at ASC
             LIMIT 60"
        );
        $orders = $stmt->fetchAll();

        if ($orders !== []) {
            $ids = array_map(static fn (array $o): int => (int) $o['id'], $orders);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $itemStmt = $pdo->prepare(
                "SELECT order_id, item_name, quantity
                 FROM order_items
                 WHERE order_id IN ($placeholders)
                 ORDER BY id ASC"
            );
            $itemStmt->execute($ids);
            $byOrder = [];
            foreach ($itemStmt->fetchAll() as $row) {
                $byOrder[(int) $row['order_id']][] = [
                    'name' => (string) $row['item_name'],
                    'quantity' => (int) $row['quantity'],
                ];
            }
            foreach ($orders as &$order) {
                $order['id'] = (int) $order['id'];
                $order['items'] = $byOrder[$order['id']] ?? [];
            }
            unset($order);
        }

        snc_orders_json(200, ['ok' => true, 'orders' => $orders]);
    }

    // Cashier or Admin summary + recent orders
    $recentStmt = $pdo->query(
        "SELECT o.id, o.order_number, o.order_type, o.table_or_note, o.total_amount,
                o.payment_status, o.kitchen_status, o.created_at, p.method AS payment_method
         FROM orders o
         LEFT JOIN payments p ON p.order_id = o.id
         ORDER BY o.id DESC
         LIMIT 15"
    );
    $recent = $recentStmt->fetchAll();
    require_once __DIR__.'/../includes/system-settings.php';
    try{$enabledMethods=snc_settings_methods(snc_settings_get($pdo));}catch(Throwable $e){snc_orders_json(503,['ok'=>false,'message'=>'Payment settings are unavailable.']);}

    $countsStmt = $pdo->query(
        "SELECT
           SUM(CASE WHEN kitchen_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN kitchen_status = 'preparing' THEN 1 ELSE 0 END) AS preparing_count,
           SUM(CASE WHEN kitchen_status = 'ready' THEN 1 ELSE 0 END) AS ready_count
         FROM orders
         WHERE payment_status = 'paid'"
    );
    $counts = $countsStmt->fetch() ?: [];

    snc_orders_json(200, [
        'ok' => true,
        'counts' => [
            'pending' => (int) ($counts['pending_count'] ?? 0),
            'preparing' => (int) ($counts['preparing_count'] ?? 0),
            'ready' => (int) ($counts['ready_count'] ?? 0),
        ],
        'orders' => $recent,
        'payment_methods' => $enabledMethods,
    ]);
}

if ($method === 'POST') {
    if ($role !== 'cashier') {
        snc_orders_json(403, ['ok' => false, 'message' => 'Only cashier accounts can create orders.']);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!snc_verify_csrf(is_string($csrfToken) ? $csrfToken : null)) {
        snc_orders_json(403, ['ok' => false, 'message' => 'Session expired. Refresh and try again.']);
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?: '', true);
    if (!is_array($payload)) {
        snc_orders_json(400, ['ok' => false, 'message' => 'Invalid request payload.']);
    }

    require_once __DIR__ . '/../includes/checkout.php';
    try {
        $order = snc_checkout_create($pdo, $user, $payload);
        snc_orders_json(201, ['ok' => true, 'message' => 'Order sent to kitchen.', 'order' => $order]);
    } catch (InvalidArgumentException $e) {
        snc_orders_json(422, ['ok' => false, 'message' => $e->getMessage()]);
    } catch (RuntimeException $e) {
        if (!($e instanceof PDOException) && in_array($e->getCode(), [403, 409], true)) {
            snc_orders_json($e->getCode(), ['ok' => false, 'message' => $e->getMessage()]);
        }
        snc_orders_json(500, ['ok' => false, 'message' => 'Unable to confirm this order. Retry the same checkout to check its result.']);
    } catch (Throwable $e) {
        snc_orders_json(500, ['ok' => false, 'message' => 'Unable to confirm this order. Retry the same checkout to check its result.']);
    }
}

snc_orders_json(405, ['ok' => false, 'message' => 'Method not allowed.']);
