<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/system-settings.php';require __DIR__.'/../includes/checkout.php';
$p=snc_get_db();$tag='QA settings '.bin2hex(random_bytes(5));$item=0;$temporary=false;$original=snc_settings_get($p);
function settings_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: $label\n";}
function settings_reject(callable $op,int $code,string $label): void {try{$op();}catch(InvalidArgumentException $e){if($code!==422)throw $e;echo "PASS: $label\n";return;}catch(RuntimeException $e){if($e instanceof PDOException||$e->getCode()!==$code)throw $e;echo "PASS: $label\n";return;}throw new LogicException($label);}
function settings_input(array $row): array {return snc_receipt_profile($row)+['revision'=>$row['revision'],'paper_width'=>$row['paper_width'],'methods'=>array_keys(snc_settings_methods($row))];}
try{
 $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();$cashier=$p->query("SELECT id,full_name,role FROM users WHERE role='cashier' AND is_active=1 LIMIT 1")->fetch();
 $p->beginTransaction();$input=settings_input($original);
 settings_reject(fn()=>snc_settings_save($p,$cashier,$input),403,'cashier cannot save settings');
 settings_reject(fn()=>snc_settings_save($p,$owner,array_replace($input,['methods'=>[]])),422,'at least one payment method is required');
 settings_reject(fn()=>snc_settings_save($p,$owner,array_replace($input,['paper_width'=>'100'])),422,'unsupported paper width rejected');
 settings_reject(fn()=>snc_settings_save($p,$owner,array_replace($input,['business_name'=>''])),422,'business name is required');
 settings_reject(fn()=>snc_settings_save($p,$owner,array_replace($input,['contact_number'=>"123\n456"])),422,'contact number cannot contain newlines');
 settings_reject(fn()=>snc_settings_save($p,$owner,array_replace($input,['receipt_footer'=>str_repeat('x',241)])),422,'receipt text limits enforced');
 $input['business_name']=$tag;$input['paper_width']='58';snc_settings_save($p,$owner,$input);$saved=snc_settings_get($p);settings_expect((int)$saved['revision']===(int)$original['revision']+1&&$saved['business_name']===$tag,'validated settings save increments the edit revision');
 settings_reject(fn()=>snc_settings_save($p,$owner,$input),409,'stale revision cannot overwrite settings');
 $q=$p->prepare("SELECT COUNT(*) FROM audit_events WHERE module='settings' AND LOCATE(?,after_details)>0");$q->execute([$tag]);$count=(int)$q->fetchColumn();snc_settings_save($p,$owner,settings_input($saved));$q->execute([$tag]);settings_expect((int)$q->fetchColumn()===$count,'unchanged settings do not create duplicate audit events');
 $q=$p->prepare("SELECT id FROM audit_events WHERE module='settings' AND LOCATE(?,after_details)>0 ORDER BY id DESC LIMIT 1");$q->execute([$tag]);$audit=snc_audit_detail($p,'change',(int)$q->fetchColumn());settings_expect($audit['activity']==='System settings updated'&&count($audit['changes'])>=6,'settings changes are readable through Audit Trail');$p->rollBack();
 // A connection-local table shadows production settings during committed checkout tests.
 $ddl=$p->query('SHOW CREATE TABLE system_settings')->fetch(PDO::FETCH_NUM)[1];$p->exec(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl));$temporary=true;$p->exec('INSERT INTO system_settings(id) VALUES(1)');
 $input=settings_input(snc_settings_get($p));$input['business_name']=$tag;$input['receipt_footer']='QA initial footer';$input['methods']=['cash'];snc_settings_save($p,$owner,$input);
 $p->prepare('INSERT INTO menu_items(name,category,price,is_available) VALUES(?,?,10,1)')->execute([$tag.' item','QA']);$item=(int)$p->lastInsertId();
 $payload=['request_key'=>bin2hex(random_bytes(16)),'items'=>[['menu_item_id'=>$item,'quantity'=>1]],'payment_method'=>'cash','amount_tendered'=>'20.00','table_or_note'=>$tag];$order=snc_checkout_create($p,$cashier,$payload);
 $q=$p->prepare('SELECT receipt_profile FROM orders WHERE id=?');$q->execute([$order['id']]);$snapshot=json_decode($q->fetchColumn(),true);settings_expect($snapshot['business_name']===$tag&&$snapshot['receipt_footer']==='QA initial footer','checkout records business and receipt text with the order');
 $input=settings_input(snc_settings_get($p));$input['methods']=['gcash'];$input['receipt_footer']='QA changed footer';snc_settings_save($p,$owner,$input);
 $retry=snc_checkout_create($p,$cashier,$payload);settings_expect($retry['id']===$order['id'],'idempotent checkout still returns its paid order after its method is disabled');
 settings_reject(fn()=>snc_checkout_create($p,$cashier,array_replace($payload,['request_key'=>bin2hex(random_bytes(16))])),409,'new checkout rejects disabled payment methods');
 $q->execute([$order['id']]);settings_expect(json_decode($q->fetchColumn(),true)===$snapshot,'later settings changes do not alter original receipt text');
 $payload['request_key']=bin2hex(random_bytes(16));$payload['payment_method']='gcash';$next=snc_checkout_create($p,$cashier,$payload);$q->execute([$next['id']]);settings_expect(json_decode($q->fetchColumn(),true)['receipt_footer']==='QA changed footer','new orders use the updated receipt text and enabled method');
}finally{
 if($p->inTransaction())$p->rollBack();$p->beginTransaction();$q=$p->prepare('SELECT id FROM orders WHERE table_or_note=?');$q->execute([$tag]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id){foreach(['order_void_requests','order_voids','inventory_movements','order_recipe_usage','order_requests'] as $table)$p->prepare("DELETE FROM $table WHERE order_id=?")->execute([$id]);$p->prepare("DELETE FROM audit_events WHERE target_type='order' AND target_id=?")->execute([$id]);$p->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);}
 $p->prepare("DELETE FROM audit_events WHERE module='settings' AND LOCATE(?,after_details)>0")->execute([$tag]);if($item)$p->prepare('DELETE FROM menu_items WHERE id=? AND name=?')->execute([$item,$tag.' item']);$p->commit();if($temporary)$p->exec('DROP TEMPORARY TABLE system_settings');echo "Disposable settings fixtures removed; real settings preserved.\n";
}
