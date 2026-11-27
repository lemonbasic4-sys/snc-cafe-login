<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/database.php';require __DIR__.'/../includes/analytics-report.php';
$p=snc_get_db();$p->beginTransaction();$tag='QA analytics '.bin2hex(random_bytes(5));
function analytics_expect(bool $ok,string $label): void {if(!$ok)throw new LogicException($label);echo "PASS: $label\n";}
function analytics_bad_dates(array $input,string $label): void {try{snc_analytics_dates($input);}catch(InvalidArgumentException $e){echo "PASS: $label\n";return;}throw new LogicException($label);}
function analytics_order(PDO $p,string $tag,string $status,string $date,string $total,?string $method,string $discount='0.00',string $kitchen='pending'): int {
 $p->prepare('INSERT INTO orders(order_number,table_or_note,payment_status,created_at,subtotal,discount_amount,total_amount,kitchen_status) VALUES(?,?,?,?,?,?,?,?)')->execute(['QA-A-'.bin2hex(random_bytes(8)),$tag,$status,$date,(string)((float)$total+(float)$discount),$discount,$total,$kitchen]);$id=(int)$p->lastInsertId();
 if($method)$p->prepare('INSERT INTO payments(order_id,method,amount_tendered,change_amount,paid_at) VALUES(?,?,1000,0,?)')->execute([$id,$method,$date]);return $id;
}
try{
 analytics_bad_dates(['from'=>'2026-02-30','to'=>'2026-03-01'],'invalid calendar date rejected');analytics_bad_dates(['from'=>'2026-11-27','to'=>'2026-11-01'],'reversed dates rejected');analytics_bad_dates(['from'=>['bad'],'to'=>'2026-11-27'],'array dates rejected');analytics_bad_dates(['from'=>'2024-01-01','to'=>'2026-01-01'],'overlong report range rejected');
 // Remote dates isolate fixtures from normal application activity; everything rolls back.
 $id=analytics_order($p,$tag,'paid','2040-02-01 00:00:00','2.00','cash','1.00','completed');
 $q=$p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,1,1,1)');foreach(['=SUM(1,2)','QA second','QA third'] as $name)$q->execute([$id,$name]);
 $p->prepare("INSERT INTO payments(order_id,method,amount_tendered,paid_at) VALUES(?,'card',1000,'2040-02-01 01:00:00')")->execute([$id]);
 $second=analytics_order($p,$tag,'paid','2040-02-02 23:59:59','5.00','gcash');$p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,5,1,5)')->execute([$second,'QA fourth']);
 $missing=analytics_order($p,$tag,'paid','2040-02-02 12:00:00','4.00',null);$p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,4,1,4)')->execute([$missing,'QA no payment']);
 $free=analytics_order($p,$tag,'paid','2040-02-02 12:00:00','0.00','card');$p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,0,2,0)')->execute([$free,'QA free product']);
 foreach(['unpaid','voided'] as $status)analytics_order($p,$tag,$status,'2040-02-01 12:00:00','500.00','cash');analytics_order($p,$tag,'paid','2040-02-04 00:00:00','900.00','cash');
 $moved=analytics_order($p,$tag,'paid','2040-01-31 23:59:59','3.00','cash');$p->prepare("UPDATE payments SET paid_at='2040-02-02 12:00:00' WHERE order_id=?")->execute([$moved]);$p->prepare('INSERT INTO order_items(order_id,item_name,unit_price,quantity,line_total) VALUES(?,?,3,1,3)')->execute([$moved,'QA payment date']);
 $r=snc_analytics_report($p,'2040-02-01','2040-02-03');$t=$r['totals'];analytics_expect($t['paid_sales']==='14.00'&&(int)$t['paid_orders']===5&&$t['discounts']==='1.00'&&$t['average_paid_order']==='2.80','paid totals exclude unpaid, voided and out-of-period orders');
 analytics_expect(count($r['series'])===3&&$r['series'][0]['amount']==='2.00'&&$r['series'][1]['amount']==='12.00'&&$r['series'][2]['amount']==='0.00','daily series includes date boundaries, payment dates and zero-sales days');
 analytics_expect(array_sum(array_map(fn($row)=>(int)$row['net_cents'],$r['products']))===1400,'product allocation exactly reconciles discounted paid sales');
 $products=array_column($r['products'],null,'item_name');analytics_expect($products['=SUM(1,2)']['net_cents']==='67'&&$products['QA third']['net_cents']==='66','fractional discount cents use deterministic largest remainders');analytics_expect((int)$products['QA free product']['quantity']===2&&$products['QA free product']['net_cents']==='0','zero-priced paid products retain quantities');
 $payments=array_column($r['payments'],null,'method');analytics_expect($payments['cash']['paid_sales']==='5.00'&&(int)$payments['cash']['paid_orders']===2&&(int)$payments['card']['paid_orders']===1&&$payments['unrecorded']['paid_sales']==='4.00','payment breakdown counts each order once using its first payment and exposes missing records');
 analytics_expect(array_sum(array_map(fn($row)=>(float)$row['paid_sales'],$r['payments']))===14.0,'payment methods reconcile to paid sales');
 $single=snc_analytics_report($p,'2040-02-01','2040-02-01');analytics_expect(count($single['series'])===1&&!$single['hourly'],'single-day analytics remains daily');$dashboard=snc_dashboard_report($p,'2040-02-01','2040-02-03');analytics_expect($dashboard['totals']===$r['totals']&&count($dashboard['products'])===5,'dashboard retains its default product limit and agrees with report totals');
 $stream=fopen('php://temp','w+');snc_analytics_csv($stream,$r,['from'=>'2040-02-01','to'=>'2040-02-03'],'2040-02-04 10:00:00');rewind($stream);$csv=stream_get_contents($stream);fclose($stream);analytics_expect(str_contains($csv,"'=SUM(1,2)")&&str_contains($csv,'2040-02-03,0.00')&&str_contains($csv,'"Not recorded",1,4.00'),'CSV contains all report sections, zero days and neutralizes stored formulas');
 analytics_expect(snc_analytics_csv_cell('  +danger')==="'  +danger"&&snc_analytics_amount('1401')==='14.01','CSV cell protection covers leading whitespace and monetary cents format exactly');
 $empty=snc_analytics_report($p,'2041-01-01','2041-01-02');analytics_expect((int)$empty['totals']['paid_orders']===0&&count($empty['series'])===2&&$empty['products']===[],'empty periods return a usable zero report');
}finally{if($p->inTransaction())$p->rollBack();echo "Analytics QA fixtures rolled back.\n";}
