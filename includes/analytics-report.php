<?php
declare(strict_types=1);
require_once __DIR__.'/dashboard-report.php';
function snc_analytics_dates(array $input): array
{
    $today=new DateTimeImmutable('today',new DateTimeZone('Asia/Singapore'));
    $dates=[];foreach(['from'=>$today->modify('-6 days')->format('Y-m-d'),'to'=>$today->format('Y-m-d')] as $key=>$default){
        $value=$input[$key]??$default;
        $date=is_string($value)&&preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value)?DateTimeImmutable::createFromFormat('!Y-m-d',$value):false;
        if(!$date||$date->format('Y-m-d')!==$value)throw new InvalidArgumentException('Choose valid start and end dates.');
        $dates[$key]=$value;
    }
    if($dates['from']>$dates['to'])throw new InvalidArgumentException('The start date must be on or before the end date.');
    if((new DateTimeImmutable($dates['from']))->diff(new DateTimeImmutable($dates['to']))->days>365)throw new InvalidArgumentException('Choose a reporting period of up to 366 days.');
    return $dates;
}
function snc_analytics_report(PDO $pdo,string $start,string $end): array
{
    snc_analytics_dates(['from'=>$start,'to'=>$end]);$owns=!$pdo->inTransaction();if($owns)$pdo->beginTransaction();
    try{
        $report=snc_dashboard_report($pdo,$start,$end,false,0);
        [$from,$where,$params]=snc_sales_scope(['from'=>$start,'to'=>$end,'status'=>'paid','method'=>'','search'=>'']);
        $q=$pdo->prepare("SELECT COALESCE(p.method,'unrecorded') AS method,COUNT(*) AS paid_orders,SUM(o.total_amount) AS paid_sales {$from}{$where} GROUP BY COALESCE(p.method,'unrecorded')");$q->execute($params);$methods=array_column($q->fetchAll(),null,'method');
        $report['payments']=[];foreach(['cash'=>'Cash','gcash'=>'GCash','card'=>'Card','unrecorded'=>'Not recorded'] as $method=>$label){
            if($method==='unrecorded'&&!isset($methods[$method]))continue;
            $row=$methods[$method]??['method'=>$method,'paid_orders'=>0,'paid_sales'=>'0.00'];$row['label']=$label;$report['payments'][]=$row;
        }
        if($owns)$pdo->commit();return $report;
    }catch(Throwable $e){if($owns&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function snc_analytics_amount(mixed $cents): string
{
    $cents=(int)$cents;return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT);
}
function snc_analytics_csv_cell(mixed $value): string
{
    $value=(string)$value;
    // Neutralize spreadsheet formulas in stored product names and other text.
    return preg_match('/^[\s\x00-\x1f]*[=+@-]/u',$value)?"'".$value:$value;
}
function snc_analytics_csv($stream,array $report,array $dates,string $generated): void
{
    $write=static function(array $row)use($stream): void {fputcsv($stream,array_map('snc_analytics_csv_cell',$row),',','"','');};
    $write(['SNC 2018 Cafe & Resto','Analytics & Reports']);$write(['From',$dates['from'],'To',$dates['to']]);$write(['Generated at (UTC+08:00)',$generated]);$write(['Currency','PHP']);$write(['Reporting rules','Paid orders only; after discounts; earliest recorded payment date, order date when no payment exists.']);$write([]);
    $write(['Summary','Value']);foreach(['Paid Sales'=>'paid_sales','Paid Orders'=>'paid_orders','Discounts'=>'discounts','Average Paid Order'=>'average_paid_order'] as $label=>$key)$write([$label,$report['totals'][$key]]);
    $write([]);$write(['Daily Sales']);$write(['Date','Paid Sales (PHP)']);foreach($report['series'] as $row)$write([$row['timestamp'],$row['amount']]);
    $write([]);$write(['Product Performance','All matching products, ranked by paid sales']);$write(['Product','Quantity Sold','Paid Sales (PHP)']);foreach($report['products'] as $row)$write([$row['item_name'],$row['quantity'],snc_analytics_amount($row['net_cents'])]);
    $write([]);$write(['Payment Breakdown']);$write(['Payment Method','Paid Orders','Paid Sales (PHP)']);foreach($report['payments'] as $row)$write([$row['label'],$row['paid_orders'],$row['paid_sales']]);
}
