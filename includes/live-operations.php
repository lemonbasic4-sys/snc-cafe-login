<?php
declare(strict_types=1);
function snc_operations_filters(array $input): array
{
    $status=$input['status']??'all';$type=$input['type']??'all';
    if(!in_array($status,['all','pending','preparing','ready'],true)||!in_array($type,['all','dine_in','takeout'],true))throw new InvalidArgumentException('Choose a valid kitchen status and order type.');
    $page=$input['page']??'1';if(!is_int($page)&&!is_string($page))throw new InvalidArgumentException('Choose a valid page.');
    $page=filter_var($page,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$page)throw new InvalidArgumentException('Choose a valid page.');
    return ['status'=>$status,'type'=>$type,'page'=>$page];
}
function snc_operations_report(PDO $pdo,array $input): array
{
    $filters=snc_operations_filters($input);$owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try{
        $scope="o.payment_status='paid' AND o.kitchen_status IN ('pending','preparing','ready')";$params=[];$filter=$scope;
        $counts=['pending'=>0,'preparing'=>0,'ready'=>0];foreach($pdo->query("SELECT kitchen_status,COUNT(*) AS n FROM orders o WHERE {$scope} GROUP BY kitchen_status") as $row)$counts[$row['kitchen_status']]=(int)$row['n'];
        if($filters['status']!=='all'){$filter.=' AND o.kitchen_status=?';$params[]=$filters['status'];}
        if($filters['type']!=='all'){$filter.=' AND o.order_type=?';$params[]=$filters['type'];}
        $q=$pdo->prepare("SELECT COUNT(*) FROM orders o WHERE {$filter}");$q->execute($params);$total=(int)$q->fetchColumn();$pages=max(1,(int)ceil($total/25));$page=min($filters['page'],$pages);$offset=($page-1)*25;
        $q=$pdo->prepare("SELECT o.id,o.order_number,o.order_type,o.table_or_note,o.kitchen_status,o.created_at,o.payment_status,o.total_amount,
            GREATEST(0,TIMESTAMPDIFF(SECOND,o.created_at,NOW())) AS age_seconds,u.full_name AS cashier_name,p.method AS payment_method,
            r.status AS void_request_status
            FROM orders o LEFT JOIN users u ON u.id=o.cashier_id
            LEFT JOIN payments p ON p.id=(SELECT MIN(p2.id) FROM payments p2 WHERE p2.order_id=o.id)
            LEFT JOIN order_void_requests r ON r.id=(SELECT MAX(r2.id) FROM order_void_requests r2 WHERE r2.order_id=o.id)
            WHERE {$filter} ORDER BY o.created_at ASC,o.id ASC LIMIT 25 OFFSET {$offset}");$q->execute($params);$orders=$q->fetchAll();
        if($orders){$ids=array_column($orders,'id');$q=$pdo->prepare('SELECT order_id,item_name,quantity FROM order_items WHERE order_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY id');$q->execute($ids);$items=[];foreach($q as $item)$items[$item['order_id']][]=['item_name'=>$item['item_name'],'quantity'=>(int)$item['quantity']];foreach($orders as &$order)$order['items']=$items[$order['id']]??[];unset($order);}
        $updated=$pdo->query('SELECT NOW()')->fetchColumn();if($owns)$pdo->commit();
        return ['orders'=>$orders,'counts'=>$counts,'total'=>$total,'page'=>$page,'pages'=>$pages,'updated_at'=>$updated];
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function snc_operations_age(int $seconds): string
{
    $minutes=intdiv(max(0,$seconds),60);if($minutes<1)return 'Just received';if($minutes<60)return $minutes.' min ago';$hours=intdiv($minutes,60);if($hours<24)return $hours.' hr '.($minutes%60).' min ago';return intdiv($hours,24).' day '.($hours%24).' hr ago';
}
