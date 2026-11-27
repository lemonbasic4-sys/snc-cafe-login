<dialog class="snc-order-dialog" id="snc-order-dialog" aria-labelledby="snc-order-dialog-title"<?= !empty($sncOrderDialogReadOnly) ? ' data-print-disabled="true"' : '' ?>>
  <div class="snc-pos-head snc-order-dialog__head">
    <h2 id="snc-order-dialog-title">Order Details</h2>
    <form method="dialog"><button class="snc-cat-btn" type="submit" data-request-control autofocus>Close</button></form>
  </div>
  <div class="snc-order-dialog__body">
    <p class="snc-record-muted" id="snc-order-dialog-status" role="status">Loading order details…</p>
    <div id="snc-order-dialog-content" hidden></div>
  </div>
  <div class="snc-order-dialog__actions">
    <button class="snc-cat-btn snc-request-action" type="button" id="snc-order-dialog-request" hidden>Request Void</button>
    <button class="snc-cat-btn snc-request-action" type="button" id="snc-order-dialog-back" data-request-control hidden>Back to Details</button>
    <button class="snc-cat-btn" type="button" id="snc-order-dialog-retry" hidden>Try Again</button>
    <a class="snc-cat-btn snc-receipt-link" id="snc-order-dialog-print" target="_blank" rel="noopener" hidden>Print Receipt</a>
    <button class="snc-cat-btn snc-request-send" type="submit" form="snc-order-request-form" id="snc-order-dialog-send-request" data-request-control hidden>Send Request</button>
  </div>
</dialog>
