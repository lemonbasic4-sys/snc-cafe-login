<?php
declare(strict_types=1);
function snc_inventory_escape(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function snc_inventory_page_start(array $user, string $csrfToken, string $title, ?array $flash = null): void {
    header('Cache-Control: no-store');
    ?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= snc_inventory_escape($title) ?> | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.2">
  <link rel="stylesheet" href="../assets/css/snc-menu.css?v=1.4">
  <link rel="stylesheet" href="../assets/css/snc-inventory.css?v=1.2">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-inventory.js?v=1.2" defer></script>
</head><body<?= $flash ? ' data-snc-flash="'.snc_inventory_escape(json_encode($flash)).'"' : '' ?>>
  <div class="snc-shell" id="snc-shell">
  <?php $adminPage='inventory'; require __DIR__.'/admin-shell.php'; ?>
  <main class="snc-dashboard snc-inventory-main">
    <?php
}
function snc_inventory_page_end(): void { echo '</main></div></div></body></html>'; }
