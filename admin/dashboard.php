<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = snc_require_auth(['admin']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();

$recentLogs = [];
$opsCounts = ['pending' => 0, 'preparing' => 0, 'ready' => 0];
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Business Overview | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-dashboard.js?v=1.0" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <div class="snc-shell" id="snc-shell">
    <aside class="snc-sidebar" id="snc-sidebar" aria-label="Owner navigation">
      <div class="snc-sidebar__inner">
        <div class="snc-sidebar__brand">
          <img class="snc-sidebar__emblem" src="../assets/images/snc-dreamcatcher-emblem.svg" alt="">
          <div class="snc-sidebar__brand-text">
            <p class="snc-sidebar__title">SNC 2018</p>
            <p class="snc-sidebar__subtitle">CAFÉ &amp; RESTO</p>
          </div>
        </div>

        <nav class="snc-sidebar__nav" aria-label="Management sections">
          <div>
            <p class="snc-nav-group__label">OVERVIEW</p>
            <ul class="snc-nav-list">
              <li>
                <a class="snc-nav-link is-active" href="dashboard.php" aria-current="page">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                  <span>Dashboard</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#live-operations" data-snc-module="Live Operations">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                  <span>Live Operations</span>
                  <span class="snc-nav-badge">Soon</span>
                </a>
              </li>
            </ul>
          </div>

          <div>
            <p class="snc-nav-group__label">MANAGEMENT</p>
            <ul class="snc-nav-list">
              <li>
                <a class="snc-nav-link is-planned" href="#menu" data-snc-module="Menu Management">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                  <span>Menu Management</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#inventory" data-snc-module="Inventory">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                  <span>Inventory</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#staff" data-snc-module="Staff Management">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M2 20v-2a7 7 0 0 1 14 0v2M17 5a3 3 0 0 1 0 6M18 14a6 6 0 0 1 4 6"/></svg>
                  <span>Staff Management</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#approvals" data-snc-module="Approvals">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                  <span>Approvals</span>
                </a>
              </li>
            </ul>
          </div>

          <div>
            <p class="snc-nav-group__label">BUSINESS INSIGHTS</p>
            <ul class="snc-nav-list">
              <li>
                <a class="snc-nav-link is-planned" href="#sales" data-snc-module="Sales & Transactions">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                  <span>Sales &amp; Transactions</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#analytics" data-snc-module="Analytics & Reports">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                  <span>Analytics &amp; Reports</span>
                </a>
              </li>
            </ul>
          </div>

          <div>
            <p class="snc-nav-group__label">SYSTEM</p>
            <ul class="snc-nav-list">
              <li>
                <a class="snc-nav-link is-planned" href="#notifications" data-snc-module="Notifications">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                  <span>Notifications</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#audit" data-snc-module="Audit Trail">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                  <span>Audit Trail</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link is-planned" href="#settings" data-snc-module="System Settings">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                  <span>System Settings</span>
                </a>
              </li>
            </ul>
          </div>
        </nav>

        <div class="snc-sidebar__footer">
          <div class="snc-sidebar__user">
            <p class="snc-sidebar__user-name"><?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="snc-sidebar__user-role">Business Owner · <?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <form action="../logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button class="snc-logout-btn" type="submit">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
              <span>Sign Out</span>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <div class="snc-sidebar-backdrop" id="snc-sidebar-backdrop" aria-hidden="true"></div>

    <div class="snc-workspace">
      <header class="snc-topbar">
        <div class="snc-topbar__left">
          <button
            class="snc-sidebar-toggle"
            id="snc-sidebar-toggle"
            type="button"
            aria-label="Toggle navigation sidebar"
            aria-controls="snc-sidebar"
            aria-expanded="true"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>
          </button>
          <span class="snc-topbar__crumb">Owner Workspace · Overview</span>
        </div>
        <div class="snc-topbar__right">
          <span class="snc-demo-pill" title="Financial and order metrics below are prototype placeholders until POS and Kitchen modules are connected">Demo Data · Financial Prototype</span>
        </div>
      </header>

      <main class="snc-dashboard">
        <section class="snc-dashboard__header" aria-labelledby="snc-overview-heading">
          <div>
            <h1 class="snc-dashboard__title" id="snc-overview-heading">Business Overview</h1>
            <p class="snc-dashboard__subtitle">Financial-first oversight for SNC 2018 Café &amp; Resto</p>
          </div>
          <div class="snc-period-bar" role="group" aria-label="Reporting period">
            <button class="snc-period-btn is-active" type="button" data-period="today" aria-pressed="true">Today</button>
            <button class="snc-period-btn" type="button" data-period="week" aria-pressed="false">This Week</button>
            <button class="snc-period-btn" type="button" data-period="month" aria-pressed="false">This Month</button>
          </div>
        </section>

        <section class="snc-kpi-grid" aria-label="Key financial metrics">
          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Net Sales</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value" id="snc-kpi-net-sales">₱18,450.00</p>
            <p class="snc-kpi-card__meta">Demo Data · Net of discounts</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Completed Orders</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value" id="snc-kpi-orders">64</p>
            <p class="snc-kpi-card__meta">Demo Data · Paid &amp; fulfilled</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Average Order Value</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value" id="snc-kpi-aov">₱288.28</p>
            <p class="snc-kpi-card__meta">Demo Data · Net Sales ÷ orders</p>
          </article>

          <article class="snc-kpi-card">
            <div class="snc-kpi-card__top">
              <h2 class="snc-kpi-card__label">Owner Attention</h2>
              <span class="snc-kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3 2.5 19.5h19L12 3z"/><path d="M12 10v4M12 17.5h.01"/></svg>
              </span>
            </div>
            <p class="snc-kpi-card__value" id="snc-kpi-attention">2</p>
            <p class="snc-kpi-card__meta">Demo Data · Pending review</p>
          </article>
        </section>

        <section class="snc-grid-primary" aria-label="Revenue performance and owner attention">
          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Revenue Performance</h2>
                <p class="snc-card__subtitle" id="snc-chart-subtitle">Hourly revenue performance (Today)</p>
              </div>
              <span class="snc-tag" id="snc-chart-comparison">Demo Data · +8.4% vs. yesterday</span>
            </div>
            <div class="snc-chart-box" aria-label="Revenue trend chart">
              <svg viewBox="0 0 560 185" width="100%" height="100%" role="img" aria-label="Revenue bar chart">
                <g fill="#3d2b1f">
                  <rect class="snc-svg-bar" x="22" y="90" width="38" height="62" rx="6" />
                  <rect class="snc-svg-bar" x="98" y="60" width="38" height="92" rx="6" />
                  <rect class="snc-svg-bar" x="174" y="24" width="38" height="128" rx="6" />
                  <rect class="snc-svg-bar" x="250" y="74" width="38" height="78" rx="6" />
                  <rect class="snc-svg-bar" x="326" y="48" width="38" height="104" rx="6" />
                  <rect class="snc-svg-bar" x="402" y="88" width="38" height="64" rx="6" />
                  <rect class="snc-svg-bar" x="478" y="128" width="38" height="24" rx="6" />
                </g>
                <g fill="#685344" font-size="11" font-weight="600" text-anchor="middle">
                  <text class="snc-svg-val" x="41" y="16">₱1.8k</text>
                  <text class="snc-svg-val" x="117" y="16">₱3.2k</text>
                  <text class="snc-svg-val" x="193" y="16">₱4.4k</text>
                  <text class="snc-svg-val" x="269" y="16">₱2.6k</text>
                  <text class="snc-svg-val" x="345" y="16">₱3.6k</text>
                  <text class="snc-svg-val" x="421" y="16">₱2.1k</text>
                  <text class="snc-svg-val" x="497" y="16">₱0.8k</text>

                  <text class="snc-svg-label" x="41" y="174">9A</text>
                  <text class="snc-svg-label" x="117" y="174">11A</text>
                  <text class="snc-svg-label" x="193" y="174">1P</text>
                  <text class="snc-svg-label" x="269" y="174">3P</text>
                  <text class="snc-svg-label" x="345" y="174">5P</text>
                  <text class="snc-svg-label" x="421" y="174">7P</text>
                  <text class="snc-svg-label" x="497" y="174">9P</text>
                </g>
              </svg>
            </div>
          </article>

          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Owner Attention</h2>
                <p class="snc-card__subtitle">Exceptions and approvals awaiting review</p>
              </div>
              <span class="snc-tag">Demo Data</span>
            </div>
            <ul class="snc-list">
              <li class="snc-list-item">
                <div class="snc-list-item__main">
                  <p class="snc-list-item__title">Low Stock: Whole Milk</p>
                  <p class="snc-list-item__desc">Inventory threshold alert · Planned module</p>
                </div>
                <span class="snc-list-item__metric">4 L left</span>
              </li>
              <li class="snc-list-item">
                <div class="snc-list-item__main">
                  <p class="snc-list-item__title">Void Request #1042</p>
                  <p class="snc-list-item__desc">Awaiting owner approval · Planned module</p>
                </div>
                <span class="snc-list-item__metric">₱320.00</span>
              </li>
            </ul>
          </article>
        </section>

        <section class="snc-grid-secondary" aria-label="Best-selling products and live operations summary">
          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Best-Selling Products</h2>
                <p class="snc-card__subtitle">Ranked by Net Sales</p>
              </div>
              <span class="snc-tag">Demo Data</span>
            </div>
            <ul class="snc-list" id="snc-bestsellers-list">
              <li class="snc-list-item">
                <div class="snc-list-item__main">
                  <p class="snc-list-item__title">Iced Spanish Latte</p>
                  <p class="snc-list-item__desc">24 sold · Beverages</p>
                </div>
                <span class="snc-list-item__metric">₱3,840.00</span>
              </li>
              <li class="snc-list-item">
                <div class="snc-list-item__main">
                  <p class="snc-list-item__title">Bistre Cold Brew</p>
                  <p class="snc-list-item__desc">18 sold · Beverages</p>
                </div>
                <span class="snc-list-item__metric">₱2,880.00</span>
              </li>
              <li class="snc-list-item">
                <div class="snc-list-item__main">
                  <p class="snc-list-item__title">Truffle Cream Pasta</p>
                  <p class="snc-list-item__desc">11 sold · Kitchen</p>
                </div>
                <span class="snc-list-item__metric">₱3,520.00</span>
              </li>
            </ul>
          </article>

          <article class="snc-card">
            <div class="snc-card__head">
              <div>
                <h2 class="snc-card__title">Live Operations Summary</h2>
                <p class="snc-card__subtitle">Read-only monitoring snapshot (Under Discussion)</p>
              </div>
              <span class="snc-tag">Read-Only</span>
            </div>
            <div class="snc-ops-strip">
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $opsCounts['pending'] ?></p>
                <p class="snc-ops-stat__label">Pending</p>
              </div>
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $opsCounts['preparing'] ?></p>
                <p class="snc-ops-stat__label">Preparing</p>
              </div>
              <div class="snc-ops-stat">
                <p class="snc-ops-stat__num"><?= $opsCounts['ready'] ?></p>
                <p class="snc-ops-stat__label">Ready</p>
              </div>
            </div>
            <p class="snc-card__subtitle">Live read-only counts from `orders`.</p>
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
                    <td colspan="4">No audit events recorded yet.</td>
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
