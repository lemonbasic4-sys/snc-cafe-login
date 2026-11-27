<?php
declare(strict_types=1);
require_once __DIR__.'/order-void.php';

function snc_void_request_reason(mixed $reason): string
{
    if(!is_string($reason)||mb_strlen(trim($reason))<3||mb_strlen(trim($reason))>240||preg_match('/[\x00-\x1f\x7f]/u',$reason))throw new InvalidArgumentException('Enter a reason between 3 and 240 characters.');
    return trim($reason);
}
function snc_void_request_latest(PDO $pdo,int $orderId): ?array
{
    $q=$pdo->prepare('SELECT id,requester_name,reason,status,created_at,reviewer_name,decision_reason,reviewed_at FROM order_void_requests WHERE order_id=? ORDER BY id DESC LIMIT 1');$q->execute([$orderId]);return $q->fetch()?:null;
}
function snc_void_request_actor(PDO $pdo,array $user,string $role): array
{
    $q=$pdo->prepare('SELECT id,full_name,role,is_active FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']??0]);$actor=$q->fetch();
    if(($user['role']??'')!==$role||!$actor||$actor['role']!==$role||!(int)$actor['is_active'])throw new RuntimeException($role==='admin'?'Only an active owner can review requests.':'Only an active cashier can request a void.',403);
    return $actor;
}
function snc_submit_void_request(PDO $pdo,array $user,array $data): array
{
    $id=filter_var($data['order_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$key=$data['request_key']??null;
    if(!$id||!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException('Reopen the order details to request a void.');
    $reason=snc_void_request_reason($data['reason']??null);$owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try{
        $actor=snc_void_request_actor($pdo,$user,'cashier');
        $q=$pdo->prepare('SELECT id,order_number,payment_status FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$order=$q->fetch();if(!$order)throw new RuntimeException('Order not found.',404);
        $q=$pdo->prepare('SELECT * FROM order_void_requests WHERE request_key=? FOR UPDATE');$q->execute([$key]);$prior=$q->fetch();
        if($prior){if((int)$prior['order_id']!==$id||(int)$prior['requester_id']!==(int)$actor['id']||$prior['reason']!==$reason)throw new RuntimeException('This confirmation was already used. Reopen the order details.',409);if($owns)$pdo->commit();return ['ok'=>true,'message'=>'Your request was already recorded. Refresh the order details for its status.'];}
        if($order['payment_status']!=='paid')throw new RuntimeException('Only paid orders can be requested for voiding.',409);
        $q=$pdo->prepare("SELECT id FROM order_void_requests WHERE order_id=? AND status='pending' FOR UPDATE");$q->execute([$id]);if($q->fetch())throw new RuntimeException('This order already has a pending void request. Refresh its details.',409);
        $pdo->prepare('INSERT INTO order_void_requests(order_id,requester_id,requester_name,reason,request_key) VALUES(?,?,?,?,?)')->execute([$id,$actor['id'],$actor['full_name'],$reason,$key]);
        snc_notify_void_request($pdo,(int)$pdo->lastInsertId());
        snc_audit_write($pdo,$actor,'order','void_requested','order',$id,$order['order_number'],null,['reason'=>$reason,'request_status'=>'pending']);
        if($owns)$pdo->commit();return ['ok'=>true,'message'=>'Void requested. The order remains paid and active until the owner approves.'];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function snc_review_void_request(PDO $pdo,array $user,array $data): array
{
    $id=filter_var($data['request_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$decision=$data['decision']??null;
    if(!$id||!in_array($decision,['approved','rejected'],true))throw new InvalidArgumentException('Choose a valid request and decision.');
    $reason=$decision==='rejected'?snc_void_request_reason($data['reason']??null):'';
    $owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try{
        $actor=snc_void_request_actor($pdo,$user,'admin');
        $q=$pdo->prepare('SELECT order_id FROM order_void_requests WHERE id=?');$q->execute([$id]);$orderId=$q->fetchColumn();if(!$orderId)throw new RuntimeException('Request not found.',404);
        $q=$pdo->prepare('SELECT id,order_number,payment_status FROM orders WHERE id=? FOR UPDATE');$q->execute([$orderId]);$order=$q->fetch();
        $q=$pdo->prepare('SELECT * FROM order_void_requests WHERE id=? FOR UPDATE');$q->execute([$id]);$request=$q->fetch();
        if($request['status']!=='pending'){
            if($request['status']===$decision&&(int)$request['reviewer_id']===(int)$actor['id']&&($decision!=='rejected'||$request['decision_reason']===$reason)){if($owns)$pdo->commit();return ['ok'=>true,'message'=>'This decision was already recorded.'];}
            throw new RuntimeException('This request has already been reviewed. Refresh to see the decision.',409);
        }
        if($decision==='approved'){
            snc_void_order($pdo,$actor,['order_id'=>(string)$orderId,'request_key'=>$request['request_key'],'reason'=>$request['reason'],'kitchen_status'=>$data['kitchen_status']??'','approval_request_id'=>$id]);
        }else{
            $pdo->prepare("UPDATE order_void_requests SET status='rejected',reviewer_id=?,reviewer_name=?,decision_reason=?,reviewed_at=NOW() WHERE id=?")->execute([$actor['id'],$actor['full_name'],$reason,$id]);
            snc_audit_write($pdo,$actor,'order','void_rejected','order',(int)$orderId,$order['order_number'],['request_status'=>'pending'],['request_status'=>'rejected','reason'=>$reason]);
            snc_notify_void_request($pdo,(int)$id);
        }
        if($owns)$pdo->commit();return ['ok'=>true,'message'=>$decision==='approved'?'Void approved. No refund or stock return was processed.':'Void request rejected. The order was not changed.'];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
