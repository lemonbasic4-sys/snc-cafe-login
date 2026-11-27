<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/void-requests.php';
$p=snc_get_db();$p->beginTransaction();$tag='QA inbox '.bin2hex(random_bytes(5));
function inbox_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: $label\n";}
function inbox_reject(callable $fn,int $code,string $label): void {try{$fn();}catch(RuntimeException $e){if($e instanceof PDOException||$e->getCode()!==$code)throw $e;echo "PASS: $label\n";return;}throw new LogicException($label);}
try{
 $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();
 $p->prepare("INSERT INTO users(username,full_name,role,password_hash,is_active) VALUES(?,?,'cashier',?,1)")->execute([$tag,$tag,password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT)]);$cashier=['id'=>(int)$p->lastInsertId(),'full_name'=>$tag,'role'=>'cashier'];
 $p->prepare("INSERT INTO orders(order_number,cashier_id,table_or_note,payment_status,total_amount) VALUES(?,?,?,'paid',100)")->execute(['QA-INBOX-'.bin2hex(random_bytes(8)),$cashier['id'],$tag]);$orderId=(int)$p->lastInsertId();
 $data=['order_id'=>$orderId,'request_key'=>bin2hex(random_bytes(16)),'reason'=>'QA duplicate transaction'];snc_submit_void_request($p,$cashier,$data);snc_submit_void_request($p,$cashier,$data);$request=snc_void_request_latest($p,$orderId);$rid=(int)$request['id'];
 $q=$p->prepare("SELECT recipient_id,COUNT(*) AS n FROM in_app_notifications WHERE request_id=? AND event_type='void_requested' GROUP BY recipient_id");$q->execute([$rid]);$recipients=$q->fetchAll();$ownerCount=(int)$p->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();inbox_expect(count($recipients)===$ownerCount&&!array_filter($recipients,fn($r)=>(int)$r['n']!==1),'each active owner receives one new-request notification across retries');
 inbox_expect(snc_notification_list($p,$cashier)['total']===0,'request does not notify unrelated cashiers');
 $review=['request_id'=>$rid,'decision'=>'rejected','reason'=>'QA request does not justify a void'];snc_review_void_request($p,$owner,$review);snc_review_void_request($p,$owner,$review);$list=snc_notification_list($p,$cashier);inbox_expect($list['total']===1&&$list['unread']===1&&str_contains($list['rows'][0]['message'],$review['reason']),'requester receives one rejection with the owner reason');$notice=(int)$list['rows'][0]['id'];
 inbox_reject(fn()=>snc_notification_read($p,$owner,$notice),404,'owner cannot read or mark another recipient notification');
 inbox_reject(fn()=>snc_notification_list($p,['id'=>$cashier['id'],'role'=>'kitchen']),403,'kitchen role cannot access an inbox');
 inbox_expect(snc_notification_list($p,['id'=>$cashier['id'],'role'=>'admin'])['total']===0,'role changes do not expose old cashier messages under a different role');
 $opened=snc_notification_read($p,$cashier,$notice);snc_notification_read($p,$cashier,$notice);inbox_expect((int)$opened['order_id']===$orderId&&(int)$opened['request_id']===$rid&&snc_notification_list($p,$cashier)['unread']===0,'opening identifies the correct order and marks read idempotently');
 $data['request_key']=bin2hex(random_bytes(16));snc_submit_void_request($p,$cashier,$data);$rid=(int)snc_void_request_latest($p,$orderId)['id'];snc_review_void_request($p,$owner,['request_id'=>$rid,'decision'=>'approved','kitchen_status'=>'pending']);snc_review_void_request($p,$owner,['request_id'=>$rid,'decision'=>'approved','kitchen_status'=>'pending']);$list=snc_notification_list($p,$cashier);inbox_expect($list['total']===2&&$list['unread']===1&&$list['rows'][0]['title']==='Void request approved','approval creates one decision notification without duplicating on retry');$through=$list['through'];
 // Generate later notices for pagination and mark-all boundary checks.
 $q=$p->prepare("INSERT INTO in_app_notifications(recipient_id,recipient_role,request_id,event_type,title,message) VALUES(?,'cashier',?,'void_requested','QA pagination','QA message')");
 for($i=0;$i<22;$i++){$p->prepare('INSERT INTO order_void_requests(order_id,requester_id,requester_name,reason,status,request_key) VALUES(?,?,?,?,?,?)')->execute([$orderId,$cashier['id'],$tag,'QA history','rejected',bin2hex(random_bytes(16))]);$q->execute([$cashier['id'],$p->lastInsertId()]);}
 snc_notification_read($p,$cashier,$through,true);$list=snc_notification_list($p,$cashier,true);inbox_expect($list['unread']===22&&count($list['rows'])===20&&$list['pages']===2,'mark-all covers the displayed boundary and preserves later notifications');
 inbox_expect(count(snc_notification_list($p,$cashier,true,2)['rows'])===2,'second notification page has the remaining records');
 snc_notification_read($p,$cashier,$list['through'],true);inbox_expect(snc_notification_list($p,$cashier,true)['total']===0&&snc_notification_list($p,$cashier)['total']===24,'mark-read retains history while clearing the unread filter');
}finally{if($p->inTransaction())$p->rollBack();echo "Notification QA transaction rolled back.\n";}
