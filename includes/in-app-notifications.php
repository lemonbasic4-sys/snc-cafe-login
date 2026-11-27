<?php
declare(strict_types=1);
function snc_notify_void_request(PDO $pdo,int $requestId): void
{
    if(!$pdo->inTransaction())throw new LogicException('Notifications require the business transaction.');
    $q=$pdo->prepare('SELECT r.*,o.order_number FROM order_void_requests r JOIN orders o ON o.id=r.order_id WHERE r.id=?');$q->execute([$requestId]);$r=$q->fetch();if(!$r)throw new LogicException('Request not found.');
    if($r['status']==='pending'){
        $recipients=$pdo->query("SELECT id FROM users WHERE role='admin' AND is_active=1")->fetchAll(PDO::FETCH_COLUMN);$role='admin';$event='void_requested';$title='Void request received';$message=$r['requester_name'].' requested a void for '.$r['order_number'].'. Reason: '.$r['reason'];
    }else{
        $recipients=$r['requester_id']?[$r['requester_id']]:[];$role='cashier';$event='void_'.$r['status'];$title='Void request '.$r['status'];$message=$r['order_number'].' · '.$r['reviewer_name'].' '.$r['status'].' your void request. '.$r['decision_reason'];
    }
    $q=$pdo->prepare('INSERT INTO in_app_notifications(recipient_id,recipient_role,request_id,event_type,title,message) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id');
    foreach($recipients as $recipient)$q->execute([$recipient,$role,$requestId,$event,$title,mb_substr($message,0,500)]);
}
function snc_notification_scope(array $user): array
{
    if(!in_array($user['role']??'', ['admin','cashier'],true))throw new RuntimeException('Your role cannot access notifications.',403);
    return [(int)$user['id'],$user['role']];
}
function snc_notification_list(PDO $pdo,array $user,bool $unread=false,int $page=1): array
{
    $params=snc_notification_scope($user);$where=' WHERE recipient_id=? AND recipient_role=?';
    $q=$pdo->prepare('SELECT COUNT(*) FROM in_app_notifications'.$where.' AND read_at IS NULL');$q->execute($params);$unreadCount=(int)$q->fetchColumn();
    $filter=$where.($unread?' AND read_at IS NULL':'');$q=$pdo->prepare('SELECT COUNT(*),COALESCE(MAX(id),0) FROM in_app_notifications'.$filter);$q->execute($params);[$total,$through]=$q->fetch(PDO::FETCH_NUM);$pages=max(1,(int)ceil((int)$total/20));$page=max(1,min($page,$pages));$offset=($page-1)*20;
    $q=$pdo->prepare('SELECT id,title,message,created_at,read_at FROM in_app_notifications'.$filter." ORDER BY id DESC LIMIT 20 OFFSET $offset");$q->execute($params);
    return ['rows'=>$q->fetchAll(),'unread'=>$unreadCount,'total'=>(int)$total,'through'=>(int)$through,'page'=>$page,'pages'=>$pages];
}
function snc_notification_read(PDO $pdo,array $user,int $id,bool $all=false): ?array
{
    [$userId,$role]=snc_notification_scope($user);if($id<1)throw new InvalidArgumentException('Choose a valid notification.');
    if($all){$pdo->prepare('UPDATE in_app_notifications SET read_at=NOW() WHERE recipient_id=? AND recipient_role=? AND id<=? AND read_at IS NULL')->execute([$userId,$role,$id]);return null;}
    $q=$pdo->prepare('SELECT n.id,n.request_id,r.order_id FROM in_app_notifications n JOIN order_void_requests r ON r.id=n.request_id WHERE n.id=? AND n.recipient_id=? AND n.recipient_role=?');$q->execute([$id,$userId,$role]);$row=$q->fetch();if(!$row)throw new RuntimeException('Notification not found.',404);
    $pdo->prepare('UPDATE in_app_notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND recipient_id=? AND recipient_role=?')->execute([$id,$userId,$role]);return $row;
}
