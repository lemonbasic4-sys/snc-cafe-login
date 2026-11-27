<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/audit.php';require __DIR__.'/../includes/recipe.php';require __DIR__.'/../includes/staff.php';
$p=snc_get_db();$tag='QA audit '.bin2hex(random_bytes(5));
function audit_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: {$label}\n";}
function audit_reject(callable $op,string $label): void {try{$op();}catch(InvalidArgumentException|LogicException $e){echo "PASS: {$label}\n";return;}throw new RuntimeException($label);}
$p->beginTransaction();
try {
    $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();
    for($i=0;$i<27;$i++)snc_audit_write($p,$owner,'menu','item_updated','menu_item',0,$tag,['name'=>$tag,'price'=>(string)$i],['name'=>$tag,'price'=>(string)($i+1),'password'=>'DO NOT LOG','password_hash'=>'DO NOT LOG']);
    $id=(int)$p->lastInsertId();$detail=snc_audit_detail($p,'change',$id);
    audit_expect($detail['activity']==='Menu item updated'&&count($detail['changes'])===2,'menu before/after details and friendly activity labels');
    $q=$p->prepare('SELECT after_details FROM audit_events WHERE id=?');$q->execute([$id]);$raw=$q->fetchColumn();audit_expect(!str_contains($raw,'password')&&!str_contains($raw,'DO NOT LOG'),'sensitive fields excluded from audit snapshots');
    snc_audit_write($p,$owner,'menu','item_updated','menu_item',0,$tag,['name'=>$tag,'price'=>'1.00'],['price'=>'1.00','name'=>$tag]);
    $r=snc_audit_report($p,['search'=>$tag]);audit_expect($r['total']===27&&count($r['rows'])===25&&$r['pages']===2,'count and pagination cover all matches; no-op changes not logged');
    $r=snc_audit_report($p,['search'=>$tag],2);audit_expect(count($r['rows'])===2,'second page contains remaining records');
    $today=$p->query('SELECT CURRENT_DATE')->fetchColumn();$r=snc_audit_report($p,['search'=>$tag,'user'=>(string)$owner['id'],'from'=>$today,'to'=>$today,'activity'=>'menu:item_updated']);audit_expect($r['total']===27,'inclusive date, user, search and activity filters combine');
    audit_reject(fn()=>snc_audit_report($p,['from'=>'2026-02-30']),'invalid dates rejected');
    audit_reject(fn()=>snc_audit_report($p,['from'=>'2026-11-28','to'=>'2026-11-27']),'reversed dates rejected');
    audit_reject(fn()=>snc_audit_report($p,['activity'=>'menu:delete_everything']),'unknown activity types rejected');
    audit_expect(snc_audit_detail($p,'change',999999999)===null,'missing record returns no detail');
    $p->prepare('INSERT INTO inventory_ingredients(name,base_unit,current_stock,is_active) VALUES(?,?,100,1)')->execute([$tag.' ingredient','ml']);$ingredient=(int)$p->lastInsertId();
    $p->prepare('INSERT INTO menu_items(name,category,price,is_available) VALUES(?,?,10,0)')->execute([$tag.' menu','QA']);$item=(int)$p->lastInsertId();
    snc_recipe_save($p,$owner,$item,0,[(string)$ingredient],['25'],'manual');
    $r=snc_audit_report($p,['search'=>$tag,'activity'=>'recipe:saved']);audit_expect($r['total']===1,'recipe changes appear in the combined audit trail');
    $record=snc_audit_detail($p,$r['rows'][0]['source'],(int)$r['rows'][0]['event_id']);audit_expect(str_contains(json_encode($record),$tag.' ingredient'),'recipe ingredient names, quantities and units are retained');
    snc_inventory_mutate($p,$owner,['action'=>'save_ingredient','id'=>(string)$ingredient,'request_key'=>bin2hex(random_bytes(16)),'name'=>$tag.' ingredient','low_stock_threshold'=>'20','is_active'=>'1']);
    $r=snc_audit_report($p,['search'=>$tag,'activity'=>'inventory:updated']);audit_expect($r['total']===1,'ingredient metadata edits are audited separately from stock changes');
    $movement=['action'=>'movement','id'=>(string)$ingredient,'request_key'=>bin2hex(random_bytes(16)),'movement_type'=>'delivery','entry_unit'=>'ml','quantity'=>'50','reason'=>$tag];
    snc_inventory_mutate($p,$owner,$movement);snc_inventory_mutate($p,$owner,$movement);
    $r=snc_audit_report($p,['search'=>$tag,'activity'=>'inventory:delivery']);audit_expect($r['total']===1&&$r['rows'][0]['source']==='stock','existing stock ledger appears once, including retries');
    $username='qa_audit_'.bin2hex(random_bytes(5));$password='Qa!'.bin2hex(random_bytes(8));
    snc_staff_mutate($p,$owner,['action'=>'save_staff','id'=>'0','revision'=>'0','full_name'=>$tag.' staff','username'=>$username,'role'=>'cashier','is_active'=>'0','password'=>$password,'password_confirmation'=>$password]);
    $r=snc_audit_report($p,['search'=>$tag,'activity'=>'staff:created']);audit_expect($r['total']===1&&$r['rows'][0]['source']==='staff','existing staff audit records are included without copying');
    $p->prepare("INSERT INTO auth_audit_logs(username_attempted,event_type) VALUES(?,'login_failed')")->execute([$tag]);
    $r=snc_audit_report($p,['search'=>$tag,'user'=>'unknown','activity'=>'auth:login_failed']);$row=$r['rows'][0];
    audit_expect($r['total']===1&&str_starts_with(snc_audit_actor($row),'Attempted: '),'failed sign-ins distinguish attempted identities from authenticated users');
}finally{$p->rollBack();}
audit_expect(snc_audit_report($p,['search'=>$tag])['total']===0,'rolled-back changes leave no audit events or QA data');
audit_reject(fn()=>snc_audit_write($p,['id'=>1,'full_name'=>'QA'],'menu','item_created','menu_item',0,$tag,null,['name'=>$tag]),'audit writes require a change transaction');
