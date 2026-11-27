<?php
declare(strict_types=1);
// CLI integration check using only uniquely named disposable fixtures.
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require __DIR__.'/../config/database.php';
require __DIR__.'/../includes/checkout.php';
require __DIR__.'/../includes/recipe.php';
$pdo=snc_get_db();$tag='QA stock '.bin2hex(random_bytes(6));$items=[];$ingredients=[];
function expect(bool $condition,string $label): void { if (!$condition) throw new RuntimeException($label);echo "PASS: {$label}\n"; }
function rejected(callable $operation,string $label): void {
    try { $operation(); } catch (InvalidArgumentException|RuntimeException $e) { echo "PASS: {$label}\n";return; }
    throw new LogicException($label);
}
function quantity(PDO $pdo,int $id): string { $q=$pdo->prepare('SELECT current_stock FROM inventory_ingredients WHERE id=?');$q->execute([$id]);return $q->fetchColumn(); }
try {
    $cashier=$pdo->query("SELECT id,full_name,role FROM users WHERE role='cashier' AND is_active=1 LIMIT 1")->fetch();
    $owner=$pdo->query("SELECT id,full_name,role FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetch();
    if (!$cashier||!$owner) throw new RuntimeException('An active owner and cashier are required.');
    foreach ([['milk','ml','1000'],['beans','g','100'],['egg','pcs','5']] as [$name,$unit,$balance]) {
        $pdo->prepare('INSERT INTO inventory_ingredients(name,base_unit,current_stock,is_active) VALUES(?,?,?,1)')->execute([$tag.' '.$name,$unit,$balance]);
        $ingredients[]=(int)$pdo->lastInsertId();
    }
    [$milk,$beans,$egg]=$ingredients;
    foreach ([['latte','100'],['coffee','50'],['manual','30'],['empty','25']] as [$name,$price]) {
        $pdo->prepare('INSERT INTO menu_items(name,category,price,is_available) VALUES(?,?,?,1)')->execute([$tag.' '.$name,'QA',$price]);$items[]=(int)$pdo->lastInsertId();
    }
    [$latte,$coffee,$manual,$empty]=$items;
    snc_recipe_save($pdo,$owner,$latte,0,[(string)$milk,(string)$beans],['200.125','18'],'recipe');
    snc_recipe_save($pdo,$owner,$coffee,0,[(string)$milk],['50'],'recipe');
    snc_recipe_save($pdo,$owner,$manual,0,[(string)$milk],['9999'],'manual');
    rejected(fn()=>snc_recipe_save($pdo,$owner,$empty,0,[],[],'recipe'),'automatic tracking requires a recipe');
    $payload=['request_key'=>bin2hex(random_bytes(16)),'items'=>[['menu_item_id'=>$latte,'quantity'=>2],['menu_item_id'=>$coffee,'quantity'=>1],['menu_item_id'=>$manual,'quantity'=>1]],'payment_method'=>'cash','amount_tendered'=>'300.00','table_or_note'=>$tag];
    $order=snc_checkout_create($pdo,$cashier,$payload);$orderId=$order['id'];
    expect($order['total_amount']==='280.00'&&$order['change_amount']==='20.00','cash payment and change');
    expect(quantity($pdo,$milk)==='549.750'&&quantity($pdo,$beans)==='64.000','shared ingredients and multiple drinks deduct exact amounts; manual recipe skipped');
    $q=$pdo->prepare('SELECT quantity_delta,movement_type,order_id FROM inventory_movements WHERE order_id=? ORDER BY ingredient_id');$q->execute([$orderId]);$movements=$q->fetchAll();
    expect(count($movements)===2&&$movements[0]['quantity_delta']==='-450.250'&&$movements[0]['movement_type']==='sale','one order-linked ledger entry per ingredient');
    $retry=snc_checkout_create($pdo,$cashier,$payload);
    expect($retry['id']===$orderId&&quantity($pdo,$milk)==='549.750','retry returns the original order without another deduction');
    $changed=$payload;$changed['amount_tendered']='301';
    rejected(fn()=>snc_checkout_create($pdo,$cashier,$changed),'same request key with changed details rejected');
    snc_recipe_save($pdo,$owner,$latte,1,[(string)$milk,(string)$egg],['100','1'],'recipe');
    $q=$pdo->prepare('SELECT quantity_per_item FROM order_recipe_usage WHERE order_id=? AND menu_item_id=? AND ingredient_id=?');$q->execute([$orderId,$latte,$milk]);
    expect($q->fetchColumn()==='200.125','original recipe quantity retained after editing');
    $fail=$payload;$fail['request_key']=bin2hex(random_bytes(16));$fail['items']=[['menu_item_id'=>$latte,'quantity'=>6]];$fail['payment_method']='gcash';
    rejected(fn()=>snc_checkout_create($pdo,$cashier,$fail),'insufficient shared stock rejects checkout');
    $q=$pdo->prepare('SELECT COUNT(*) FROM order_requests WHERE request_key=?');$q->execute([$fail['request_key']]);
    expect((int)$q->fetchColumn()===0&&quantity($pdo,$milk)==='549.750'&&quantity($pdo,$egg)==='5.000','failed checkout rolls back its request and all stock changes');
    $pdo->prepare('UPDATE inventory_ingredients SET is_active=0 WHERE id=?')->execute([$egg]);
    $fail['items']=[['menu_item_id'=>$latte,'quantity'=>1]];
    rejected(fn()=>snc_checkout_create($pdo,$cashier,$fail),'inactive recipe ingredient blocks checkout');
    $pdo->prepare('UPDATE inventory_ingredients SET is_active=1 WHERE id=?')->execute([$egg]);
    $fail['amount_tendered']='1';$fail['payment_method']='cash';
    rejected(fn()=>snc_checkout_create($pdo,$cashier,$fail),'underpayment blocks checkout');
    $fail['payment_method']='card';$success=snc_checkout_create($pdo,$cashier,$fail);
    expect(quantity($pdo,$milk)==='449.750'&&quantity($pdo,$egg)==='4.000'&&$success['change_amount']==='0.00','card payment and whole-piece ingredients');
    $manualPayload=$payload;$manualPayload['request_key']=bin2hex(random_bytes(16));$manualPayload['items']=[['menu_item_id'=>$manual,'quantity'=>1]];
    snc_checkout_create($pdo,$cashier,$manualPayload);
    expect(quantity($pdo,$milk)==='449.750','manual-only order leaves stock unchanged');
    echo "All automatic stock checks passed.\n";
} finally {
    // Only fixture menu IDs created above can identify cleanup targets.
    if ($items) {
        $pdo->beginTransaction();
        $marks=implode(',',array_fill(0,count($items),'?'));
        $q=$pdo->prepare("SELECT DISTINCT order_id FROM order_items WHERE menu_item_id IN ($marks)");$q->execute($items);$orders=$q->fetchAll(PDO::FETCH_COLUMN);
        foreach ($orders as $id) {
            $pdo->prepare("DELETE FROM audit_events WHERE target_type='order' AND target_id=?")->execute([$id]);
            foreach (['inventory_movements','order_recipe_usage','order_requests'] as $table) $pdo->prepare("DELETE FROM $table WHERE order_id=?")->execute([$id]);
            $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);
        }
        foreach ($items as $id) {
            $pdo->prepare("DELETE FROM audit_events WHERE target_type IN ('menu_item','recipe') AND target_id=?")->execute([$id]);
            $pdo->prepare('DELETE FROM menu_recipe_ingredients WHERE menu_item_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM menu_recipes WHERE menu_item_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM menu_items WHERE id=? AND name LIKE ?')->execute([$id,$tag.'%']);
        }
        foreach ($ingredients as $id) $pdo->prepare('DELETE FROM inventory_ingredients WHERE id=? AND name LIKE ?')->execute([$id,$tag.'%']);
        $pdo->commit();
    }
    echo "Disposable QA fixtures removed.\n";
}
