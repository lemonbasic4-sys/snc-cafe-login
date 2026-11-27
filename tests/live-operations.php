<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/live-operations.php';
$p=snc_get_db();$p->beginTransaction();$tag='QA live '.bin2hex(random_bytes(5));
function live_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: $label\n";}
function live_bad(array $data,string $label): void {try{snc_operations_filters($data);}catch(InvalidArgumentException $e){echo "PASS: $label\n";return;}throw new LogicException($label);}
function live_order(PDO $p,string $tag,string $paid,string $kitchen,string $type='dine_in'): int {
 $p->prepare('INSERT INTO orders(order_number,table_or_note,payment_status,kitchen_status,order_type,created_at) VALUES(?,?,?,?,?,?)')->execute(['QA-L-'.bin2hex(random_bytes(8)),$tag,$paid,$kitchen,$type,'2040-01-01 10:00:00']);return (int)$p->lastInsertId();
}
try{
 live_bad(['status'=>'completed'],'completed is not an active status filter');live_bad(['type'=>['takeout']],'array filter rejected');live_bad(['page'=>'-1'],'invalid page rejected');
 $base=snc_operations_report($p,[]);$a=live_order($p,$tag,'paid','pending');$b=live_order($p,$tag,'paid','preparing','takeout');$c=live_order($p,$tag,'paid','ready');
 foreach([['unpaid','pending'],['voided','preparing'],['paid','completed']] as [$paid,$kitchen])live_order($p,$tag,$paid,$kitchen);
 $p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,10,3,30)')->execute([$a,'QA recorded item']);
 $p->prepare("INSERT INTO payments(order_id,method,amount_tendered,paid_at) VALUES(?,'cash',30,'2040-01-01 10:00:00'),(?,'card',30,'2040-01-01 10:01:00')")->execute([$a,$a]);
 $p->prepare('INSERT INTO order_void_requests(order_id,requester_name,reason,request_key) VALUES(?,?,?,?)')->execute([$a,'QA cashier','QA pending request',bin2hex(random_bytes(16))]);
 $r=snc_operations_report($p,[]);live_expect($r['total']===$base['total']+3,'only paid pending, preparing and ready orders enter the active queue');
 foreach(['pending','preparing','ready'] as $status)live_expect($r['counts'][$status]===$base['counts'][$status]+1,$status.' global count is accurate');
 $filtered=snc_operations_report($p,['status'=>'preparing','type'=>'takeout']);$beforeFiltered=snc_operations_report($p,['status'=>'ready','type'=>'takeout']);live_expect($filtered['counts']===$r['counts']&&$beforeFiltered['counts']===$r['counts'],'summary counts remain global regardless of filters');
 $byId=array_column($r['orders'],null,'id');live_expect(isset($byId[$a])&&$byId[$a]['void_request_status']==='pending','pending void request leaves order visible with its status');
 live_expect($byId[$a]['payment_method']==='cash'&&$byId[$a]['items'][0]['quantity']===3,'first payment and recorded item quantities appear without duplicate orders');
 live_expect((int)$byId[$a]['age_seconds']===0,'future timestamps clamp elapsed age at zero');
 live_expect(isset(array_column($filtered['orders'],null,'id')[$b])&&!isset(array_column($filtered['orders'],null,'id')[$a]),'status and type filters combine');
 for($i=0;$i<26;$i++)live_order($p,$tag,'paid','pending');$paged=snc_operations_report($p,[]);$last=snc_operations_report($p,['page'=>999]);live_expect(count($paged['orders'])===25&&$paged['total']===$base['total']+29&&$last['page']===$last['pages'],'pagination covers the full queue and clamps stale page numbers');
 live_expect(snc_operations_age(59)==='Just received'&&snc_operations_age(3660)==='1 hr 1 min ago'&&snc_operations_age(90000)==='1 day 1 hr ago','received age uses neutral elapsed time without delay thresholds');
 $p->prepare("UPDATE orders SET payment_status='voided' WHERE id=?")->execute([$a]);$after=snc_operations_report($p,[]);live_expect($after['total']===$paged['total']-1,'voided orders leave the active queue on refresh');
}finally{if($p->inTransaction())$p->rollBack();echo "Live Operations fixtures rolled back.\n";}
