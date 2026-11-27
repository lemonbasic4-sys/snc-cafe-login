<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/staff.php';
$p=snc_get_db();$p->beginTransaction();$tag='qa_staff_'.bin2hex(random_bytes(6));
function staff_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: {$label}\n";}
function staff_reject(callable $op,string $label): void {try{$op();}catch(InvalidArgumentException|RuntimeException $e){echo "PASS: {$label}\n";return;}throw new LogicException($label);}
try {
    $owner=$p->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();if(!$owner)throw new RuntimeException('An active owner is required.');
    $password='Qa!'.bin2hex(random_bytes(10));
    $create=['action'=>'save_staff','id'=>'0','revision'=>'0','full_name'=>'QA Staff Test','username'=>$tag,'role'=>'cashier','is_active'=>'1','password'=>$password,'password_confirmation'=>$password];
    snc_staff_mutate($p,$owner,$create);$q=$p->prepare('SELECT * FROM users WHERE username=?');$q->execute([$tag]);$staff=$q->fetch();$id=(int)$staff['id'];
    staff_expect(password_verify($password,$staff['password_hash'])&&$staff['password_hash']!==$password,'initial password is hashed and can be verified');
    staff_expect($staff['role']==='cashier'&&(int)$staff['auth_version']===1,'new staff role and session version');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,$create),'duplicate usernames rejected');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,array_replace($create,['username'=>$tag.'_a','role'=>'admin'])),'owner role cannot be created');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,array_replace($create,['username'=>$tag.'_b','password_confirmation'=>'different'])),'password mismatch rejected');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,array_replace($create,['username'=>$tag.'_c','password'=>'short','password_confirmation'=>'short'])),'short password rejected');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,array_replace($create,['username'=>'invalid username'])),'invalid username rejected');
    staff_reject(fn()=>snc_staff_mutate($p,['id'=>$id,'role'=>'cashier'],$create),'staff cannot manage other accounts');
    $edit=['action'=>'save_staff','id'=>(string)$id,'revision'=>'0','full_name'=>'QA Staff Renamed','username'=>$tag,'role'=>'cashier','is_active'=>'1'];
    snc_staff_mutate($p,$owner,$edit);$q->execute([$tag]);$renamed=$q->fetch();
    staff_expect((int)$renamed['staff_revision']===1&&(int)$renamed['auth_version']===1&&$renamed['password_hash']===$staff['password_hash'],'name edit preserves password and access while advancing edit revision');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,$edit),'stale edits rejected');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,array_replace($edit,['revision'=>'1','password'=>$password])),'edit cannot replace a password');
    $edit['revision']='1';$edit['role']='kitchen';snc_staff_mutate($p,$owner,$edit);$q->execute([$tag]);$changed=$q->fetch();
    staff_expect($changed['role']==='kitchen'&&(int)$changed['auth_version']===2,'role change revokes prior session version');
    snc_staff_mutate($p,$owner,['action'=>'status','id'=>(string)$id,'revision'=>'2','is_active'=>'0']);
    $q->execute([$tag]);$inactive=$q->fetch();staff_expect(!(int)$inactive['is_active']&&(int)$inactive['auth_version']===3,'deactivation revokes existing access');
    snc_staff_mutate($p,$owner,['action'=>'status','id'=>(string)$id,'revision'=>'3','is_active'=>'1']);
    $q->execute([$tag]);$active=$q->fetch();staff_expect((int)$active['is_active']===1&&(int)$active['auth_version']===4,'reactivation does not revive old sessions');
    staff_reject(fn()=>snc_staff_mutate($p,$owner,['action'=>'status','id'=>(string)$owner['id'],'revision'=>'0','is_active'=>'0']),'owner accounts are protected');
    $q=$p->prepare('SELECT * FROM staff_management_logs WHERE staff_id=? ORDER BY id');$q->execute([$id]);$logs=$q->fetchAll();
    $serialized=json_encode($logs);
    staff_expect(count($logs)===5&&!str_contains($serialized,$password)&&!str_contains($serialized,$staff['password_hash']),'account changes are audited without passwords or password hashes');
    staff_expect($active['last_login_at']===null,'new staff has no invented sign-in history');
} finally {$p->rollBack();echo "Staff QA records rolled back.\n";}
