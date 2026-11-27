<?php
declare(strict_types=1);
$id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$order = null;
$items = [];
$payment = null;
$void = null;
$voidRequest = null;
require_once __DIR__.'/void-requests.php';
$error = '';
if (!$id) {
    http_response_code(400);
    $error = 'Choose a valid order from Recent Orders or Order History.';
} else {
    try {
        $pdo = snc_get_db();
        $stmt = $pdo->prepare('SELECT o.*, u.full_name AS cashier_name FROM orders o LEFT JOIN users u ON u.id = o.cashier_id WHERE o.id = :id');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch() ?: null;
        if (!$order) {
            http_response_code(404);
            $error = 'This order could not be found.';
        } else {
            $stmt = $pdo->prepare('SELECT item_name, quantity, unit_price, line_total FROM order_items WHERE order_id = :id ORDER BY id ASC');
            $stmt->execute([':id' => $id]);
            $items = $stmt->fetchAll();
            $stmt = $pdo->prepare('SELECT method, amount_tendered, change_amount, paid_at FROM payments WHERE order_id = :id ORDER BY id ASC LIMIT 1');
            $stmt->execute([':id' => $id]);
            $payment = $stmt->fetch() ?: null;
            $void=snc_order_void_record($pdo,(int)$id);
            $voidRequest=snc_void_request_latest($pdo,(int)$id);
        }
    } catch (Throwable $e) {
        http_response_code(503);
        $error = 'Order details are unavailable. Please try again.';
    }
}
