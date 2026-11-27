<?php require_once __DIR__.'/notification-link.php'; ?>
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
                <a class="snc-nav-link<?= $adminPage === 'dashboard' ? ' is-active' : '' ?>" href="dashboard.php"<?= $adminPage === 'dashboard' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                  <span>Dashboard</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'operations' ? ' is-active' : '' ?>" href="operations.php"<?= $adminPage === 'operations' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                  <span>Live Operations</span>
                </a>
              </li>
            </ul>
          </div>

          <div>
            <p class="snc-nav-group__label">MANAGEMENT</p>
            <ul class="snc-nav-list">
              <li>
                <a class="snc-nav-link<?= $adminPage === 'menu' ? ' is-active' : '' ?>" href="menu.php"<?= $adminPage === 'menu' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                  <span>Menu Management</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'inventory' ? ' is-active' : '' ?>" href="inventory.php"<?= $adminPage === 'inventory' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                  <span>Inventory</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'staff' ? ' is-active' : '' ?>" href="staff.php"<?= $adminPage === 'staff' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M2 20v-2a7 7 0 0 1 14 0v2M17 5a3 3 0 0 1 0 6M18 14a6 6 0 0 1 4 6"/></svg>
                  <span>Staff Management</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'approvals' ? ' is-active' : '' ?>" href="approvals.php"<?= $adminPage === 'approvals' ? ' aria-current="page"' : '' ?>>
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
                <a class="snc-nav-link<?= $adminPage === 'sales' ? ' is-active' : '' ?>" href="sales.php"<?= $adminPage === 'sales' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                  <span>Sales &amp; Transactions</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'analytics' ? ' is-active' : '' ?>" href="analytics.php"<?= $adminPage === 'analytics' ? ' aria-current="page"' : '' ?>>
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
                <a class="snc-nav-link<?= $adminPage === 'notifications' ? ' is-active' : '' ?>" href="notifications.php" data-notification-link>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                  <span>Notifications</span><span class="snc-inbox-count" data-notification-count hidden></span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'audit' ? ' is-active' : '' ?>" href="audit.php"<?= $adminPage === 'audit' ? ' aria-current="page"' : '' ?>>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                  <span>Audit Trail</span>
                </a>
              </li>
              <li>
                <a class="snc-nav-link<?= $adminPage === 'settings' ? ' is-active' : '' ?>" href="settings.php"<?= $adminPage === 'settings' ? ' aria-current="page"' : '' ?>>
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
          <span class="snc-topbar__crumb">Owner Workspace · <?= ['menu' => 'Menu Management', 'sales' => 'Sales &amp; Transactions', 'inventory' => 'Inventory', 'staff' => 'Staff Management', 'audit' => 'Audit Trail', 'approvals' => 'Approvals', 'notifications' => 'Notifications', 'analytics' => 'Analytics &amp; Reports', 'settings' => 'System Settings', 'operations' => 'Live Operations'][$adminPage] ?? 'Overview' ?></span>
        </div>
        <?php snc_notification_link('admin'); ?>
      </header>

