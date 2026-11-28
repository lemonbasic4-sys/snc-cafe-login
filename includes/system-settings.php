<?php
declare(strict_types=1);
require_once __DIR__.'/audit.php';
require_once __DIR__.'/array-utils.php';
function snc_settings_defaults(): array
{
    return ['business_name'=>'SNC 2018','business_address'=>'','contact_number'=>'','receipt_header'=>'CAFÉ & RESTO','receipt_footer'=>'Thank you for dining with us!','paper_width'=>'80','cash_enabled'=>1,'gcash_enabled'=>1,'card_enabled'=>1,'revision'=>1];
}
function snc_settings_get(PDO $pdo,bool $lock=false): array
{
    if($lock&&!$pdo->inTransaction())throw new LogicException('Lock settings inside the checkout transaction.');
    $row=$pdo->query('SELECT * FROM system_settings WHERE id=1'.($lock?' LOCK IN SHARE MODE':''))->fetch();if(!$row)throw new RuntimeException('System settings are unavailable.');return $row;
}
function snc_settings_methods(array $settings): array
{
    $methods=[];foreach(['cash'=>'Cash','gcash'=>'GCash','card'=>'Card'] as $value=>$label)if((int)$settings[$value.'_enabled'])$methods[$value]=$label;return $methods;
}
function snc_receipt_profile(array $settings): array
{
    return array_intersect_key($settings,array_flip(['business_name','business_address','contact_number','receipt_header','receipt_footer']));
}
function snc_settings_validate(array $input): array
{
    $result=[];
    foreach(['business_name'=>120,'business_address'=>240,'contact_number'=>80,'receipt_header'=>240,'receipt_footer'=>240] as $key=>$max){
        $value=$input[$key]??null;
        if(!is_string($value)||mb_strlen(trim($value))>$max||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u',$value))throw new InvalidArgumentException('Enter valid business details and receipt messages within the displayed limits.');
        $value=str_replace(["\r\n","\r"],"\n",trim($value));
        if(in_array($key,['business_name','contact_number'],true)&&preg_match('/[\n\t]/',$value))throw new InvalidArgumentException('Business name and contact number must each use one line.');
        if($key==='business_name'&&$value==='')throw new InvalidArgumentException('Business name is required.');$result[$key]=$value;
    }
    if(!in_array($input['paper_width']??null,['58','80'],true))throw new InvalidArgumentException('Choose 58 mm or 80 mm paper.');$result['paper_width']=$input['paper_width'];
    $methods=$input['methods']??[];if(!is_array($methods)||!snc_is_list($methods)||!count($methods)||count($methods)>3)throw new InvalidArgumentException('Keep at least one payment method enabled.');
    foreach($methods as $method)if(!is_string($method)||!in_array($method,['cash','gcash','card'],true))throw new InvalidArgumentException('Choose valid payment methods.');
    foreach(['cash','gcash','card'] as $method)$result[$method.'_enabled']=in_array($method,$methods,true)?1:0;return $result;
}
function snc_settings_save(PDO $pdo,array $user,array $input): array
{
    if(($user['role']??'')!=='admin')throw new RuntimeException('Only the owner can update settings.',403);
    $values=snc_settings_validate($input);$revision=filter_var($input['revision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$revision)throw new InvalidArgumentException('Refresh settings before saving.');
    $owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT id,full_name,role,is_active FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']]);$actor=$q->fetch();if(!$actor||$actor['role']!=='admin'||!(int)$actor['is_active'])throw new RuntimeException('Only an active owner can update settings.',403);
        $before=$pdo->query('SELECT * FROM system_settings WHERE id=1 FOR UPDATE')->fetch();if(!$before)throw new RuntimeException('Settings are unavailable.');
        if((int)$before['revision']!==$revision)throw new RuntimeException('Settings changed in another session. Refresh and review the latest values before saving.',409);
        $old=array_intersect_key($before,$values);$changed=false;foreach($values as $key=>$value)if((string)$old[$key]!== (string)$value)$changed=true;
        if($changed){
            $sets=implode(',',array_map(static fn($key)=>$key.'=?',array_keys($values)));$pdo->prepare('UPDATE system_settings SET '.$sets.',revision=revision+1 WHERE id=1')->execute(array_values($values));
            snc_audit_write($pdo,$actor,'settings','updated','settings',1,'System Settings',$old,$values);
        }
        if($owns)$pdo->commit();return ['ok'=>true,'message'=>$changed?'System settings saved.':'No settings changed.'];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
