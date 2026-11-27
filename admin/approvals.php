<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/cashier-view.php';require_once __DIR__.'/../includes/void-requests.php';
$user=snc_require_auth(['admin']);$csrfToken=snc_csrf_token();header('Cache-Control: no-store');
$status=is_string($_GET['status']??null)?$_GET['status']:'pending';if(!in_array($status,['pending','approved','rejected','all'],true))$status='pending';
$search=is_string($_GET['search']??null)?mb_substr(trim($_GET['search']),0,120):'';
$page=max(1,(int)(filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT)?:1));$selectedId=filter_input(INPUT_GET,'request_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$error='';$report=null;$selected=null;$flash=snc_pop_flash();
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(!snc_verify_csrf(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null))throw new RuntimeException('Session expired. Refresh and try again.',403);
        if(!$selectedId||(string)$selectedId!==($_POST['request_id']??''))throw new InvalidArgumentException('Review the request shown on this page.');
        $result=snc_review_void_request(snc_get_db(),$user,$_POST);snc_set_flash('success',$result['message'],'Request Reviewed');
        header('Location: approvals.php?'.http_build_query(['status'=>$status,'search'=>$search,'page'=>$page,'request_id'=>$selectedId]),true,303);exit;
    }catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}
    catch(RuntimeException $e){$code=!($e instanceof PDOException)&&in_array($e->getCode(),[403,404,409],true)?$e->getCode():503;http_response_code($code);$error=$code===503?'Unable to confirm the result. Refresh this request before trying again.':$e->getMessage();}
}
try{
    $pdo=snc_get_db();$where=[];$params=[];
    if($status!=='all'){$where[]='r.status=?';$params[]=$status;}
    if($search!==''){$where[]='(LOCATE(?,o.order_number)>0 OR LOCATE(?,r.requester_name)>0)';$params[]=$search;$params[]=$search;}
    $sql=' FROM order_void_requests r JOIN orders o ON o.id=r.order_id';$filter=$where?' WHERE '.implode(' AND ',$where):'';
    $q=$pdo->prepare('SELECT COUNT(*)'.$sql.$filter);$q->execute($params);$total=(int)$q->fetchColumn();$pages=max(1,(int)ceil($total/20));$page=min($page,$pages);$offset=($page-1)*20;
    $q=$pdo->prepare('SELECT r.*,o.order_number,o.total_amount,o.kitchen_status,o.payment_status'.$sql.$filter." ORDER BY r.created_at DESC,r.id DESC LIMIT 20 OFFSET $offset");$q->execute($params);$rows=$q->fetchAll();$report=true;
    $counts=['pending'=>0,'approved'=>0,'rejected'=>0];foreach($pdo->query('SELECT status,COUNT(*) AS n FROM order_void_requests GROUP BY status') as $row)$counts[$row['status']]=(int)$row['n'];
    if($selectedId){$q=$pdo->prepare('SELECT r.*,o.order_number,o.total_amount,o.kitchen_status,o.payment_status'.$sql.' WHERE r.id=?');$q->execute([$selectedId]);$selected=$q->fetch();if(!$selected){http_response_code(404);$error='Request not found.';}}
}catch(Throwable $e){http_response_code(503);$error='Approval records are unavailable. Please try again.';}
function approval_url(array $changes=[]): string {global $status,$search,$page;return '?'.http_build_query(array_replace(['status'=>$status,'search'=>$search,'page'=>$page],$changes));}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Approvals | SNC 2018 Café &amp; Resto</title>
<link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
<link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0"><link rel="stylesheet" href="../assets/css/snc-pos.css?v=2.5"><link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1"><link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.2"><link rel="stylesheet" href="../assets/css/snc-menu.css?v=1.4"><link rel="stylesheet" href="../assets/css/snc-approvals.css?v=1.1"><link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
<script src="../assets/js/snc-navigation.js?v=1.0" defer></script><script src="../assets/js/snc-notifications.js?v=1.0" defer></script><script src="../assets/js/snc-void-order.js?v=1.0" defer></script><script src="../assets/js/snc-void-request.js?v=1.1" defer></script><script src="../assets/js/snc-order-details.js?v=1.5" defer></script>
<script src="../assets/js/snc-approvals.js?v=1.0" defer></script>
</head><body<?= $flash?' data-snc-flash="'.snc_cashier_escape(json_encode($flash)).'"':'' ?>><div class="snc-shell" id="snc-shell"><?php $adminPage='approvals';require __DIR__.'/../includes/admin-shell.php'; ?><main class="snc-dashboard snc-menu-workspace">
<section class="snc-dashboard__header"><div><h1 class="snc-dashboard__title">Approvals</h1><p class="snc-dashboard__subtitle">Review cashier void requests and record your decision.</p></div><a class="snc-menu-button" href="<?= snc_cashier_escape(approval_url(['request_id'=>$selectedId?:null])) ?>">Refresh</a></section>
<?php if($error): ?><p class="snc-menu-error" role="alert"><?= snc_cashier_escape($error) ?></p><?php endif; ?>
<?php if($report): ?>
<section class="snc-card snc-menu-panel"><nav class="snc-menu-actions" aria-label="Request status"><?php foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All Requests'] as $value=>$label): ?><a class="snc-menu-button<?= $status===$value?' snc-menu-button--primary':'' ?>" href="<?= snc_cashier_escape(approval_url(['status'=>$value,'page'=>1])) ?>"<?= $status===$value?' aria-current="page"':'' ?>><?= $label ?><?= $value!=='all'?' ('.$counts[$value].')':'' ?></a><?php endforeach; ?></nav>
<form class="snc-menu-filters snc-approval-search" method="get"><input type="hidden" name="status" value="<?= $status ?>"><label>Order / Requested by<input name="search" maxlength="120" value="<?= snc_cashier_escape($search) ?>" placeholder="Order number or cashier name"></label><button class="snc-menu-button" type="submit">Search</button><a class="snc-menu-button" href="?status=<?= $status ?>">Reset</a></form><p class="snc-menu-hint">Pending requests leave orders paid and active. Approval voids the order without processing a refund or returning ingredients.</p></section>
<section class="snc-card snc-menu-panel"><div class="snc-menu-results"><h2>Void Requests <span class="snc-menu-count"><?= $total ?></span></h2><span>Page <?= $page ?> of <?= $pages ?></span></div><div class="snc-orders-scroll" role="region" aria-label="Void requests" tabindex="0"><table class="snc-orders-table"><thead><tr><th>Order / Requested at</th><th>Requested by</th><th>Reason</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="6">No <?= $status==='all'?'':snc_cashier_escape($status).' ' ?>void requests found.</td></tr><?php endif; ?>
<?php foreach($rows as $row): ?><tr><td><?= snc_cashier_escape($row['order_number']) ?><br><small><?= snc_cashier_escape($row['created_at']) ?></small></td><td><?= snc_cashier_escape($row['requester_name']) ?></td><td class="snc-approval-reason"><?= snc_cashier_escape($row['reason']) ?></td><td><?= snc_cashier_money($row['total_amount']) ?></td><td><span class="snc-status-pill" data-status="<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td><td><a class="snc-menu-button" data-approval-review href="<?= snc_cashier_escape(approval_url(['request_id'=>$row['id']])) ?>"><?= $row['status']==='pending'?'Review Request':'View Decision' ?></a></td></tr><?php endforeach; ?></tbody></table></div>
<nav class="snc-menu-pagination" aria-label="Request pages"><?php if($page>1): ?><a class="snc-menu-button" href="<?= snc_cashier_escape(approval_url(['page'=>$page-1])) ?>">Previous</a><?php endif; ?><?php if($page<$pages): ?><a class="snc-menu-button" href="<?= snc_cashier_escape(approval_url(['page'=>$page+1])) ?>">Next</a><?php endif; ?></nav></section>
<?php endif; ?>
<?php if($selected): require __DIR__.'/../includes/approval-review.php'; endif; ?>
<?php $sncOrderDialogReadOnly=true;require __DIR__.'/../includes/cashier-order-dialog.php'; ?></main></div></div></body></html>
