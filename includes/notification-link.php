<?php
require_once __DIR__.'/auth.php';
function snc_notification_link(string $role): void
{
    static $assets=false;
    if(!$assets){$assets=true;?><link rel="stylesheet" href="<?= htmlspecialchars(snc_url('assets/css/snc-inbox.css'),ENT_QUOTES,'UTF-8') ?>?v=1.0"><script src="<?= htmlspecialchars(snc_url('assets/js/snc-inbox.js'),ENT_QUOTES,'UTF-8') ?>?v=1.0" defer></script><?php }
    ?><a class="snc-inbox-link" href="notifications.php"<?= $role==='cashier'?' target="_blank" rel="noopener"':'' ?> data-notification-link><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg><span>Notifications</span><span class="snc-inbox-count" data-notification-count hidden></span></a><?php
}
