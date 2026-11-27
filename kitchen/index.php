<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = snc_require_auth(['kitchen']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kitchen Orders | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-kitchen.css?v=1.3">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-kitchen.js?v=1.3" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <header class="snc-kitchen-bar">
    <div class="snc-kitchen-bar__brand">
      <img class="snc-kitchen-bar__emblem" src="../assets/images/snc-dreamcatcher-emblem.svg" alt="">
      <div>
        <p class="snc-kitchen-bar__title">SNC 2018 · Kitchen Orders</p>
        <p class="snc-kitchen-bar__sub">CAFÉ &amp; RESTO</p>
      </div>
    </div>
    <div class="snc-kitchen-bar__actions">
      <span class="snc-kitchen-bar__user"><?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
      <form action="../logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="snc-kitchen-logout" type="submit">Sign Out</button>
      </form>
    </div>
  </header>

  <main class="snc-kitchen-main">
    <input type="hidden" id="snc-kitchen-csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="snc-kitchen-toolbar">
      <div class="snc-kitchen-tabs" role="group" aria-label="Filter kitchen tickets">
        <button class="snc-kitchen-tab is-active" type="button" data-status="all" aria-pressed="true">All Active <span class="snc-kitchen-count">(—)</span></button>
        <button class="snc-kitchen-tab" type="button" data-status="pending" aria-pressed="false">Pending <span class="snc-kitchen-count">(—)</span></button>
        <button class="snc-kitchen-tab" type="button" data-status="preparing" aria-pressed="false">Preparing <span class="snc-kitchen-count">(—)</span></button>
        <button class="snc-kitchen-tab" type="button" data-status="ready" aria-pressed="false">Ready <span class="snc-kitchen-count">(—)</span></button>
      </div>
      <div class="snc-kitchen-updates">
        <span class="snc-kitchen-update-status" id="snc-kitchen-update-status" role="status">Loading queue…</span>
        <button class="snc-kitchen-refresh" id="snc-kitchen-refresh" type="button">Refresh</button>
      </div>
    </div>

    <section class="snc-ticket-grid" id="snc-ticket-grid" aria-label="Active kitchen tickets">
      <div class="snc-kitchen-empty">Loading kitchen tickets...</div>
    </section>
  </main>
</body>
</html>
