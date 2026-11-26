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
            "SELECT id, order_number, order_type, table_or_note, kitchen_status, created_at
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

    $itemsInput = $payload['items'] ?? [];
    if (!is_array($itemsInput) || $itemsInput === []) {
        snc_orders_json(422, ['ok' => false, 'message' => 'Add at least one menu item to the order.']);
    }

    $orderType = (string) ($payload['order_type'] ?? 'dine_in');
    if (!in_array($orderType, ['dine_in', 'takeout'], true)) {
        snc_orders_json(422, ['ok' => false, 'message' => 'Invalid order type.']);
    }

    $tableOrNote = trim((string) ($payload['table_or_note'] ?? ''));
    $tableOrNote = substr($tableOrNote, 0, 80);

    $paymentMethod = (string) ($payload['payment_method'] ?? 'cash');
    if (!in_array($paymentMethod, ['cash', 'gcash', 'card'], true)) {
        snc_orders_json(422, ['ok' => false, 'message' => 'Invalid payment method.']);
    }

    $requestedQty = [];
    foreach ($itemsInput as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $menuId = (int) ($entry['menu_item_id'] ?? 0);
        $qty = (int) ($entry['quantity'] ?? 0);
        if ($menuId > 0 && $qty > 0 && $qty <= 99) {
            $requestedQty[$menuId] = min(99, ($requestedQty[$menuId] ?? 0) + $qty);
        }
    }

    if ($requestedQty === []) {
        snc_orders_json(422, ['ok' => false, 'message' => 'No valid order items provided.']);
    }

    $menuIds = array_keys($requestedQty);
    $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
    $menuStmt = $pdo->prepare(
        "SELECT id, name, price
         FROM menu_items
         WHERE id IN ($placeholders) AND is_available = 1"
    );
    $menuStmt->execute($menuIds);
    $menuRows = $menuStmt->fetchAll();

    if (count($menuRows) !== count($menuIds)) {
        snc_orders_json(422, ['ok' => false, 'message' => 'One or more selected items are unavailable.']);
    }

    $subtotalCents = 0;
    $lineItems = [];
    foreach ($menuRows as $row) {
        $id = (int) $row['id'];
        $qty = $requestedQty[$id];
        $unitCents = (int) round(((float) $row['price']) * 100);
        $lineCents = $unitCents * $qty;
        $subtotalCents += $lineCents;
        $lineItems[] = [
            'menu_item_id' => $id,
            'item_name' => (string) $row['name'],
            'unit_price' => number_format($unitCents / 100, 2, '.', ''),
            'quantity' => $qty,
            'line_total' => number_format($lineCents / 100, 2, '.', ''),
        ];
    }

    $totalCents = $subtotalCents;
    $tenderedCents = $paymentMethod === 'cash'
        ? (int) round(((float) ($payload['amount_tendered'] ?? 0)) * 100)
        : $totalCents;

    if ($tenderedCents < $totalCents) {
        snc_orders_json(422, ['ok' => false, 'message' => 'Amount tendered is less than the order total.']);
    }

    $changeCents = $tenderedCents - $totalCents;
    $subtotalStr = number_format($subtotalCents / 100, 2, '.', '');
    $totalStr = number_format($totalCents / 100, 2, '.', '');
    $tenderedStr = number_format($tenderedCents / 100, 2, '.', '');
    $changeStr = number_format($changeCents / 100, 2, '.', '');

    $pdo->beginTransaction();
    try {
        $tempOrderNumber = 'TMP-' . bin2hex(random_bytes(6));
        $orderStmt = $pdo->prepare(
            "INSERT INTO orders
               (order_number, cashier_id, order_type, table_or_note, subtotal, discount_amount, total_amount, payment_status, kitchen_status)
             VALUES
               (:order_number, :cashier_id, :order_type, :table_or_note, :subtotal, 0.00, :total_amount, 'paid', 'pending')"
        );
        $orderStmt->execute([
            ':order_number' => $tempOrderNumber,
            ':cashier_id' => (int) $user['id'],
            ':order_type' => $orderType,
            ':table_or_note' => $tableOrNote,
            ':subtotal' => $subtotalStr,
            ':total_amount' => $totalStr,
        ]);

        $orderId = (int) $pdo->lastInsertId();
        $orderNumber = sprintf('SNC-%s-%04d', date('Ymd'), $orderId);

        $numStmt = $pdo->prepare('UPDATE orders SET order_number = :order_number WHERE id = :id');
        $numStmt->execute([
            ':order_number' => $orderNumber,
            ':id' => $orderId,
        ]);

        $itemInsert = $pdo->prepare(
            'INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity, line_total)
             VALUES (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :line_total)'
        );
        foreach ($lineItems as $item) {
            $itemInsert->execute([
                ':order_id' => $orderId,
                ':menu_item_id' => $item['menu_item_id'],
                ':item_name' => $item['item_name'],
                ':unit_price' => $item['unit_price'],
                ':quantity' => $item['quantity'],
                ':line_total' => $item['line_total'],
            ]);
        }

        $payStmt = $pdo->prepare(
            'INSERT INTO payments (order_id, method, amount_tendered, change_amount, cashier_id)
             VALUES (:order_id, :method, :amount_tendered, :change_amount, :cashier_id)'
        );
        $payStmt->execute([
            ':order_id' => $orderId,
            ':method' => $paymentMethod,
            ':amount_tendered' => $tenderedStr,
            ':change_amount' => $changeStr,
            ':cashier_id' => (int) $user['id'],
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        snc_orders_json(500, ['ok' => false, 'message' => 'Failed to save order.']);
    }

    snc_orders_json(201, [
        'ok' => true,
        'message' => 'Order sent to kitchen.',
        'order' => [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalStr,
            'change_amount' => $changeStr,
            'payment_method' => $paymentMethod,
        ],
    ]);
}

snc_orders_json(405, ['ok' => false, 'message' => 'Method not allowed.']);
