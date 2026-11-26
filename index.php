<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$currentUser = snc_current_user();
if ($currentUser !== null) {
    header('Location: ' . snc_role_home((string) ($currentUser['role'] ?? '')));
    exit;
}

$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();
$requestedPortal = (string) ($_GET['portal'] ?? 'admin');
$initialPortal = in_array($requestedPortal, ['admin', 'staff'], true) ? $requestedPortal : 'admin';
$isStaffPortal = $initialPortal === 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#3d2b1f">
  <meta name="description" content="SNC 2018 Café & Resto management system sign-in">
  <title>Sign In | SNC 2018 Café & Resto</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="assets/css/snc-login.css?v=8.7">
  <link rel="stylesheet" href="assets/css/snc-notifications.css?v=1.0">
  <script src="assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="assets/js/snc-login.js?v=8.7" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <main class="snc-page">
    <section class="snc-login" data-role="<?= htmlspecialchars($initialPortal, ENT_QUOTES, 'UTF-8') ?>" aria-label="SNC Café sign in">
      <aside class="snc-brand-panel" aria-label="SNC Café branding and login portal selection">
        <div class="snc-brand-content">
          <div class="snc-emblem-frame" aria-hidden="true">
            <img class="snc-emblem" src="assets/images/snc-dreamcatcher-emblem.svg" alt="">
          </div>
          <h1 class="snc-brand-title">SNC 2018</h1>
          <p class="snc-brand-category">CAFÉ &amp; RESTO</p>
          <p class="snc-brand-description">Management System</p>
          <div class="snc-brand-divider" aria-hidden="true"></div>
          <div class="snc-role-switch" role="group" aria-label="Select login portal">
            <button class="snc-role-button<?= $isStaffPortal ? '' : ' is-active' ?>" type="button" data-snc-role="admin" aria-pressed="<?= $isStaffPortal ? 'false' : 'true' ?>">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 20 6v6c0 5-3 8-8 10-5-2-8-5-8-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
              <span>Admin Login</span>
            </button>
            <button class="snc-role-button<?= $isStaffPortal ? ' is-active' : '' ?>" type="button" data-snc-role="staff" aria-pressed="<?= $isStaffPortal ? 'true' : 'false' ?>">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M2 20v-2a7 7 0 0 1 14 0v2M17 5a3 3 0 0 1 0 6M18 14a6 6 0 0 1 4 6"/></svg>
              <span>Staff Login</span>
            </button>
          </div>
          <p class="snc-brand-footer">SNC 2018 Café &amp; Resto</p>
        </div>
      </aside>
      <section class="snc-form-panel" aria-labelledby="snc-form-title">
        <div class="snc-form-content">
          <header class="snc-form-heading">
            <p class="snc-portal-label" id="snc-portal-label"><?= $isStaffPortal ? 'STAFF PORTAL' : 'ADMIN PORTAL' ?></p>
            <h2 id="snc-form-title">Welcome Back</h2>
            <p class="snc-form-subtitle" id="snc-form-subtitle"><?= $isStaffPortal ? 'Sign in to your staff account.' : 'Sign in to your management account.' ?></p>
          </header>
          <form id="snc-login-form" action="api/login.php" method="post" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="portal" id="snc-portal-value" value="<?= htmlspecialchars($initialPortal, ENT_QUOTES, 'UTF-8') ?>">
            <div class="snc-form-field">
              <label for="snc-username">Username</label>
              <div class="snc-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                <input id="snc-username" name="username" type="text" placeholder="Enter your username" autocomplete="username" required>
              </div>
            </div>
            <div class="snc-form-field">
              <label for="snc-password">Password</label>
              <div class="snc-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input id="snc-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                <button class="snc-password-toggle" id="snc-password-toggle" type="button" aria-label="Show password" aria-pressed="false">Show</button>
              </div>
            </div>
            <button class="snc-submit" type="submit">Login</button>
            <p class="snc-form-status" id="snc-form-status" role="status" aria-live="polite"></p>
          </form>
          <p class="snc-form-footer">Authorized personnel only</p>
        </div>
      </section>
    </section>
  </main>
</body>
</html>
