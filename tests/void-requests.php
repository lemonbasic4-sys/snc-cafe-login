<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/checkout.php';require __DIR__.'/../includes/recipe.php';require __DIR__.'/../includes/void-requests.php';require __DIR__.'/../includes/sales-report.php';
$p=snc_get_db();$tag='QA requests '.bin2hex(random_bytes(6));$item=0;$ingredient=0;
function request_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: $label\n";}
function request_reject(callable $op,int $code,string $label): void {try{$op();}catch(InvalidArgumentException $e){if($code!==422)throw $e;echo "PASS: $label\n";return;}catch(RuntimeException $e){if($e instanceof PDOException||$e->getCode()!==$code)throw $e;echo "PASS: $label\n";return;}throw new LogicException($label);}
try{
 $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();$cashier=$p->query("SELECT id,full_name,role FROM users WHERE role='cashier' AND is_active=1 LIMIT 1")->fetch();$kitchen=$p->query("SELECT id,full_name,role FROM users WHERE role='kitchen' AND is_active=1 LIMIT 1")->fetch();
 $p->prepare('INSERT INTO inventory_ingredients(name,base_unit,current_stock,is_active) VALUES(?,?,1000,1)')->execute([$tag.' ingredient','ml']);$ingredient=(int)$p->lastInsertId();
 $p->prepare('INSERT INTO menu_items(name,category,price,is_available) VALUES(?,?,100,1)')->execute([$tag.' item','QA']);$item=(int)$p->lastInsertId();snc_recipe_save($p,$owner,$item,0,[(string)$ingredient],['100'],'recipe');
 $payload=['request_key'=>bin2hex(random_bytes(16)),'items'=>[['menu_item_id'=>$item,'quantity'=>1]],'payment_method'=>'gcash','table_or_note'=>$tag];$order=snc_checkout_create($p,$cashier,$payload);$id=$order['id'];
 $request=['order_id'=>$id,'request_key'=>bin2hex(random_bytes(16)),'reason'=>'QA mistaken duplicate order'];
 request_reject(fn()=>snc_submit_void_request($p,$owner,$request),403,'owner cannot impersonate a cashier request');
 request_reject(fn()=>snc_submit_void_request($p,$kitchen,$request),403,'kitchen cannot request a void');
 request_reject(fn()=>snc_submit_void_request($p,$cashier,array_replace($request,['reason'=>''])),422,'request reason is required');
 snc_submit_void_request($p,$cashier,$request);snc_submit_void_request($p,$cashier,$request);$latest=snc_void_request_latest($p,$id);$rid=(int)$latest['id'];
 $q=$p->prepare('SELECT COUNT(*) FROM order_void_requests WHERE order_id=?');$q->execute([$id]);request_expect((int)$q->fetchColumn()===1,'retry records one request');
 request_reject(fn()=>snc_submit_void_request($p,$cashier,array_replace($request,['request_key'=>bin2hex(random_bytes(16))])),409,'another confirmation cannot duplicate a pending request');
 request_reject(fn()=>snc_submit_void_request($p,$cashier,array_replace($request,['reason'=>'Changed request reason'])),409,'request key cannot be reused for changed details');
 $q=$p->prepare('SELECT payment_status,kitchen_status FROM orders WHERE id=?');$q->execute([$id]);request_expect($q->fetch()===['payment_status'=>'paid','kitchen_status'=>'pending'],'pending request leaves paid order and kitchen queue unchanged');
 $filters=['from'=>'','to'=>'','method'=>'','status'=>'','search'=>$tag];request_expect(snc_sales_report($p,$filters)['totals']['paid_sales']==='100.00','pending request leaves paid sales unchanged');
 $review=['request_id'=>$rid,'decision'=>'rejected','reason'=>'QA order confirmed correct'];
 request_reject(fn()=>snc_review_void_request($p,$cashier,$review),403,'cashier cannot review requests');
 request_reject(fn()=>snc_review_void_request($p,$owner,array_replace($review,['reason'=>''])),422,'rejection reason is required');
 snc_review_void_request($p,$owner,$review);snc_review_void_request($p,$owner,$review);$latest=snc_void_request_latest($p,$id);request_expect($latest['status']==='rejected'&&$latest['decision_reason']===$review['reason'],'rejection retains owner reason and allows safe retry');
 $request['request_key']=bin2hex(random_bytes(16));snc_submit_void_request($p,$cashier,$request);$latest=snc_void_request_latest($p,$id);request_expect((int)$latest['id']!==$rid,'cashier can submit a new request after rejection');
 $approve=['request_id'=>$latest['id'],'decision'=>'approved','kitchen_status'=>'pending'];$p->prepare("UPDATE orders SET kitchen_status='preparing' WHERE id=?")->execute([$id]);
 request_reject(fn()=>snc_review_void_request($p,$owner,$approve),409,'kitchen progress change requires renewed approval');
 request_expect(snc_void_request_latest($p,$id)['status']==='pending','failed approval leaves request pending');
 $approve['kitchen_status']='preparing';snc_review_void_request($p,$owner,$approve);snc_review_void_request($p,$owner,$approve);
 request_expect(snc_void_request_latest($p,$id)['status']==='approved'&&snc_order_void_record($p,$id)['reason']===$request['reason'],'approval records the original request reason and voids once');
 request_expect((float)snc_sales_report($p,$filters)['totals']['paid_sales']===0.0,'approved void is excluded from paid sales');
 $q=$p->prepare('SELECT current_stock FROM inventory_ingredients WHERE id=?');$q->execute([$ingredient]);request_expect($q->fetchColumn()==='900.000','approval never returns consumed ingredients');
 $q=$p->prepare('SELECT COUNT(*) FROM payments WHERE order_id=?');$q->execute([$id]);request_expect((int)$q->fetchColumn()===1,'approval preserves original payment record');
 $q=$p->prepare("SELECT action,COUNT(*) AS n FROM audit_events WHERE target_type='order' AND target_id=? GROUP BY action");$q->execute([$id]);$events=array_column($q->fetchAll(),'n','action');request_expect((int)$events['void_requested']===2&&(int)$events['void_rejected']===1&&(int)$events['void_approved']===1&&(int)$events['voided']===1,'request and decision audit events are not duplicated');
 request_reject(fn()=>snc_review_void_request($p,$owner,array_replace($approve,['decision'=>'rejected','reason'=>'QA late rejection'])),409,'later decision cannot overwrite approval');
 request_reject(fn()=>snc_submit_void_request($p,$cashier,array_replace($request,['request_key'=>bin2hex(random_bytes(16))])),409,'voided order cannot receive a new request');
 $payload['request_key']=bin2hex(random_bytes(16));$second=snc_checkout_create($p,$cashier,$payload);$request['order_id']=$second['id'];$request['request_key']=bin2hex(random_bytes(16));snc_submit_void_request($p,$cashier,$request);
 snc_void_order($p,$owner,['order_id'=>$second['id'],'request_key'=>bin2hex(random_bytes(16)),'reason'=>'QA direct owner void','kitchen_status'=>'pending']);$latest=snc_void_request_latest($p,$second['id']);request_expect($latest['status']==='approved'&&$latest['decision_reason']==='Order voided directly by owner.','direct owner void closes an existing pending request');
}finally{
 if($p->inTransaction())$p->rollBack();$p->beginTransaction();$q=$p->prepare('SELECT id FROM orders WHERE table_or_note=?');$q->execute([$tag]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id){foreach(['order_void_requests','order_voids','inventory_movements','order_recipe_usage','order_requests'] as $table)$p->prepare("DELETE FROM $table WHERE order_id=?")->execute([$id]);$p->prepare("DELETE FROM audit_events WHERE target_type='order' AND target_id=?")->execute([$id]);$p->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);}
 if($item){$p->prepare("DELETE FROM audit_events WHERE target_type IN ('menu_item','recipe') AND target_id=?")->execute([$item]);$p->prepare('DELETE FROM menu_recipe_ingredients WHERE menu_item_id=?')->execute([$item]);$p->prepare('DELETE FROM menu_recipes WHERE menu_item_id=?')->execute([$item]);$p->prepare('DELETE FROM menu_items WHERE id=?')->execute([$item]);}if($ingredient)$p->prepare('DELETE FROM inventory_ingredients WHERE id=?')->execute([$ingredient]);$p->commit();echo "Disposable approval fixtures removed.\n";
}
