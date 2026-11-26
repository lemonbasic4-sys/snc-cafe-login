<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = snc_require_auth(['cashier']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();

$menuItems = [];
$categories = [];
try {
    $pdo = snc_get_db();
    $stmt = $pdo->query(
        'SELECT id, name, category, price
         FROM menu_items
         WHERE is_available = 1
         ORDER BY category ASC, name ASC'
    );
    $menuItems = $stmt->fetchAll();
    foreach ($menuItems as $item) {
        $cat = (string) $item['category'];
        if (!in_array($cat, $categories, true)) {
            $categories[] = $cat;
        }
    }
} catch (Throwable $e) {
    $menuItems = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashier POS | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-pos.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-pos.js?v=1.0" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <header class="snc-pos-bar">
    <div class="snc-pos-bar__brand">
      <img class="snc-pos-bar__emblem" src="../assets/images/snc-dreamcatcher-emblem.svg" alt="">
      <div>
        <p class="snc-pos-bar__title">SNC 2018 · Cashier POS</p>
        <p class="snc-pos-bar__sub">CAFÉ &amp; RESTO</p>
      </div>
    </div>
    <div class="snc-pos-bar__actions">
      <span class="snc-pos-bar__user"><?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
      <form action="../logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="snc-pos-logout" type="submit">Sign Out</button>
      </form>
    </div>
  </header>

  <main class="snc-pos-layout">
    <div class="snc-pos-main">
      <section class="snc-pos-card" aria-label="Menu items">
        <div class="snc-pos-head">
          <h2>Menu</h2>
          <div class="snc-cat-tabs" role="group" aria-label="Filter by category">
            <button class="snc-cat-btn is-active" type="button" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
              <button class="snc-cat-btn" type="button" data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="snc-menu-grid">
          <?php if ($menuItems === []): ?>
            <p>No menu items available. Run <code>database/seed_menu.sql</code> to load starter items.</p>
          <?php else: ?>
            <?php foreach ($menuItems as $item): ?>
              <button
                class="snc-menu-item"
                type="button"
                data-id="<?= (int) $item['id'] ?>"
                data-name="<?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?>"
                data-category="<?= htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8') ?>"
                data-price="<?= htmlspecialchars(number_format((float) $item['price'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
              >
                <span class="snc-menu-item__cat"><?= htmlspecialchars((string) $item['category'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="snc-menu-item__name"><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="snc-menu-item__price">₱<?= number_format((float) $item['price'], 2) ?></span>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="snc-pos-card" aria-label="Recent orders">
        <div class="snc-pos-head">
          <h2>Recent Orders</h2>
        </div>
        <table class="snc-orders-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Type</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Kitchen</th>
            </tr>
          </thead>
          <tbody id="snc-recent-orders-body">
            <tr><td colspan="5">Loading recent orders...</td></tr>
          </tbody>
        </table>
      </section>
    </div>

    <aside class="snc-pos-card snc-cart" aria-label="Current order">
      <input type="hidden" id="snc-pos-csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <div class="snc-pos-head">
        <h2>Current Order</h2>
        <button class="snc-cat-btn" id="snc-pos-clear" type="button">Clear</button>
      </div>

      <div class="snc-cart-meta">
        <div class="snc-field">
          <label for="snc-order-type">Order Type</label>
          <select class="snc-input" id="snc-order-type">
            <option value="dine_in">Dine-In</option>
            <option value="takeout">Takeout</option>
          </select>
        </div>
        <div class="snc-field">
          <label for="snc-order-note">Table / Customer</label>
          <input class="snc-input" id="snc-order-note" type="text" maxlength="80" placeholder="e.g. Table 4">
        </div>
      </div>

      <ul class="snc-cart-list" id="snc-cart-list"></ul>

      <div class="snc-summary-box">
        <div class="snc-summary-line snc-summary-line--total">
          <span>Total</span>
          <span id="snc-pos-total">₱0.00</span>
        </div>
        <div class="snc-summary-line">
          <span>Change</span>
          <span id="snc-pos-change">₱0.00</span>
        </div>
      </div>

      <div class="snc-pay-grid">
        <div class="snc-field">
          <label for="snc-pay-method">Payment</label>
          <select class="snc-input" id="snc-pay-method">
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
            <option value="card">Card</option>
          </select>
        </div>
        <div class="snc-field">
          <label for="snc-amount-tendered">Amount Tendered</label>
          <input class="snc-input" id="snc-amount-tendered" type="number" min="0" step="0.01" placeholder="0.00">
        </div>
      </div>

      <button class="snc-pos-submit" id="snc-pos-submit" type="button" disabled>Charge &amp; Send to Kitchen</button>
    </aside>
  </main>
</body>
</html>
