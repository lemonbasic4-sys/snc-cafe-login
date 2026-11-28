<?php
declare(strict_types=1);
require_once __DIR__.'/inventory.php';
require_once __DIR__.'/system-settings.php';
require_once __DIR__.'/array-utils.php';

function snc_checkout_cents(mixed $value): int
{
    if (is_int($value)||is_float($value)) $value=(string)$value;
    if (!is_string($value)||!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',$value)) throw new InvalidArgumentException('Enter a valid payment amount with up to two decimal places.');
    $parts=explode('.',$value);
    return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
}
function snc_checkout_money(int $cents): string { return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT); }

/** Owns the transaction so paid order, payment, stock, and request identity commit together. */
function snc_checkout_create(PDO $pdo,array $user,array $payload): array
{
    if (($user['role']??'')!=='cashier') throw new RuntimeException('Only cashiers can create orders.',403);
    $key=$payload['request_key']??'';
    if (!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new InvalidArgumentException('Refresh the cashier page before submitting this order.');
    $input=$payload['items']??null;
    if (!is_array($input)||!snc_is_list($input)||!count($input)||count($input)>100) throw new InvalidArgumentException('Choose between 1 and 100 menu items.');
    $requested=[];
    foreach ($input as $entry) {
        if (!is_array($entry)) throw new InvalidArgumentException('Invalid order item.');
        if (!empty($entry['variation_id'])||!empty($entry['addon_ids'])) throw new InvalidArgumentException('Refresh the cashier page before submitting this order.');
        $id=filter_var($entry['menu_item_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $qty=filter_var($entry['quantity']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99]]);
        if (!$id||!$qty||($requested[$id]??0)+$qty>99) throw new InvalidArgumentException('Choose valid menu items with quantities from 1 to 99.');
        $requested[$id]=($requested[$id]??0)+$qty;
    }
    ksort($requested);
    $type=$payload['order_type']??'dine_in';$method=$payload['payment_method']??'cash';$note=$payload['table_or_note']??'';
    if (!in_array($type,['dine_in','takeout'],true)||!in_array($method,['cash','gcash','card'],true)) throw new InvalidArgumentException('Choose a valid order type and payment method.');
    if (!is_string($note)||mb_strlen(trim($note))>80||preg_match('/[\x00-\x1f\x7f]/u',$note)) throw new InvalidArgumentException('Table or customer must be at most 80 characters.');
    $note=trim($note);$cash=$method==='cash'?snc_checkout_cents($payload['amount_tendered']??null):null;
    $hash=hash('sha256',json_encode([$requested,$type,$method,$note,$cash],JSON_THROW_ON_ERROR));
    // Recognize base-item retries submitted before the coffee-options rollback.
    $previousFormat=[];
    foreach($requested as $id=>$qty) {
        $lineKey=hash('md5',json_encode([$id,0,[]],JSON_THROW_ON_ERROR));
        $previousFormat[$lineKey]=['id'=>$id,'qty'=>$qty,'variation'=>0,'addons'=>[],'key'=>$lineKey];
    }
    $previousHash=hash('sha256',json_encode([$previousFormat,$type,$method,$note,$cash],JSON_THROW_ON_ERROR));
    if ($pdo->inTransaction()) throw new LogicException('Checkout must own its transaction.');
    $pdo->beginTransaction();
    try {
        // Unique key serializes simultaneous retries before any stock is touched.
        $stmt=$pdo->prepare('INSERT INTO order_requests(request_key,request_hash,cashier_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE request_key=VALUES(request_key)');
        $stmt->execute([$key,$hash,$user['id']]);
        $stmt=$pdo->prepare('SELECT * FROM order_requests WHERE request_key=? FOR UPDATE');$stmt->execute([$key]);$request=$stmt->fetch();
        if ((int)$request['cashier_id']!==(int)$user['id']||(!hash_equals($request['request_hash'],$hash)&&!hash_equals($request['request_hash'],$previousHash))) throw new RuntimeException('This checkout request was already used with different details.',409);
        if ($request['order_id']) {
            $stmt=$pdo->prepare('SELECT o.id,o.order_number,o.total_amount,o.payment_status,p.change_amount,p.method AS payment_method FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=? ORDER BY p.id LIMIT 1');
            $stmt->execute([$request['order_id']]);$result=$stmt->fetch();
            if (!$result) throw new RuntimeException('Unable to verify the earlier order. Check Recent Orders before trying again.',409);
            $pdo->commit();$result['id']=(int)$result['id'];return $result;
        }
        $settings=snc_settings_get($pdo,true);
        if(!isset(snc_settings_methods($settings)[$method]))throw new RuntimeException('This payment method is no longer enabled. Choose another method or refresh the cashier page.',409);
        $menu=[];$demands=[];$snapshots=[];$total=0;
        foreach ($requested as $id=>$qty) {
            // Recipe saves lock this same menu row, so quantities and tracking mode agree.
            $stmt=$pdo->prepare('SELECT id,name,price,is_available FROM menu_items WHERE id=? FOR UPDATE');$stmt->execute([$id]);$item=$stmt->fetch();
            if (!$item||!(int)$item['is_available']) throw new InvalidArgumentException('One or more selected items are unavailable. Refresh the menu.');
            $price=snc_checkout_cents($item['price']);$line=$price*$qty;$total+=$line;
            if ($line>9999999999||$total>9999999999) throw new InvalidArgumentException('The order total exceeds the supported limit.');
            $menu[]=['id'=>$id,'name'=>$item['name'],'qty'=>$qty,'price'=>$price,'line'=>$line];
            $stmt=$pdo->prepare('SELECT stock_tracking,revision FROM menu_recipes WHERE menu_item_id=? FOR UPDATE');$stmt->execute([$id]);$recipe=$stmt->fetch();
            if (!$recipe||$recipe['stock_tracking']!=='recipe') continue;
            $stmt=$pdo->prepare('SELECT ingredient_id,quantity FROM menu_recipe_ingredients WHERE menu_item_id=? ORDER BY ingredient_id FOR UPDATE');$stmt->execute([$id]);$parts=$stmt->fetchAll();
            if (!$parts) throw new RuntimeException($item['name'].' has automatic tracking enabled but no recipe. Ask the owner to review it.',409);
            foreach ($parts as $part) {
                $perItem=snc_inventory_parse($part['quantity']);$used=$perItem*$qty;$ingredientId=(int)$part['ingredient_id'];
                if ($perItem<=0||$used>9999999999) throw new RuntimeException('An ingredient quantity exceeds the stock limit. Ask the owner to review the recipe.',409);
                $demands[$ingredientId]=($demands[$ingredientId]??0)+$used;
                $snapshots[]=[$id,$item['name'],$qty,(int)$recipe['revision'],$ingredientId,$perItem,$used];
            }
        }

        $tendered=$cash??$total;
        if ($tendered<$total) throw new InvalidArgumentException('Amount tendered is less than the order total.');
        ksort($demands);$stock=[];
        foreach ($demands as $id=>$used) {
            $stmt=$pdo->prepare('SELECT * FROM inventory_ingredients WHERE id=? FOR UPDATE');$stmt->execute([$id]);$ingredient=$stmt->fetch();
            if (!$ingredient||!(int)$ingredient['is_active']) throw new RuntimeException('A required recipe ingredient is inactive or missing. Ask the owner to review the recipe.',409);
            $before=snc_inventory_parse($ingredient['current_stock']);
            if ($used>$before) throw new RuntimeException('Not enough '.$ingredient['name'].': need '.snc_inventory_qty(snc_inventory_decimal($used)).' '.$ingredient['base_unit'].', available '.snc_inventory_qty($ingredient['current_stock']).' '.$ingredient['base_unit'].'. No order was saved.',409);
            if ($ingredient['base_unit']==='pcs' && $used%1000) throw new RuntimeException('A recipe has an invalid pieces quantity. Ask the owner to review it.',409);
            $stock[$id]=$ingredient+['before'=>$before,'after'=>$before-$used];
        }
        $stmt=$pdo->prepare("INSERT INTO orders(order_number,cashier_id,order_type,table_or_note,subtotal,discount_amount,total_amount,payment_status,kitchen_status,receipt_profile) VALUES(?,?,?,?,?,0,?,'paid','pending',?)");
        $stmt->execute(['TMP-'.bin2hex(random_bytes(8)),$user['id'],$type,$note,snc_checkout_money($total),snc_checkout_money($total),json_encode(snc_receipt_profile($settings),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        $orderId=(int)$pdo->lastInsertId();$number=sprintf('SNC-%s-%04d',(new DateTimeImmutable('now',new DateTimeZone('Asia/Singapore')))->format('Ymd'),$orderId);
        $pdo->prepare('UPDATE orders SET order_number=? WHERE id=?')->execute([$number,$orderId]);
        $stmt=$pdo->prepare('INSERT INTO order_items(order_id,menu_item_id,item_name,unit_price,quantity,line_total) VALUES(?,?,?,?,?,?)');
        foreach ($menu as $item) $stmt->execute([$orderId,$item['id'],$item['name'],snc_checkout_money($item['price']),$item['qty'],snc_checkout_money($item['line'])]);
        $pdo->prepare('INSERT INTO payments(order_id,method,amount_tendered,change_amount,cashier_id) VALUES(?,?,?,?,?)')->execute([$orderId,$method,snc_checkout_money($tendered),snc_checkout_money($tendered-$total),$user['id']]);
        $stmt=$pdo->prepare('INSERT INTO order_recipe_usage(order_id,menu_item_id,item_name,quantity_sold,recipe_revision,ingredient_id,base_unit,quantity_per_item,quantity_used) VALUES(?,?,?,?,?,?,?,?,?)');
        foreach ($snapshots as [$id,$name,$qty,$rev,$ingredientId,$perItem,$used]) $stmt->execute([$orderId,$id,$name,$qty,$rev,$ingredientId,$stock[$ingredientId]['base_unit'],snc_inventory_decimal($perItem),snc_inventory_decimal($used)]);
        foreach ($stock as $id=>$ingredient) {
            $pdo->prepare('UPDATE inventory_ingredients SET current_stock=? WHERE id=?')->execute([snc_inventory_decimal($ingredient['after']),$id]);
            $stmt=$pdo->prepare("INSERT INTO inventory_movements(ingredient_id,ingredient_name,base_unit,movement_type,quantity_delta,balance_before,balance_after,reason,actor_id,actor_name,request_key,request_hash,order_id) VALUES(?,?,?,'sale',?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$id,$ingredient['name'],$ingredient['base_unit'],snc_inventory_decimal(-$demands[$id]),snc_inventory_decimal($ingredient['before']),snc_inventory_decimal($ingredient['after']),'Sold order '.$number,$user['id'],$user['full_name'],bin2hex(random_bytes(16)),$hash,$orderId]);
        }
        $pdo->prepare('UPDATE order_requests SET order_id=? WHERE request_key=?')->execute([$orderId,$key]);
        snc_audit_write($pdo,$user,'order','paid','order',$orderId,$number,null,['order_number'=>$number,'total_amount'=>snc_checkout_money($total),'payment_method'=>$method,'items_count'=>array_sum($requested),'kitchen_status'=>'pending']);
        $pdo->commit();
        return ['id'=>$orderId,'order_number'=>$number,'total_amount'=>snc_checkout_money($total),'change_amount'=>snc_checkout_money($tendered-$total),'payment_method'=>$method,'payment_status'=>'paid'];
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack();throw $e; }
}
