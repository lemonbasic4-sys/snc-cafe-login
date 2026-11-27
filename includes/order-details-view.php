  <section class="snc-pos-card">
    <div class="snc-pos-head"><h1>Order Details</h1><a class="snc-order-link" href="<?= !empty($sncOrderRecordReadOnly) ? 'operations.php' : (!empty($sncOwnerOrderView) ? 'sales.php' : 'history.php') ?>"><?= !empty($sncOrderRecordReadOnly) ? 'Back to Live Operations' : (!empty($sncOwnerOrderView) ? 'Back to Transactions' : 'View History') ?></a></div>
    <?php if ($error): ?><p role="alert"><?= snc_cashier_escape($error) ?></p>
    <?php else: ?>
    <div class="snc-pos-head"><h2><?= snc_cashier_escape($order['order_number']) ?></h2><span class="snc-status-pill" data-status="<?= snc_cashier_escape($order['payment_status']==='voided'?'voided':$order['kitchen_status']) ?>"><?= snc_cashier_escape($order['payment_status']==='voided'?'Voided':ucfirst($order['kitchen_status'])) ?></span></div>
    <?php if($order['payment_status']==='voided'): ?><section class="snc-void-notice"><h3>Order Voided</h3><?php if($void): ?><p><?= snc_cashier_escape($void['actor_name']) ?> · <?= snc_cashier_escape($void['voided_at']) ?></p><p><strong>Reason:</strong> <?= snc_cashier_escape($void['reason']) ?></p><p>Kitchen status at void: <?= snc_cashier_escape(ucfirst($void['kitchen_status'])) ?></p><?php else: ?><p>Void details are not available for this older record.</p><?php endif; ?><p>Voiding does not refund payment or return ingredients to stock.</p></section><?php endif; ?>
    <dl class="snc-order-facts">
      <div><dt>Ordered at</dt><dd><?= snc_cashier_escape($order['created_at']) ?></dd></div>
      <div><dt>Cashier</dt><dd><?= snc_cashier_escape($order['cashier_name'] ?? 'Staff') ?></dd></div>
      <div><dt>Order type</dt><dd><?= $order['order_type'] === 'takeout' ? 'Takeout' : 'Dine-In' ?></dd></div>
      <div><dt>Table / Customer</dt><dd><?= snc_cashier_escape(trim($order['table_or_note']) ?: '—') ?></dd></div>
      <div><dt>Payment status</dt><dd><?= snc_cashier_escape(ucfirst($order['payment_status'])) ?></dd></div>
      <?php if ($order['completed_at']): ?><div><dt>Completed at</dt><dd><?= snc_cashier_escape($order['completed_at']) ?></dd></div><?php endif; ?>
    </dl>
    <div class="snc-orders-scroll" role="region" aria-label="Order items" tabindex="0">
      <table class="snc-orders-table snc-order-items">
        <thead><tr><th>Item</th><th>Quantity</th><th>Unit Price</th><th>Amount</th></tr></thead>
        <tbody><?php foreach ($items as $item): ?><tr><td><?= snc_cashier_escape($item['item_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td><?= snc_cashier_money($item['unit_price']) ?></td><td><?= snc_cashier_money($item['line_total']) ?></td></tr><?php endforeach; ?></tbody>
      </table>
    </div>
    <dl class="snc-order-amounts">
      <div><dt>Subtotal</dt><dd><?= snc_cashier_money($order['subtotal']) ?></dd></div>
      <div><dt>Discount</dt><dd><?= snc_cashier_money($order['discount_amount']) ?></dd></div>
      <div class="snc-summary-line--total"><dt>Total</dt><dd><?= snc_cashier_money($order['total_amount']) ?></dd></div>
      <?php if ($payment): ?>
      <div><dt>Payment method</dt><dd><?= snc_cashier_escape(strtoupper($payment['method'])) ?></dd></div>
      <div><dt>Paid at</dt><dd><?= snc_cashier_escape($payment['paid_at']) ?></dd></div>
      <div><dt>Amount tendered</dt><dd><?= snc_cashier_money($payment['amount_tendered']) ?></dd></div>
      <div><dt>Change</dt><dd><?= snc_cashier_money($payment['change_amount']) ?></dd></div>
      <?php endif; ?>
    </dl>
    <?php require __DIR__.'/order-request-view.php'; ?>
    <?php if(empty($sncOrderRecordReadOnly)&&!empty($sncOwnerOrderView)&&$order['payment_status']==='paid'): ?>
    <details class="snc-void-panel"<?= !empty($voidError)?' open':'' ?>><summary class="snc-cat-btn snc-void-trigger">Void Order</summary><form class="snc-void-form" data-void-order method="post" action="order.php?order_id=<?= (int)$id ?>"><h3>Void <?= snc_cashier_escape($order['order_number']) ?>?</h3><p class="snc-void-help">This removes the order from paid sales and the active kitchen queue. Payment is not refunded and deducted ingredients are not returned to stock. This action cannot be undone here.</p><input type="hidden" name="action" value="void"><input type="hidden" name="order_id" value="<?= (int)$id ?>"><input type="hidden" name="kitchen_status" value="<?= snc_cashier_escape($order['kitchen_status']) ?>"><input type="hidden" name="csrf_token" value="<?= snc_cashier_escape($csrfToken) ?>"><input type="hidden" name="request_key" value="<?= snc_cashier_escape(is_string($_POST['request_key']??null)&&preg_match('/^[a-f0-9]{32}$/D',$_POST['request_key'])?$_POST['request_key']:bin2hex(random_bytes(16))) ?>"><label>Reason<input class="snc-input" name="reason" minlength="3" maxlength="240" required value="<?= snc_cashier_escape(is_string($_POST['reason']??null)?$_POST['reason']:'') ?>"></label><p class="snc-void-message" data-void-message role="status"><?= snc_cashier_escape($voidError??'') ?></p><div class="snc-void-actions"><button class="snc-cat-btn snc-void-trigger" type="submit">Confirm Void</button></div></form></details>
    <?php endif; ?>
    <?php if (empty($sncOwnerOrderView) && $payment && $order['payment_status'] === 'paid'): ?><a class="snc-cat-btn snc-receipt-link" href="receipt.php?order_id=<?= (int) $id ?>" target="_blank" rel="noopener">Print Receipt</a><?php endif; ?>
    <?php endif; ?>
  </section>
