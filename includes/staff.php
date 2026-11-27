<?php
declare(strict_types=1);

function snc_staff_text(mixed $value,int $max,string $label): string
{
    if (!is_string($value)||trim($value)===''||mb_strlen(trim($value))>$max||preg_match('/[\x00-\x1f\x7f]/u',$value)) throw new InvalidArgumentException('Enter a valid '.$label.' (up to '.$max.' characters).');
    return trim($value);
}
function snc_staff_details(array $row): array
{
    return array_intersect_key($row,array_flip(['full_name','username','role','is_active']));
}
/** Changes staff accounts only; keeps orders and all history attached to the same ID. */
function snc_staff_mutate(PDO $pdo,array $actor,array $data): array
{
    if (($actor['role']??'')!=='admin') throw new RuntimeException('Only the owner can manage staff.',403);
    $action=$data['action']??'';
    if (!in_array($action,['save_staff','status'],true)) throw new InvalidArgumentException('Choose a valid staff action.');
    $id=filter_var($data['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    $revision=filter_var($data['revision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    if ($id===false||$revision===false||($action==='status'&&!$id)) throw new InvalidArgumentException('Choose a valid staff account.');
    if (!in_array($data['is_active']??null,['0','1'],true)) throw new InvalidArgumentException('Choose an account status.');
    $after=['is_active'=>(int)$data['is_active']];$passwordHash=null;
    if ($action==='save_staff') {
        $after['full_name']=snc_staff_text($data['full_name']??null,120,'staff name');
        $after['username']=snc_staff_text($data['username']??null,60,'username');
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,59}$/D',$after['username'])) throw new InvalidArgumentException('Use 3–60 letters, numbers, dots, underscores, or hyphens for the username. Start with a letter or number.');
        if (!in_array($data['role']??null,['cashier','kitchen'],true)) throw new InvalidArgumentException('Choose Cashier or Kitchen. Owner accounts cannot be created here.');
        $after['role']=$data['role'];
        if (!$id) {
            $password=$data['password']??null;
            if (!is_string($password)||mb_strlen($password)<8||strlen($password)>72||str_contains($password,"\0")||trim($password)==='') throw new InvalidArgumentException('Use at least 8 characters and no more than 72 bytes for the initial password.');
            if (!is_string($data['password_confirmation']??null)||!hash_equals($password,$data['password_confirmation'])) throw new InvalidArgumentException('The passwords do not match.');
            $passwordHash=password_hash($password,PASSWORD_DEFAULT);
        } elseif (isset($data['password'])||isset($data['password_confirmation'])) throw new InvalidArgumentException('Passwords cannot be changed through Edit Staff.');
    }
    $owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try {
        $q=$pdo->prepare('SELECT id,role,is_active,full_name FROM users WHERE id=? FOR UPDATE');$q->execute([$actor['id']??0]);$owner=$q->fetch();
        if (!$owner||$owner['role']!=='admin'||!(int)$owner['is_active']) throw new RuntimeException('Only an active owner can manage staff.',403);
        $before=null;
        if ($id) {
            $q=$pdo->prepare('SELECT id,full_name,username,role,is_active,staff_revision,auth_version FROM users WHERE id=? FOR UPDATE');$q->execute([$id]);$target=$q->fetch();
            if (!$target) throw new RuntimeException('Staff account no longer exists. Refresh the page.',409);
            if (!in_array($target['role'],['cashier','kitchen'],true)) throw new RuntimeException('Owner accounts cannot be changed through Staff Management.',403);
            if ((int)$target['staff_revision']!==$revision) throw new RuntimeException('This account changed since you opened it. Close the dialog and refresh before editing again.',409);
            $before=snc_staff_details($target);$before['is_active']=(int)$before['is_active'];
            $after=array_replace($before,$after);
            if ($after===$before) {if($owns)$pdo->commit();return ['ok'=>true,'message'=>'No account details changed.'];}
            $revoke=$after['role']!==$target['role']||$after['is_active']!==(int)$target['is_active'];
            $q=$pdo->prepare('UPDATE users SET full_name=?,username=?,role=?,is_active=?,staff_revision=staff_revision+1,auth_version=auth_version+? WHERE id=?');
            $q->execute([$after['full_name'],$after['username'],$after['role'],$after['is_active'],$revoke?1:0,$id]);
            $event=$action==='status'?($after['is_active']?'activated':'deactivated'):'updated';
            $message=$action==='status'?($after['is_active']?'Staff account activated.':'Staff account deactivated. Existing access has been revoked.'):'Staff account updated.';
        } else {
            if ($revision!==0) throw new InvalidArgumentException('Reopen Add Staff before saving.');
            $q=$pdo->prepare('INSERT INTO users(username,full_name,role,password_hash,is_active) VALUES(?,?,?,?,?)');
            $q->execute([$after['username'],$after['full_name'],$after['role'],$passwordHash,$after['is_active']]);$id=(int)$pdo->lastInsertId();$event='created';$message='Staff account created.';
        }
        $pdo->prepare('INSERT INTO staff_management_logs(actor_id,staff_id,actor_name,staff_name,event_type,before_details,after_details) VALUES(?,?,?,?,?,?,?)')->execute([$owner['id'],$id,$owner['full_name'],$after['full_name'],$event,$before?json_encode($before,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE):null,json_encode($after,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        if($owns)$pdo->commit();return ['ok'=>true,'message'=>$message];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
