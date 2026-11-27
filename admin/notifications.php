<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';
$user=snc_require_auth(['admin']);require __DIR__.'/../includes/notification-page.php';
