<?php
declare(strict_types=1);
require_once __DIR__.'/audit.php';
require_once __DIR__.'/in-app-notifications.php';

function snc_order_void_record(PDO $pdo,int $orderId): ?array
{
    $q=$pdo->prepare('SELECT actor_name,reason,kitchen_status,voided_at FROM order_voids WHERE order_id=?');$q->execute([$orderId]);return $q->fetch()?:null;
}
function snc_void_order(PDO $pdo,array $user,array $data): array
{
    if(($user['role']??'')!=='admin')throw new RuntimeException('Only the owner can void orders.',403);
    $id=filter_var($data['order_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $key=$data['request_key']??'';$reason=$data['reason']??null;$expected=$data['kitchen_status']??'';
    if(!$id||!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException('Reopen the order details before voiding this order.');
    if(!is_string($reason)||mb_strlen(trim($reason))<3||mb_strlen(trim($reason))>240||preg_match('/[\x00-\x1f\x7f]/u',$reason))throw new InvalidArgumentException('Enter a void reason between 3 and 240 characters.');
    if(!in_array($expected,['pending','preparing','ready','completed'],true))throw new InvalidArgumentException('Refresh the order details before voiding.');
    $reason=trim($reason);$owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try {
        $q=$pdo->prepare('SELECT id,full_name,role,is_active FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']??0]);$owner=$q->fetch();
        if(!$owner||$owner['role']!=='admin'||!(int)$owner['is_active'])throw new RuntimeException('Only an active owner can void orders.',403);
        $q=$pdo->prepare('SELECT id,order_number,payment_status,kitchen_status,total_amount FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$order=$q->fetch();
        if(!$order)throw new RuntimeException('Order not found.',404);
        $q=$pdo->prepare('SELECT * FROM order_voids WHERE order_id=? FOR UPDATE');$q->execute([$id]);$previous=$q->fetch();
        if($previous||$order['payment_status']==='voided'){
            if($previous&&$order['payment_status']==='voided'&&hash_equals($previous['request_key'],$key)&&(int)$previous['actor_id']===(int)$owner['id']&&$previous['reason']===$reason){
                if($owns)$pdo->commit();return ['ok'=>true,'message'=>'This order was already voided. No duplicate change was made.','order_id'=>$id];
            }
            throw new RuntimeException('This order is already voided. Refresh its details to view the recorded reason.',409);
        }
        if($order['payment_status']!=='paid')throw new RuntimeException('Only paid orders can be voided through this action.',409);
        if($order['kitchen_status']!==$expected)throw new RuntimeException('Kitchen progress changed since you opened this order. Refresh its details before confirming the void.',409);
        $q=$pdo->prepare('INSERT INTO order_voids(order_id,actor_id,actor_name,reason,kitchen_status,request_key) VALUES(?,?,?,?,?,?)');
        $q->execute([$id,$owner['id'],$owner['full_name'],$reason,$order['kitchen_status'],$key]);
        $pdo->prepare("UPDATE orders SET payment_status='voided' WHERE id=?")->execute([$id]);
        snc_audit_write($pdo,$owner,'order','voided','order',$id,$order['order_number'],['payment_status'=>'paid','kitchen_status'=>$order['kitchen_status'],'total_amount'=>$order['total_amount']],['payment_status'=>'voided','kitchen_status'=>$order['kitchen_status'],'total_amount'=>$order['total_amount'],'reason'=>$reason]);
        $q=$pdo->prepare("SELECT id FROM order_void_requests WHERE order_id=? AND status='pending' FOR UPDATE");$q->execute([$id]);$pending=$q->fetchColumn();
        if($pending){
            $decision=(int)($data['approval_request_id']??0)===(int)$pending?'Approved by owner.':'Order voided directly by owner.';
            $pdo->prepare("UPDATE order_void_requests SET status='approved',reviewer_id=?,reviewer_name=?,decision_reason=?,reviewed_at=NOW() WHERE id=?")->execute([$owner['id'],$owner['full_name'],$decision,$pending]);
            snc_audit_write($pdo,$owner,'order','void_approved','order',$id,$order['order_number'],['request_status'=>'pending'],['request_status'=>'approved','reason'=>$decision]);
            snc_notify_void_request($pdo,(int)$pending);
        }
        if($owns)$pdo->commit();return ['ok'=>true,'message'=>'Order voided. No refund or stock return was processed.','order_id'=>$id];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
