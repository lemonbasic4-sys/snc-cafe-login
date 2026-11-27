<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/in-app-notifications.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
function snc_inbox_json(int $code,array $data): void {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
$user=snc_current_user();if(!$user)snc_inbox_json(401,['ok'=>false,'message'=>'Sign in again to view notifications.']);
try{
    [$id,$role]=snc_notification_scope($user);$pdo=snc_get_db();
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??null))throw new RuntimeException('Session expired. Refresh and try again.',403);
        $data=json_decode(file_get_contents('php://input')?:'',true);if(!is_array($data)||!in_array($data['action']??'',['read','read_all'],true))throw new InvalidArgumentException('Choose a valid notification action.');
        $target=filter_var($data['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$target)throw new InvalidArgumentException('Choose a valid notification.');
        snc_notification_read($pdo,$user,$target,$data['action']==='read_all');
    }elseif($_SERVER['REQUEST_METHOD']!=='GET'){header('Allow: GET, POST');snc_inbox_json(405,['ok'=>false,'message'=>'Method not allowed.']);}
    $q=$pdo->prepare('SELECT COALESCE(SUM(read_at IS NULL),0) AS unread,COALESCE(MAX(id),0) AS latest FROM in_app_notifications WHERE recipient_id=? AND recipient_role=?');$q->execute([$id,$role]);$count=$q->fetch();
    snc_inbox_json(200,['ok'=>true,'unread'=>(int)$count['unread'],'latest'=>(int)$count['latest'],'viewer'=>$id.':'.$role]);
}catch(InvalidArgumentException $e){snc_inbox_json(422,['ok'=>false,'message'=>$e->getMessage()]);}
catch(RuntimeException $e){$code=!($e instanceof PDOException)&&in_array($e->getCode(),[403,404],true)?$e->getCode():503;snc_inbox_json($code,['ok'=>false,'message'=>$code===503?'Notifications are temporarily unavailable.':$e->getMessage()]);}
catch(Throwable $e){snc_inbox_json(503,['ok'=>false,'message'=>'Notifications are temporarily unavailable.']);}
