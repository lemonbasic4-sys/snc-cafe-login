<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sales-report.php';
$user = snc_require_auth(['admin']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();
header('Cache-Control: no-store');
function snc_sales_escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function snc_sales_money(mixed $value): string { return '₱' . number_format((float) $value, 2); }
function snc_sales_date(string $value): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}
$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Singapore'));
$defaults = ['from' => $today->format('Y-m-d'), 'to' => $today->format('Y-m-d'), 'method' => '', 'status' => '', 'search' => ''];
$filters = [];
$error = '';
foreach ($defaults as $key => $default) {
    if (isset($_GET[$key]) && !is_string($_GET[$key])) $error = 'Choose valid filters and try again.';
    $filters[$key] = is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : $default;
}
$filters['search'] = mb_substr($filters['search'], 0, 120);
if (($filters['from'] !== '' && !snc_sales_date($filters['from'])) || ($filters['to'] !== '' && !snc_sales_date($filters['to'])) || ($filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to'])) $error = 'Choose a valid date range with the start date on or before the end date.';
if (!in_array($filters['method'], ['', 'cash', 'gcash', 'card'], true) || !in_array($filters['status'], ['', 'paid', 'unpaid', 'voided'], true)) $error = 'Choose a valid payment method and status.';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$report = null;
if ($error) http_response_code(400);
else {
    try { $report = snc_sales_report(snc_get_db(), $filters, $page); }
    catch (Throwable $e) { http_response_code(503); $error = 'Sales records are unavailable. Please try again.'; }
}
$lastUpdated = $report !== null ? new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore')) : null;
$presets = [
    'Today' => [$today->format('Y-m-d'), $today->format('Y-m-d')],
    'Last 7 Days' => [$today->modify('-6 days')->format('Y-m-d'), $today->format('Y-m-d')],
    'This Month' => [$today->format('Y-m-01'), $today->format('Y-m-d')],
    'All Time' => ['', ''],
];
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sales &amp; Transactions | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-pos.css?v=2.5">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-menu.css?v=1.4">
  <link rel="stylesheet" href="../assets/css/snc-sales.css?v=1.2">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-void-order.js?v=1.0" defer></script>
  <script src="../assets/js/snc-void-request.js?v=1.1" defer></script>
  <script src="../assets/js/snc-order-details.js?v=1.5" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . snc_sales_escape(json_encode($flash)) . '"' : '' ?>>
  <div class="snc-shell" id="snc-shell">
    <?php $adminPage = 'sales'; require __DIR__ . '/../includes/admin-shell.php'; ?>
    <main class="snc-dashboard snc-sales-main">
      <section class="snc-dashboard__header"><div><h1 class="snc-dashboard__title">Sales &amp; Transactions</h1><p class="snc-dashboard__subtitle">Review sales and recorded orders across all cashiers.</p></div><div class="snc-sales-refresh"><?php if ($lastUpdated): ?><span>Last updated <time datetime="<?= $lastUpdated->format('c') ?>" title="<?= $lastUpdated->format('Y-m-d H:i:s') ?> (UTC+08:00)"><?= $lastUpdated->format('g:i A') ?></time></span><?php else: ?><span>Updates unavailable</span><?php endif; ?><a class="snc-menu-button" href="?<?= snc_sales_escape(http_build_query($filters + ['page' => $report['page'] ?? $page])) ?>">Refresh</a></div></section>
      <section class="snc-card snc-sales-filters" aria-label="Transaction filters">
        <nav class="snc-menu-actions" aria-label="Date shortcuts">
          <?php foreach ($presets as $label => [$from, $to]): $selected = $filters['from'] === $from && $filters['to'] === $to; ?>
          <a class="snc-menu-button<?= $selected ? ' snc-menu-button--primary' : '' ?>" href="?<?= snc_sales_escape(http_build_query(array_replace($filters, ['from' => $from, 'to' => $to]))) ?>"<?= $selected ? ' aria-current="true"' : '' ?>><?= snc_sales_escape($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <form class="snc-menu-filters" method="get" action="sales.php">
          <label>Order / Customer / Cashier<input name="search" maxlength="120" value="<?= snc_sales_escape($filters['search']) ?>" placeholder="Search transactions"></label>
          <label>From<input type="date" name="from" value="<?= snc_sales_escape($filters['from']) ?>"></label>
          <label>To<input type="date" name="to" value="<?= snc_sales_escape($filters['to']) ?>"></label>
          <label>Payment method<select name="method"><?php foreach (['' => 'All payment methods', 'cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'] as $value => $label): ?><option value="<?= $value ?>"<?= $filters['method'] === $value ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
          <label>Transaction status<select name="status"><?php foreach (['' => 'All transactions', 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'voided' => 'Voided'] as $value => $label): ?><option value="<?= $value ?>"<?= $filters['status'] === $value ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
          <div class="snc-menu-actions snc-sales-filter-actions"><button class="snc-menu-button snc-menu-button--primary" type="submit">Apply Filters</button><a class="snc-menu-button" href="sales.php">Reset</a></div>
        </form>
        <p class="snc-menu-hint" title="Reporting timezone: UTC+08:00">Dates follow payment time, or order time if none is recorded. Reset shows today.</p>
      </section>
      <?php if ($error): ?><section class="snc-card"><p role="alert"><?= snc_sales_escape($error) ?></p></section><?php else: $totals = $report['totals']; ?>
      <section class="snc-kpi-grid" aria-label="Sales totals for all matching records">
        <?php foreach ([['Paid Sales', snc_sales_money($totals['paid_sales']), 'After discounts; excludes unpaid and voided orders'], ['Paid Orders', number_format((int) $totals['paid_orders']), 'Paid orders at any kitchen status'], ['Discounts', snc_sales_money($totals['discounts']), 'Recorded discounts on paid orders'], ['Average Paid Order', snc_sales_money($totals['average_paid_order']), 'Paid sales divided by paid orders']] as [$label, $value, $hint]): ?>
        <article class="snc-kpi-card"><h2 class="snc-kpi-card__label"><?= $label ?></h2><p class="snc-kpi-card__value"><?= $value ?></p><p class="snc-kpi-card__meta"><?= $hint ?></p></article>
        <?php endforeach; ?>
      </section>
      <section class="snc-card snc-sales-transactions">
        <div class="snc-menu-results"><h2>Transactions <span class="snc-menu-count"><?= (int) $totals['matched_orders'] ?></span></h2><span>Page <?= $report['page'] ?> of <?= $report['pages'] ?></span></div>
        <div class="snc-table-wrap" role="region" aria-label="Owner transaction history" tabindex="0"><table class="snc-table snc-sales-table">
          <thead><tr><th>Order / Date</th><th>Cashier</th><th>Type / Customer</th><th>Total</th><th>Payment / Status</th><th>Kitchen</th><th>Actions</th></tr></thead>
          <tbody><?php foreach ($report['orders'] as $order): ?>
            <tr><td><a class="snc-order-link" data-order-details="<?= (int) $order['id'] ?>" href="order.php?order_id=<?= (int) $order['id'] ?>"><?= snc_sales_escape($order['order_number']) ?></a><time class="snc-order-secondary" datetime="<?= snc_sales_escape(str_replace(' ', 'T', $order['transaction_at'])) ?>"><?= snc_sales_escape($order['transaction_at']) ?></time></td>
            <td><?= snc_sales_escape($order['cashier_name'] ?? 'Former staff') ?></td><td><?= $order['order_type'] === 'takeout' ? 'Takeout' : 'Dine-In' ?><span class="snc-order-secondary snc-order-customer"><?= snc_sales_escape(trim($order['table_or_note']) ?: '—') ?></span></td><td class="snc-sales-amount"><?= snc_sales_money($order['total_amount']) ?></td>
            <td><?= snc_sales_escape(['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'][$order['payment_method'] ?? ''] ?? '—') ?><span class="snc-sales-payment" data-payment-status="<?= snc_sales_escape($order['payment_status']) ?>"><?= snc_sales_escape(ucfirst($order['payment_status'])) ?></span></td><td><span class="snc-status-pill" data-status="<?= snc_sales_escape($order['payment_status']==='voided'?'voided':$order['kitchen_status']) ?>"><?= snc_sales_escape($order['payment_status']==='voided'?'Voided':ucfirst($order['kitchen_status'])) ?></span></td><td><a class="snc-menu-button" data-order-details="<?= (int) $order['id'] ?>" href="order.php?order_id=<?= (int) $order['id'] ?>" aria-label="View details for <?= snc_sales_escape($order['order_number']) ?>">View Details</a></td></tr>
          <?php endforeach; ?><?php if (!$report['orders']): ?><tr><td colspan="7">No transactions match these filters. Try a wider date range or All Time.</td></tr><?php endif; ?></tbody>
        </table></div>
        <p class="snc-menu-hint snc-sales-note">Totals include all matching records. Cash change is excluded.</p>
        <nav class="snc-menu-pagination" aria-label="Transaction pages"><?php if ($report['page'] > 1): ?><a class="snc-menu-button" href="?<?= snc_sales_escape(http_build_query($filters + ['page' => $report['page'] - 1])) ?>">Previous</a><?php endif; ?><?php if ($report['page'] < $report['pages']): ?><a class="snc-menu-button" href="?<?= snc_sales_escape(http_build_query($filters + ['page' => $report['page'] + 1])) ?>">Next</a><?php endif; ?></nav>
      </section>
      <?php endif; ?>
    </main></div>
  </div>
  <?php $sncOrderDialogReadOnly = true; require __DIR__ . '/../includes/cashier-order-dialog.php'; ?>
</body></html>
