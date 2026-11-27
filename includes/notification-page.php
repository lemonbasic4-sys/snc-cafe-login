<?php
require_once __DIR__.'/cashier-view.php';require_once __DIR__.'/in-app-notifications.php';
header('Cache-Control: no-store');$csrfToken=snc_csrf_token();$unread=($_GET['filter']??'')==='unread';$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;$error='';$inbox=null;
$returnQuery=http_build_query(['filter'=>$unread?'unread':'all','page'=>$page]);
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!snc_verify_csrf(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null))throw new RuntimeException('Session expired. Refresh and try again.',403);
        $action=$_POST['action']??'';if(!in_array($action,['read','read_all','open'],true))throw new InvalidArgumentException('Choose a valid action.');
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id)throw new InvalidArgumentException('Choose a valid notification.');
        $row=snc_notification_read(snc_get_db(),$user,$id,$action==='read_all');
        $destination=$action==='open'?($user['role']==='admin'?'approvals.php?status=all&request_id='.(int)$row['request_id']:'order.php?order_id='.(int)$row['order_id']):'notifications.php?'.$returnQuery;
        header('Location: '.$destination,true,303);exit;
    }catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}
    catch(RuntimeException $e){$code=!($e instanceof PDOException)&&in_array($e->getCode(),[403,404],true)?$e->getCode():503;http_response_code($code);$error=$code===503?'Notifications are temporarily unavailable.':$e->getMessage();}
}
try{$inbox=snc_notification_list(snc_get_db(),$user,$unread,$page);}catch(Throwable $e){http_response_code(503);$error='Notifications are temporarily unavailable.';}
if($user['role']==='admin'){
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Notifications | SNC 2018 Café &amp; Resto</title><link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0"><link rel="stylesheet" href="../assets/css/snc-pos.css?v=2.5"><link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1"><link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.2"><script src="../assets/js/snc-navigation.js?v=1.0" defer></script></head><body><div class="snc-shell" id="snc-shell"><?php $adminPage='notifications';require __DIR__.'/admin-shell.php'; ?><main class="snc-dashboard"><?php
}else snc_cashier_page_start('Notifications');
?>
<section class="snc-pos-card snc-inbox-page"><div class="snc-pos-head"><div><h1>Notifications</h1><p class="snc-record-muted">Void requests and owner decisions.</p></div><a class="snc-cat-btn" href="?<?= snc_cashier_escape($returnQuery) ?>">Refresh</a></div>
<?php if($error): ?><p role="alert"><?= snc_cashier_escape($error) ?></p><?php endif; ?>
<?php if($inbox): ?><div class="snc-inbox-toolbar"><nav aria-label="Notification filter"><a class="snc-cat-btn<?= !$unread?' is-active':'' ?>" href="?filter=all"<?= !$unread?' aria-current="page"':'' ?>>All</a><a class="snc-cat-btn<?= $unread?' is-active':'' ?>" href="?filter=unread"<?= $unread?' aria-current="page"':'' ?>>Unread (<?= $inbox['unread'] ?>)</a></nav>
<?php if($inbox['unread']&&$inbox['through']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= snc_cashier_escape($csrfToken) ?>"><input type="hidden" name="action" value="read_all"><input type="hidden" name="id" value="<?= $inbox['through'] ?>"><button class="snc-cat-btn" type="submit">Mark All as Read</button></form><?php endif; ?></div>
<?php if(!$inbox['rows']): ?><p class="snc-inbox-empty">No <?= $unread?'unread ':'' ?>notifications yet.</p><?php endif; ?>
<div class="snc-inbox-list"><?php foreach($inbox['rows'] as $notice): ?><article class="snc-inbox-item<?= !$notice['read_at']?' is-unread':'' ?>"><div class="snc-inbox-item__head"><h2><?= snc_cashier_escape($notice['title']) ?></h2><?php if(!$notice['read_at']): ?><span class="snc-status-pill">Unread</span><?php endif; ?></div><p><?= snc_cashier_escape($notice['message']) ?></p><time class="snc-record-muted"><?= snc_cashier_escape($notice['created_at']) ?> · UTC+08:00</time><form method="post" class="snc-inbox-item__actions"><input type="hidden" name="csrf_token" value="<?= snc_cashier_escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$notice['id'] ?>"><button class="snc-cat-btn" type="submit" name="action" value="open"><?= $user['role']==='admin'?'View Request':'View Order' ?></button><?php if(!$notice['read_at']): ?><button class="snc-cat-btn" type="submit" name="action" value="read">Mark as Read</button><?php endif; ?></form></article><?php endforeach; ?></div>
<nav class="snc-inbox-pagination" aria-label="Notification pages"><span>Page <?= $inbox['page'] ?> of <?= $inbox['pages'] ?></span><?php foreach([-1=>'Previous',1=>'Next'] as $delta=>$label): $target=$inbox['page']+$delta;if($target<1||$target>$inbox['pages'])continue; ?><a class="snc-cat-btn" href="?<?= snc_cashier_escape(http_build_query(['filter'=>$unread?'unread':'all','page'=>$target])) ?>"><?= $label ?></a><?php endforeach; ?></nav>
<?php endif; ?></section>
<?php if($user['role']==='admin')echo '</main></div></div></body></html>';else snc_cashier_page_end(); ?>
