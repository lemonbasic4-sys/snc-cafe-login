<?php
declare(strict_types=1);

function snc_cashier_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function snc_cashier_money(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

function snc_cashier_page_start(string $title): void
{
    header('Cache-Control: no-store');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= snc_cashier_escape($title) ?> | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-pos.css?v=2.5">
  <script src="../assets/js/snc-void-request.js?v=1.1" defer></script>
  <script src="../assets/js/snc-order-details.js?v=1.5" defer></script>
</head>
<body>
  <header class="snc-pos-bar">
    <div class="snc-pos-bar__brand">
      <img class="snc-pos-bar__emblem" src="../assets/images/snc-dreamcatcher-emblem.svg" alt="">
      <div><p class="snc-pos-bar__title">SNC 2018 · <?= snc_cashier_escape($title) ?></p><p class="snc-pos-bar__sub">CAFÉ &amp; RESTO</p></div>
    </div>
    <div class="snc-pos-bar__actions"><?php require_once __DIR__.'/notification-link.php';snc_notification_link('cashier'); ?><a class="snc-pos-logout snc-receipt-link" href="index.php">Back to Cashier</a></div>
  </header>
  <main class="snc-cashier-records">
    <?php
}

function snc_cashier_page_end(): void
{
    echo '</main></body></html>';
}
