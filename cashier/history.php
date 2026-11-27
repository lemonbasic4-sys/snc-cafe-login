<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cashier-view.php';
snc_require_auth(['cashier']);

function snc_history_date(string $value): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$search = is_string($_GET['search'] ?? null) ? trim(substr($_GET['search'], 0, 80)) : '';
$from = is_string($_GET['from'] ?? null) ? $_GET['from'] : '';
$to = is_string($_GET['to'] ?? null) ? $_GET['to'] : '';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$orders = [];
$total = 0;
$pages = 1;
$error = '';
if (($from !== '' && !snc_history_date($from)) || ($to !== '' && !snc_history_date($to)) || ($from !== '' && $to !== '' && $from > $to)) {
    http_response_code(400);
    $error = 'Choose a valid date range with the start date on or before the end date.';
} else {
    try {
        $pdo = snc_get_db();
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(LOCATE(:order_search, o.order_number) > 0 OR LOCATE(:customer_search, o.table_or_note) > 0)';
            $params[':order_search'] = $search;
            $params[':customer_search'] = $search;
        }
        if ($from !== '') {
            $conditions[] = 'o.created_at >= :from_date';
            $params[':from_date'] = $from;
        }
        if ($to !== '') {
            $conditions[] = 'o.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)';
            $params[':to_date'] = $to;
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders o' . $where);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = min($page, $pages);
        $offset = ($page - 1) * 20;
        $stmt = $pdo->prepare(
            "SELECT o.id, o.order_number, o.order_type, o.table_or_note, o.total_amount,
                    o.payment_status, o.kitchen_status, o.created_at,
                    (SELECT p.method FROM payments p WHERE p.order_id = o.id ORDER BY p.id ASC LIMIT 1) AS payment_method
             FROM orders o{$where} ORDER BY o.id DESC LIMIT 20 OFFSET {$offset}"
        );
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } catch (Throwable $e) {
        http_response_code(503);
        $error = 'Order history is unavailable. Please try again.';
    }
}
$filters = ['search' => $search, 'from' => $from, 'to' => $to];
snc_cashier_page_start('Order History');
?>
  <section class="snc-pos-card">
    <div class="snc-pos-head"><h1>Order History</h1><span class="snc-record-muted">Search all recorded orders</span></div>
    <form class="snc-history-filters" method="get" action="history.php">
      <div class="snc-field"><label for="snc-history-search">Order / Table / Customer</label><input class="snc-input" id="snc-history-search" name="search" maxlength="80" value="<?= snc_cashier_escape($search) ?>" placeholder="Order number or customer"></div>
      <div class="snc-field"><label for="snc-history-from">From</label><input class="snc-input" type="date" id="snc-history-from" name="from" value="<?= snc_cashier_escape($from) ?>"></div>
      <div class="snc-field"><label for="snc-history-to">To</label><input class="snc-input" type="date" id="snc-history-to" name="to" value="<?= snc_cashier_escape($to) ?>"></div>
      <button class="snc-cat-btn" type="submit">Search</button><a class="snc-cat-btn snc-receipt-link snc-details-link" href="history.php">Reset</a>
    </form>
    <?php if ($error): ?>
    <p role="alert"><?= snc_cashier_escape($error) ?></p>
    <?php else: ?>
    <p class="snc-record-muted"><?= $total ?> order<?= $total === 1 ? '' : 's' ?> found · Page <?= $page ?> of <?= $pages ?></p>
    <div class="snc-orders-scroll" role="region" aria-label="Order history table" tabindex="0">
      <table class="snc-orders-table">
        <thead><tr><th>Order / Time</th><th>Type / Customer</th><th>Total</th><th>Payment</th><th>Kitchen</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><a class="snc-order-link" data-order-details="<?= (int) $order['id'] ?>" href="order.php?order_id=<?= (int) $order['id'] ?>"><?= snc_cashier_escape($order['order_number']) ?></a><span class="snc-order-secondary"><?= snc_cashier_escape($order['created_at']) ?></span></td>
            <td><?= $order['order_type'] === 'takeout' ? 'Takeout' : 'Dine-In' ?><span class="snc-order-secondary snc-order-customer"><?= snc_cashier_escape(trim($order['table_or_note']) ?: '—') ?></span></td>
            <td><?= snc_cashier_money($order['total_amount']) ?></td>
            <td><?= snc_cashier_escape(strtoupper($order['payment_method'] ?? '—')) ?><span class="snc-order-secondary"><?= snc_cashier_escape(ucfirst($order['payment_status'])) ?></span></td>
            <td><span class="snc-status-pill" data-status="<?= snc_cashier_escape($order['payment_status']==='voided'?'voided':$order['kitchen_status']) ?>"><?= snc_cashier_escape($order['payment_status']==='voided'?'Voided':ucfirst($order['kitchen_status'])) ?></span></td>
            <td><div class="snc-order-actions"><a class="snc-cat-btn snc-receipt-link snc-details-link" data-order-details="<?= (int) $order['id'] ?>" href="order.php?order_id=<?= (int) $order['id'] ?>" aria-label="View details for <?= snc_cashier_escape($order['order_number']) ?>">View Details</a><?php if ($order['payment_status'] === 'paid'): ?><a class="snc-cat-btn snc-receipt-link" href="receipt.php?order_id=<?= (int) $order['id'] ?>" target="_blank" rel="noopener">Print Receipt</a><?php else: ?>Unavailable<?php endif; ?></div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="6">No orders match your search.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <nav class="snc-history-pagination" aria-label="Order history pages">
      <?php if ($page > 1): ?><a class="snc-cat-btn snc-receipt-link" href="?<?= snc_cashier_escape(http_build_query($filters + ['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
      <?php if ($page < $pages): ?><a class="snc-cat-btn snc-receipt-link" href="?<?= snc_cashier_escape(http_build_query($filters + ['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </nav>
    <?php endif; ?>
  </section>
<?php require __DIR__ . '/../includes/cashier-order-dialog.php'; snc_cashier_page_end(); ?>
