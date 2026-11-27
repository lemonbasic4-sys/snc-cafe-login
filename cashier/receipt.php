<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
snc_require_auth(['cashier']);
require_once __DIR__.'/../includes/system-settings.php';
$receiptProfile=snc_receipt_profile(snc_settings_defaults());$paperWidth='80';
header('Cache-Control: no-store');

function snc_receipt_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function snc_receipt_money(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

$order = null;
$items = [];
$error = '';
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$orderId) {
    http_response_code(400);
    $error = 'Choose an order from Recent Orders to view its receipt.';
} else {
    try {
        $pdo = snc_get_db();
        $paperWidth=snc_settings_get($pdo)['paper_width'];
        // Use recorded names and prices, never the current menu. Payment is separate from kitchen completion.
        $stmt = $pdo->prepare(
            "SELECT o.order_number, o.order_type, o.table_or_note, o.subtotal, o.discount_amount,
                    o.total_amount, o.receipt_profile, p.method, p.amount_tendered, p.change_amount, p.paid_at,
                    u.full_name AS cashier_name
             FROM orders o
             JOIN payments p ON p.order_id = o.id
             LEFT JOIN users u ON u.id = p.cashier_id
             WHERE o.id = :id AND o.payment_status = 'paid'
             ORDER BY p.id ASC LIMIT 1"
        );
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch() ?: null;
        if ($order === null) {
            http_response_code(404);
            $error = 'A paid receipt is not available for this order.';
        } else {
            $saved=json_decode($order['receipt_profile']??'null',true);
            if(is_array($saved))foreach($receiptProfile as $key=>$value)if(is_string($saved[$key]??null))$receiptProfile[$key]=$saved[$key];
            $stmt = $pdo->prepare('SELECT item_name, unit_price, quantity, line_total FROM order_items WHERE order_id = :id ORDER BY id ASC');
            $stmt->execute([':id' => $orderId]);
            $items = $stmt->fetchAll();
        }
    } catch (Throwable $e) {
        http_response_code(503);
        $order = null;
        $error = 'The receipt could not be loaded. Return to the cashier and try again. Your saved order has not changed.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $order ? snc_receipt_escape($order['order_number']) . ' · ' : '' ?>Receipt | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-pos.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-receipt.css?v=1.2">
  <script src="../assets/js/snc-receipt.js?v=1.1" defer></script>
</head>
<body class="snc-receipt-page<?= $paperWidth==='58'?' snc-receipt-page--58':'' ?>">
  <header class="snc-pos-bar snc-receipt-controls">
    <div class="snc-pos-bar__brand">
      <img class="snc-pos-bar__emblem" src="../assets/images/snc-dreamcatcher-emblem.svg" alt="">
      <div><p class="snc-pos-bar__title">SNC 2018 · Receipt</p><p class="snc-pos-bar__sub">CAFÉ &amp; RESTO</p></div>
    </div>
    <a class="snc-pos-logout snc-receipt-link" href="index.php">Back to Cashier</a>
  </header>
  <main class="snc-receipt-workspace">
    <section class="snc-pos-card snc-receipt-controls" aria-labelledby="snc-receipt-heading">
      <div class="snc-pos-head">
        <h1 id="snc-receipt-heading">Receipt Preview</h1>
        <?php if ($order): ?>
        <div class="snc-receipt-actions">
          <div class="snc-field">
            <label for="snc-paper-width">Paper width</label>
            <select class="snc-input" id="snc-paper-width"><option value="80"<?= $paperWidth==='80'?' selected':'' ?>>80 mm</option><option value="58"<?= $paperWidth==='58'?' selected':'' ?>>58 mm</option></select>
          </div>
          <button class="snc-pos-submit" type="button" id="snc-print-receipt">Print Receipt</button>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($error !== ''): ?>
      <p role="alert"><?= snc_receipt_escape($error) ?></p>
      <?php else: ?>
      <p>Print with your thermal printer and matching paper size: 100% scale, no margins, headers or footers. Reprint anytime from Recent Orders.</p>
      <noscript><p>Use your browser’s Print command to print this <?= snc_receipt_escape($paperWidth) ?> mm receipt.</p></noscript>
      <?php endif; ?>
    </section>
    <?php if ($order): ?>
    <article class="snc-receipt" aria-label="Paid order receipt">
      <header class="snc-receipt-brand">
        <img src="../assets/images/snc-dreamcatcher-emblem.svg" alt="" width="30" height="38">
        <h2><?= snc_receipt_escape($receiptProfile['business_name']) ?></h2>
        <?php foreach(['receipt_header','business_address','contact_number'] as $key): if($receiptProfile[$key]!==''): ?><p class="snc-receipt-profile"><?= snc_receipt_escape($receiptProfile[$key]) ?></p><?php endif; endforeach; ?><p>TRANSACTION SUMMARY</p>
      </header>
      <dl class="snc-receipt-meta">
        <div class="snc-receipt-meta__full"><dt>Order</dt><dd><?= snc_receipt_escape($order['order_number']) ?></dd></div>
        <div class="snc-receipt-meta__full"><dt>Paid at</dt><dd><?= snc_receipt_escape($order['paid_at']) ?></dd></div>
        <div><dt>Cashier</dt><dd><?= snc_receipt_escape($order['cashier_name'] ?? 'Staff') ?></dd></div>
        <div><dt>Type</dt><dd><?= $order['order_type'] === 'takeout' ? 'Takeout' : 'Dine-In' ?></dd></div>
        <?php if ($order['table_or_note'] !== ''): ?>
        <div><dt>Table / Customer</dt><dd><?= snc_receipt_escape($order['table_or_note']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <table class="snc-receipt-items">
        <thead><tr><th scope="col">Item / Qty × Price</th><th scope="col">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr><td><?= snc_receipt_escape($item['item_name']) ?><span><?= (int) $item['quantity'] ?> × <?= snc_receipt_money($item['unit_price']) ?></span></td><td><?= snc_receipt_money($item['line_total']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <dl class="snc-receipt-meta snc-receipt-totals">
        <div><dt>Subtotal</dt><dd><?= snc_receipt_money($order['subtotal']) ?></dd></div>
        <?php if ((float) $order['discount_amount'] > 0): ?>
        <div><dt>Discount</dt><dd><?= snc_receipt_money($order['discount_amount']) ?></dd></div>
        <?php endif; ?>
        <div class="snc-receipt-total"><dt>Total</dt><dd><?= snc_receipt_money($order['total_amount']) ?></dd></div>
        <div><dt>Payment</dt><dd><?= snc_receipt_escape(strtoupper($order['method'])) ?> · PAID</dd></div>
        <div><dt>Amount tendered</dt><dd><?= snc_receipt_money($order['amount_tendered']) ?></dd></div>
        <div><dt>Change</dt><dd><?= snc_receipt_money($order['change_amount']) ?></dd></div>
      </dl>
      <footer class="snc-receipt-footer">
        <p>For inventory purposes only.<br>This is not an official receipt.</p>
        <p>Please ask the cashier if you need an official receipt.</p>
        <?php if($receiptProfile['receipt_footer']!==''): ?><p class="snc-receipt-profile"><?= snc_receipt_escape($receiptProfile['receipt_footer']) ?></p><?php endif; ?>
      </footer>
    </article>
    <?php endif; ?>
  </main>
</body>
</html>
