<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard-report.php';
header('Cache-Control: no-store');
function snc_dashboard_escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function snc_dashboard_money(mixed $value): string { return '₱' . number_format((float) $value, 2); }

$user = snc_require_auth(['admin']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();

$recentLogs = [];
$opsCounts = ['pending' => 0, 'preparing' => 0, 'ready' => 0];
$operationsAvailable = true;
try {
    $pdo = snc_get_db();
    $stmt = $pdo->query(
        'SELECT username_attempted, portal_selected, event_type, created_at
         FROM auth_audit_logs
         ORDER BY id DESC
         LIMIT 5'
    );
    $recentLogs = $stmt->fetchAll();

    $opsStmt = $pdo->query(
        "SELECT
           SUM(CASE WHEN kitchen_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN kitchen_status = 'preparing' THEN 1 ELSE 0 END) AS preparing_count,
           SUM(CASE WHEN kitchen_status = 'ready' THEN 1 ELSE 0 END) AS ready_count
         FROM orders
         WHERE payment_status = 'paid'"
    );
    $opsRow = $opsStmt->fetch();
    if ($opsRow) {
        $opsCounts['pending'] = (int) ($opsRow['pending_count'] ?? 0);
        $opsCounts['preparing'] = (int) ($opsRow['preparing_count'] ?? 0);
        $opsCounts['ready'] = (int) ($opsRow['ready_count'] ?? 0);
    }
} catch (Throwable $e) {
    $recentLogs = [];
    $operationsAvailable = false;
}
$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Singapore'));
$period = is_string($_GET['period'] ?? null) && in_array($_GET['period'], ['today', 'week', 'month'], true) ? $_GET['period'] : 'today';
$start = match ($period) {
    'week' => $today->modify('-' . ((int) $today->format('N') - 1) . ' days'),
    'month' => $today->modify('first day of this month'),
    default => $today,
};
$fromDate = $start->format('Y-m-d');
$toDate = $today->format('Y-m-d');
$rangeLabel = $start->format('M j, Y') . ($fromDate !== $toDate ? ' – ' . $today->format('M j, Y') : '');
$salesUrl = 'sales.php?' . http_build_query(['from' => $fromDate, 'to' => $toDate]);
$dashboardData = null;
try { $dashboardData = snc_dashboard_report(snc_get_db(), $fromDate, $toDate); }
catch (Throwable $e) { http_response_code(503); }
$totals = $dashboardData['totals'] ?? null;
$updatedAt = new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Business Overview | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.2">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <div class="snc-shell" id="snc-shell">
    <?php $adminPage = 'dashboard'; require __DIR__ . '/../includes/admin-shell.php'; ?>
      <main class="snc-dashboard">
        <section class="snc-dashboard__header" aria-labelledby="snc-overview-heading">
          <div>
            <h1 class="snc-dashboard__title" id="snc-overview-heading">Business Overview</h1>
            <p class="snc-dashboard__subtitle">Financial-first oversight for SNC 2018 Café &amp; Resto</p>
          </div>
          <nav class="snc-period-bar" aria-label="Dashboard reporting period">
            <?php foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label): ?>
            <a class="snc-period-btn<?= $period === $key ? ' is-active' : '' ?>" href="?period=<?= $key ?>"<?= $period === $key ? ' aria-current="true"' : '' ?>><?= $label ?></a>
            <?php endforeach; ?>
          </nav>
        </section>
        <div class="snc-dashboard-report-meta"><span><?= snc_dashboard_escape($rangeLabel) ?> · UTC+08:00 · <?= $dashboardData ? 'Updated ' . $updatedAt->format('g:i A') : 'Sales data unavailable' ?></span><a class="snc-dashboard-action" href="<?= snc_dashboard_escape($salesUrl) ?>">View Transactions</a><a class="snc-dashboard-action" href="?period=<?= $period ?>">Refresh</a></div>
        <?php if (!$dashboardData): ?><p class="snc-dashboard-report-error" role="alert">Sales data could not be loaded. Please refresh to try again.</p><?php endif; ?>

        <section class="snc-kpi-grid" aria-label="Key financial metrics">
          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Paid Sales</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value"><?= $totals ? snc_dashboard_money($totals['paid_sales']) : '—' ?></p>
            <p class="snc-kpi-card__meta">After discounts; excludes unpaid and voided orders</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Paid Orders</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value"><?= $totals ? number_format((int) $totals['paid_orders']) : '—' ?></p>
            <p class="snc-kpi-card__meta">Paid orders at any kitchen status</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Average Paid Order</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value"><?= $totals ? snc_dashboard_money($totals['average_paid_order']) : '—' ?></p>
            <p class="snc-kpi-card__meta">Paid sales divided by paid orders</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Discounts</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M20 12 12 20 3 11V3h8l9 9Z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value"><?= $totals ? snc_dashboard_money($totals['discounts']) : '—' ?></p>
            <p class="snc-kpi-card__meta">Recorded discounts on paid orders</p>
          </article>
        </section>

        <section class="snc-dashboard-revenue" aria-label="Revenue performance">
          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Revenue Performance</h2>
              <p class="snc-card__subtitle">Paid sales by <?= $dashboardData && $dashboardData['hourly'] ? 'hour' : 'day' ?> · <?= snc_dashboard_escape($rangeLabel) ?></p>
              </div>
            </div>
            <?php if (!$dashboardData): ?>
            <div class="snc-chart-box snc-chart-box--empty"><p>Sales data unavailable.</p></div>
            <?php elseif ((int) $totals['paid_orders'] === 0): ?>
            <div class="snc-chart-box snc-chart-box--empty"><p>No paid orders in this period.</p></div>
            <?php else: require __DIR__ . '/../includes/dashboard-chart.php'; endif; ?>
          </article>

        </section>

        <section class="snc-grid-secondary" aria-label="Best-selling products and live operations summary">
          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Best-Selling Products</h2>
                <p class="snc-card__subtitle">Top 5 by sales after discounts</p>
              </div>
              <span class="snc-tag"><?= $dashboardData ? 'Paid Sales' : 'Unavailable' ?></span>
            </div>
            <?php if (!$dashboardData): ?><p class="snc-card__subtitle">Product sales unavailable.</p>
            <?php elseif (!$dashboardData['products']): ?><p class="snc-card__subtitle">No paid product sales in this period.</p>
            <?php else: ?>
            <div class="snc-table-wrap"><table class="snc-table snc-dashboard-products"><thead><tr><th>Product</th><th>Qty Sold</th><th>Paid Sales</th></tr></thead><tbody>
            <?php foreach ($dashboardData['products'] as $product): ?>
              <tr><td><?= snc_dashboard_escape($product['item_name']) ?></td><td><?= number_format((int) $product['quantity']) ?></td><td><?= snc_dashboard_money((float) $product['net_cents'] / 100) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
          </article>

          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Live Operations Summary</h2>
                <p class="snc-card__subtitle">Current kitchen queue · independent of reporting period</p>
              </div>
              <span class="snc-tag">Read-Only</span>
            </div>
            <div class="snc-ops-strip">
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $operationsAvailable ? $opsCounts['pending'] : '—' ?></p>
                <p class="snc-ops-stat__label">Pending</p>
              </div>
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $operationsAvailable ? $opsCounts['preparing'] : '—' ?></p>
                <p class="snc-ops-stat__label">Preparing</p>
              </div>
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $operationsAvailable ? $opsCounts['ready'] : '—' ?></p>
                <p class="snc-ops-stat__label">Ready</p>
              </div>
            </div>
            <p class="snc-card__subtitle">Current paid orders awaiting kitchen completion.</p>
          </article>
        </section>

        <section class="snc-card" aria-label="Recent authentication activity">
          <div class="snc-card__head">
            <div>
              <h2 class="snc-card__title">Recent Business Activity</h2>
              <p class="snc-card__subtitle">Live authentication audit records from MariaDB</p>
            </div>
            <span class="snc-tag">Live DB</span>
          </div>
          <div class="snc-table-wrap">
            <table class="snc-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Portal</th>
                  <th>Event</th>
                  <th>Timestamp</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentLogs === []): ?>
                  <tr>
                    <td colspan="4"><?= $operationsAvailable ? 'No audit events recorded yet.' : 'Activity data unavailable.' ?></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentLogs as $log): ?>
                    <tr>
                      <td><?= htmlspecialchars((string) $log['username_attempted'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($log['portal_selected'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) $log['event_type'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) $log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
