<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/checkout.php';require __DIR__.'/../includes/recipe.php';require __DIR__.'/../includes/order-void.php';require __DIR__.'/../includes/sales-report.php';
$p=snc_get_db();$tag='QA void '.bin2hex(random_bytes(6));$item=0;$ingredient=0;
function void_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: {$label}\n";}
function void_reject(callable $op,int $code,string $label): void {try{$op();}catch(InvalidArgumentException $e){if($code!==422)throw $e;echo "PASS: {$label}\n";return;}catch(RuntimeException $e){if($e instanceof PDOException||$e->getCode()!==$code)throw $e;echo "PASS: {$label}\n";return;}throw new LogicException($label);}
try {
    $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();$cashier=$p->query("SELECT id,full_name,role FROM users WHERE role='cashier' AND is_active=1 LIMIT 1")->fetch();
    $p->prepare('INSERT INTO inventory_ingredients(name,base_unit,current_stock,is_active) VALUES(?,?,1000,1)')->execute([$tag.' ingredient','ml']);$ingredient=(int)$p->lastInsertId();
    $p->prepare('INSERT INTO menu_items(name,category,price,is_available) VALUES(?,?,100,1)')->execute([$tag.' item','QA']);$item=(int)$p->lastInsertId();snc_recipe_save($p,$owner,$item,0,[(string)$ingredient],['100'],'recipe');
    $payload=['request_key'=>bin2hex(random_bytes(16)),'items'=>[['menu_item_id'=>$item,'quantity'=>1]],'payment_method'=>'gcash','table_or_note'=>$tag];$order=snc_checkout_create($p,$cashier,$payload);$id=$order['id'];
    $filters=['from'=>'','to'=>'','method'=>'','status'=>'','search'=>$tag];$before=snc_sales_report($p,$filters);
    void_expect($before['totals']['paid_sales']==='100.00','paid order initially contributes to sales');
    $request=['order_id'=>(string)$id,'request_key'=>bin2hex(random_bytes(16)),'kitchen_status'=>'pending','reason'=>'QA duplicate order'];
    void_reject(fn()=>snc_void_order($p,$cashier,$request),403,'cashier cannot void orders');
    void_reject(fn()=>snc_void_order($p,$owner,array_replace($request,['reason'=>''])),422,'reason is required');
    void_reject(fn()=>snc_void_order($p,$owner,array_replace($request,['request_key'=>'invalid'])),422,'confirmation request key validated');
    $p->prepare("UPDATE orders SET kitchen_status='preparing' WHERE id=?")->execute([$id]);
    void_reject(fn()=>snc_void_order($p,$owner,$request),409,'stale kitchen progress requires renewed confirmation');
    $request['kitchen_status']='preparing';snc_void_order($p,$owner,$request);snc_void_order($p,$owner,$request);
    $q=$p->prepare('SELECT payment_status,kitchen_status,total_amount FROM orders WHERE id=?');$q->execute([$id]);$saved=$q->fetch();
    void_expect($saved['payment_status']==='voided'&&$saved['kitchen_status']==='preparing'&&$saved['total_amount']==='100.00','void changes payment status while preserving amount and kitchen history');
    $q=$p->prepare('SELECT current_stock FROM inventory_ingredients WHERE id=?');$q->execute([$ingredient]);void_expect($q->fetchColumn()==='900.000','void does not automatically return deducted ingredients');
    $q=$p->prepare('SELECT COUNT(*) FROM payments WHERE order_id=?');$q->execute([$id]);$payments=(int)$q->fetchColumn();$q=$p->prepare('SELECT COUNT(*) FROM order_items WHERE order_id=?');$q->execute([$id]);void_expect($payments===1&&(int)$q->fetchColumn()===1,'payment and item records are preserved');
    $q=$p->prepare("SELECT COUNT(*) FROM audit_events WHERE target_type='order' AND target_id=? AND action='voided'");$q->execute([$id]);void_expect((int)$q->fetchColumn()===1,'repeat confirmation creates one void audit event');
    $record=snc_order_void_record($p,$id);void_expect($record['reason']==='QA duplicate order'&&$record['actor_name']===$owner['full_name']&&$record['kitchen_status']==='preparing','void actor, reason, time and kitchen snapshot are recorded');
    $after=snc_sales_report($p,$filters);void_expect((float)$after['totals']['paid_sales']===0.0&&(int)$after['totals']['paid_orders']===0&&count($after['orders'])===1,'voided order remains in history and is excluded from paid totals');
    $filters['status']='voided';void_expect(count(snc_sales_report($p,$filters)['orders'])===1,'voided transaction filter finds the order');
    $retry=snc_checkout_create($p,$cashier,$payload);void_expect($retry['id']===$id&&$retry['payment_status']==='voided','checkout retry identifies the original voided order without creating or charging again');
    void_reject(fn()=>snc_void_order($p,$owner,array_replace($request,['request_key'=>bin2hex(random_bytes(16))])),409,'a different confirmation cannot overwrite the original void');
    $payload['request_key']=bin2hex(random_bytes(16));$completed=snc_checkout_create($p,$cashier,$payload);
    $p->prepare("UPDATE orders SET kitchen_status='completed',completed_at=NOW() WHERE id=?")->execute([$completed['id']]);
    snc_void_order($p,$owner,['order_id'=>(string)$completed['id'],'request_key'=>bin2hex(random_bytes(16)),'kitchen_status'=>'completed','reason'=>'QA owner correction']);
    $q=$p->prepare('SELECT completed_at FROM orders WHERE id=?');$q->execute([$completed['id']]);void_expect((bool)$q->fetchColumn(),'completed order can be voided by owner without erasing completion time');
    $p->prepare("INSERT INTO orders(order_number,cashier_id,table_or_note,payment_status) VALUES(?,?,?,'unpaid')")->execute(['QA-'.bin2hex(random_bytes(8)),$cashier['id'],$tag]);$unpaid=(int)$p->lastInsertId();
    void_reject(fn()=>snc_void_order($p,$owner,['order_id'=>(string)$unpaid,'request_key'=>bin2hex(random_bytes(16)),'kitchen_status'=>'pending','reason'=>'QA unpaid order']),409,'unpaid orders are not treated as paid voids');
}finally{
    $p->beginTransaction();$q=$p->prepare('SELECT id FROM orders WHERE table_or_note=?');$q->execute([$tag]);
    foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id){foreach(['order_voids','inventory_movements','order_recipe_usage','order_requests'] as $table)$p->prepare("DELETE FROM $table WHERE order_id=?")->execute([$id]);$p->prepare("DELETE FROM audit_events WHERE target_type='order' AND target_id=?")->execute([$id]);$p->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);}
    if($item){$p->prepare("DELETE FROM audit_events WHERE target_type IN ('menu_item','recipe') AND target_id=?")->execute([$item]);$p->prepare('DELETE FROM menu_recipe_ingredients WHERE menu_item_id=?')->execute([$item]);$p->prepare('DELETE FROM menu_recipes WHERE menu_item_id=?')->execute([$item]);$p->prepare('DELETE FROM menu_items WHERE id=? AND name=?')->execute([$item,$tag.' item']);}
    if($ingredient)$p->prepare('DELETE FROM inventory_ingredients WHERE id=? AND name=?')->execute([$ingredient,$tag.' ingredient']);$p->commit();echo "Void QA records removed.\n";
}
