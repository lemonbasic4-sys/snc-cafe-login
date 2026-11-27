<?php
$reviewUrl=approval_url(['request_id'=>(int)$selected['id']]);
$reviewStep=$selected['status']==='pending'&&$error&&in_array($_POST['decision']??null,['approved','rejected'],true)?$_POST['decision']:'details';
?>
<section id="snc-approval-review" class="snc-card snc-menu-panel snc-approval-review" data-review-step="<?= $reviewStep ?>" data-review-url="<?= snc_cashier_escape($reviewUrl) ?>">
<div class="snc-approval-head"><h2 id="snc-approval-title">Review Void Request</h2><a class="snc-menu-button" data-approval-close href="<?= snc_cashier_escape(approval_url()) ?>">Close</a></div>
<?php if($error): ?><p class="snc-menu-error" role="alert"><?= snc_cashier_escape($error) ?></p><?php endif; ?>
<div class="snc-approval-body">
<div class="snc-approval-order"><h3><?= snc_cashier_escape($selected['order_number']) ?></h3><span class="snc-status-pill" data-status="<?= $selected['status'] ?>"><?= ucfirst($selected['status']) ?></span></div>
<div data-approval-step="details">
<dl class="snc-order-facts"><div><dt>Requested by</dt><dd><?= snc_cashier_escape($selected['requester_name']) ?></dd></div><div><dt>Requested at</dt><dd><?= snc_cashier_escape($selected['created_at']) ?></dd></div><div><dt>Order total</dt><dd><?= snc_cashier_money($selected['total_amount']) ?></dd></div><div><dt>Current kitchen status</dt><dd><?= snc_cashier_escape(ucfirst($selected['kitchen_status'])) ?></dd></div></dl>
<div class="snc-approval-reason-box"><h3>Request reason</h3><p><?= snc_cashier_escape($selected['reason']) ?></p></div>
<a class="snc-order-link" data-order-details="<?= (int)$selected['order_id'] ?>" href="order.php?order_id=<?= (int)$selected['order_id'] ?>">View Order Details</a>
<?php if($selected['status']!=='pending'): ?><p class="snc-approval-reason"><strong><?= ucfirst($selected['status']) ?> by <?= snc_cashier_escape($selected['reviewer_name']) ?></strong> · <?= snc_cashier_escape($selected['reviewed_at']) ?><br><?= snc_cashier_escape($selected['decision_reason']) ?></p><?php endif; ?>
</div>
<?php if($selected['status']==='pending'): ?>
<form id="snc-approval-approve" class="snc-approval-confirm" method="post" action="<?= snc_cashier_escape($reviewUrl) ?>" data-approval-decision data-approval-step="approved">
<input type="hidden" name="csrf_token" value="<?= snc_cashier_escape($csrfToken) ?>"><input type="hidden" name="request_id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="decision" value="approved"><input type="hidden" name="kitchen_status" value="<?= snc_cashier_escape($selected['kitchen_status']) ?>">
<h3>Approve this void?</h3><p>This removes <?= snc_cashier_money($selected['total_amount']) ?> from paid sales and removes the order from the active kitchen queue.</p><p class="snc-menu-hint">Payment is not refunded and ingredients are not returned. This cannot be undone here.</p>
<button class="snc-menu-button snc-void-trigger snc-approval-native-submit" type="submit">Confirm Approval &amp; Void</button></form>
<form id="snc-approval-reject" class="snc-approval-confirm" method="post" action="<?= snc_cashier_escape($reviewUrl) ?>" data-approval-decision data-approval-step="rejected">
<input type="hidden" name="csrf_token" value="<?= snc_cashier_escape($csrfToken) ?>"><input type="hidden" name="request_id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="decision" value="rejected">
<label>Reason for rejection<textarea class="snc-input" name="reason" required minlength="3" maxlength="240" rows="3" placeholder="Explain why this request is being rejected"><?= snc_cashier_escape(is_string($_POST['reason']??null)?$_POST['reason']:'') ?></textarea></label><p class="snc-menu-hint">The cashier will see this reason. The order remains unchanged.</p>
<button class="snc-menu-button snc-approval-native-submit" type="submit">Confirm Rejection</button></form>
<?php endif; ?>
</div>
<?php if($selected['status']==='pending'): ?><div class="snc-approval-footer" hidden><button class="snc-menu-button snc-approval-back" type="button" data-approval-back hidden>Back to Request</button><button class="snc-menu-button" type="button" data-approval-show="rejected">Reject Request</button><button class="snc-menu-button snc-menu-button--primary" type="button" data-approval-show="approved">Approve Void</button><button class="snc-menu-button snc-menu-button--primary" type="submit" form="snc-approval-approve" data-approval-confirm="approved" hidden>Confirm Approval &amp; Void</button><button class="snc-menu-button" type="submit" form="snc-approval-reject" data-approval-confirm="rejected" hidden>Confirm Rejection</button></div><?php endif; ?>
</section>
