<?php
declare(strict_types=1);

const SNC_AUDIT_ACTIVITIES=[
    'auth'=>['label'=>'Authentication','actions'=>['login_success'=>'Signed in','login_failed'=>'Failed sign-in','portal_mismatch'=>'Wrong login portal','inactive_account'=>'Inactive account sign-in','logout'=>'Signed out']],
    'staff'=>['label'=>'Staff','actions'=>['created'=>'Staff account created','updated'=>'Staff account updated','activated'=>'Staff account activated','deactivated'=>'Staff account deactivated']],
    'menu'=>['label'=>'Menu','actions'=>['item_created'=>'Menu item created','item_updated'=>'Menu item updated','availability_changed'=>'Availability changed','category_created'=>'Category created','category_renamed'=>'Category renamed']],
    'recipe'=>['label'=>'Recipes','actions'=>['saved'=>'Recipe saved','cleared'=>'Recipe cleared']],
    'inventory'=>['label'=>'Inventory','actions'=>['created'=>'Ingredient created','updated'=>'Ingredient details updated','opening'=>'Opening stock recorded','delivery'=>'Stock received','usage'=>'Stock used','waste'=>'Waste / spoilage recorded','correction_in'=>'Stock correction — increase','correction_out'=>'Stock correction — decrease','sale'=>'Ingredients deducted for sale']],
    'order'=>['label'=>'Orders','actions'=>['paid'=>'Paid order recorded','voided'=>'Order voided','void_requested'=>'Void requested','void_approved'=>'Void request approved','void_rejected'=>'Void request rejected']],
    'settings'=>['label'=>'Settings','actions'=>['updated'=>'System settings updated']],
    'kitchen'=>['label'=>'Kitchen','actions'=>['status_changed'=>'Kitchen status changed']],
];
const SNC_AUDIT_FIELDS=['name','full_name','username','role','is_active','category','price','is_available','stock_tracking','ingredients','base_unit','low_stock_threshold','stock','unit','quantity_delta','reason','order_number','total_amount','payment_method','items_count','kitchen_status','affected_items','portal','ip_address','payment_status','request_status','business_name','business_address','contact_number','receipt_header','receipt_footer','paper_width','cash_enabled','gcash_enabled','card_enabled'];

function snc_audit_snapshot(?array $details): ?array
{
    return $details===null?null:array_intersect_key($details,array_flip(SNC_AUDIT_FIELDS));
}
function snc_audit_write(PDO $pdo,array $actor,string $module,string $action,string $targetType,int $targetId,string $targetName,?array $before,?array $after): void
{
    if(!isset(SNC_AUDIT_ACTIVITIES[$module]['actions'][$action])||!in_array($module,['menu','recipe','inventory','order','kitchen','settings'],true))throw new InvalidArgumentException('Invalid audit event.');
    if(!$pdo->inTransaction())throw new LogicException('Record changes and audit events in the same transaction.');
    $before=snc_audit_snapshot($before);$after=snc_audit_snapshot($after);
    if($before!==null)ksort($before);if($after!==null)ksort($after);
    if($before===$after)return;
    $q=$pdo->prepare('INSERT INTO audit_events(module,action,actor_id,actor_name,target_type,target_id,target_name,before_details,after_details) VALUES(?,?,?,?,?,?,?,?,?)');
    $q->execute([$module,$action,$actor['id'],mb_substr((string)$actor['full_name'],0,120),$targetType,$targetId,mb_substr($targetName,0,160),$before===null?null:json_encode($before,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$after===null?null:json_encode($after,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
}
function snc_audit_union(): string
{
    return "SELECT 'auth' AS source,id AS event_id,created_at AS occurred_at,'auth' AS module,event_type AS action,user_id AS actor_id,username_attempted AS actor_name,'account' AS target_type,user_id AS target_id,username_attempted AS target_name,NULL AS before_details,JSON_OBJECT('username',username_attempted,'portal',portal_selected,'ip_address',ip_address) AS after_details,NULL AS order_id FROM auth_audit_logs
    UNION ALL SELECT 'staff',id,created_at,'staff',event_type,actor_id,actor_name,'staff',staff_id,staff_name,before_details,after_details,NULL FROM staff_management_logs
    UNION ALL SELECT 'stock',id,created_at,'inventory',movement_type,actor_id,actor_name,'ingredient',ingredient_id,ingredient_name,JSON_OBJECT('stock',balance_before,'unit',base_unit),JSON_OBJECT('stock',balance_after,'unit',base_unit,'quantity_delta',quantity_delta,'reason',reason),order_id FROM inventory_movements
    UNION ALL SELECT 'change',id,created_at,module,action,actor_id,actor_name,target_type,target_id,target_name,before_details,after_details,CASE WHEN target_type='order' THEN target_id ELSE NULL END FROM audit_events";
}
function snc_audit_filters(array $input): array
{
    $filters=[];
    foreach(['search','from','to','user','activity'] as $key){
        if(isset($input[$key])&&!is_string($input[$key]))throw new InvalidArgumentException('Choose valid audit filters.');
        $filters[$key]=trim($input[$key]??'');
    }
    $filters['search']=mb_substr($filters['search'],0,120);
    foreach(['from','to'] as $key)if($filters[$key]!==''){
        $date=preg_match('/^\d{4}-\d{2}-\d{2}$/D',$filters[$key])?DateTimeImmutable::createFromFormat('!Y-m-d',$filters[$key]):false;
        if(!$date||$date->format('Y-m-d')!==$filters[$key])throw new InvalidArgumentException('Choose valid dates.');
    }
    if($filters['from']!==''&&$filters['to']!==''&&$filters['from']>$filters['to'])throw new InvalidArgumentException('The start date must be on or before the end date.');
    if($filters['user']!==''&&$filters['user']!=='unknown'&&!filter_var($filters['user'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]))throw new InvalidArgumentException('Choose a valid user.');
    if($filters['activity']!==''){
        $parts=explode(':',$filters['activity']);
        if(count($parts)!==2||!isset(SNC_AUDIT_ACTIVITIES[$parts[0]]['actions'][$parts[1]]))throw new InvalidArgumentException('Choose a valid activity type.');
    }
    return $filters;
}
function snc_audit_report(PDO $pdo,array $filters,int $page=1): array
{
    $filters=snc_audit_filters($filters);$conditions=[];$params=[];
    if($filters['search']!==''){$conditions[]='(LOCATE(?,actor_name)>0 OR LOCATE(?,target_name)>0)';$params[]=$filters['search'];$params[]=$filters['search'];}
    if($filters['from']!==''){$conditions[]='occurred_at>=?';$params[]=$filters['from'];}
    if($filters['to']!==''){$conditions[]='occurred_at<DATE_ADD(?,INTERVAL 1 DAY)';$params[]=$filters['to'];}
    if($filters['user']==='unknown')$conditions[]='actor_id IS NULL';elseif($filters['user']!==''){$conditions[]='actor_id=?';$params[]=(int)$filters['user'];}
    if($filters['activity']!==''){[$module,$action]=explode(':',$filters['activity']);$conditions[]='module=? AND action=?';$params[]=$module;$params[]=$action;}
    $where=$conditions?' WHERE '.implode(' AND ',$conditions):'';$union=snc_audit_union();
    $q=$pdo->prepare("SELECT COUNT(*) FROM ({$union}) e{$where}");$q->execute($params);$total=(int)$q->fetchColumn();$pages=max(1,(int)ceil($total/25));$page=max(1,min($page,$pages));$offset=($page-1)*25;
    $q=$pdo->prepare("SELECT source,event_id,occurred_at,module,action,actor_id,actor_name,target_type,target_id,target_name,order_id FROM ({$union}) e{$where} ORDER BY occurred_at DESC,source DESC,event_id DESC LIMIT 25 OFFSET {$offset}");$q->execute($params);
    return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
}
function snc_audit_detail(PDO $pdo,string $source,int $id): ?array
{
    if(!in_array($source,['auth','staff','stock','change'],true)||$id<1)throw new InvalidArgumentException('Choose a valid audit record.');
    $union=snc_audit_union();$q=$pdo->prepare("SELECT * FROM ({$union}) e WHERE source=? AND event_id=?");$q->execute([$source,$id]);$row=$q->fetch();if(!$row)return null;
    $before=snc_audit_snapshot(json_decode($row['before_details']??'null',true));$after=snc_audit_snapshot(json_decode($row['after_details']??'null',true));$changes=[];
    foreach(array_unique(array_merge(array_keys($before??[]),array_keys($after??[]))) as $key)$changes[]=['field'=>snc_audit_field_label($key),'before'=>snc_audit_value($key,$before[$key]??null),'after'=>snc_audit_value($key,$after[$key]??null)];
    $row['changes']=$changes;unset($row['before_details'],$row['after_details']);$row['activity']=snc_audit_activity($row);$row['actor']=snc_audit_actor($row);return $row;
}
function snc_audit_activity(array $row): string {return SNC_AUDIT_ACTIVITIES[$row['module']]['actions'][$row['action']]??'Recorded activity';}
function snc_audit_actor(array $row): string
{
    $name=trim((string)$row['actor_name'])?:'Unknown account';
    return $row['module']==='auth'&&!in_array($row['action'],['login_success','logout'],true)?'Attempted: '.$name:$name;
}
function snc_audit_field_label(string $key): string
{
    return ['full_name'=>'Staff name','is_active'=>'Account / ingredient status','is_available'=>'Menu availability','stock_tracking'=>'Stock tracking','base_unit'=>'Base unit','low_stock_threshold'=>'Low-stock threshold','stock'=>'Stock balance','quantity_delta'=>'Quantity change','order_number'=>'Order number','total_amount'=>'Order total','payment_method'=>'Payment method','payment_status'=>'Payment status','items_count'=>'Items sold','kitchen_status'=>'Kitchen status','affected_items'=>'Menu items affected','ip_address'=>'IP address'][$key]??ucfirst(str_replace('_',' ',$key));
}
function snc_audit_value(string $key,mixed $value): string
{
    if($value===null)return '—';
    if(in_array($key,['is_active','is_available'],true))return (int)$value?($key==='is_active'?'Active':'Available'):($key==='is_active'?'Inactive':'Unavailable');
    if(in_array($key,['cash_enabled','gcash_enabled','card_enabled'],true))return (int)$value?'Enabled':'Disabled';
    if($key==='paper_width')return $value.' mm';
    if($key==='stock_tracking')return $value==='recipe'?'Deduct using recipe':'Manual tracking';
    if($key==='ingredients'&&is_array($value)){
        $lines=[];foreach($value as $part)if(is_array($part))$lines[]=(string)($part['name']??'Ingredient').' · '.(string)($part['quantity']??'').' '.(string)($part['unit']??'');
        return $lines?implode("\n",$lines):'No ingredients';
    }
    if(!is_scalar($value))return '—';
    if(in_array($key,['price','total_amount'],true))return '₱'.number_format((float)$value,2);
    if(in_array($key,['role','portal','kitchen_status','payment_status'],true))return ucfirst((string)$value);
    if($key==='payment_method')return strtoupper((string)$value);
    return (string)$value;
}
