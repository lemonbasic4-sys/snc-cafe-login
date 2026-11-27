<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/inventory.php';require_once __DIR__.'/../includes/inventory-view.php';
$user=snc_require_auth(['admin']);$csrfToken=snc_csrf_token();
$ingredient=filter_input(INPUT_GET,'ingredient_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$from=is_string($_GET['from']??null)?$_GET['from']:'';$to=is_string($_GET['to']??null)?$_GET['to']:'';
$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;
$error='';$rows=[];$ingredients=[];$total=0;$pages=1;
foreach([$from,$to] as $date)if($date!==''){
    $parsed=preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)?DateTimeImmutable::createFromFormat('!Y-m-d',$date):false;
    if(!$parsed||$parsed->format('Y-m-d')!==$date)$error='Choose valid dates.';
}
if($from!==''&&$to!==''&&$from>$to)$error='The start date must be on or before the end date.';
if($error)http_response_code(400);
try{
    $pdo=snc_get_db();$ingredients=$pdo->query('SELECT id,name FROM inventory_ingredients ORDER BY name')->fetchAll();
    if(!$error){
        $conditions=[];$params=[];
        if($ingredient){$conditions[]='ingredient_id=?';$params[]=$ingredient;}
        if($from!==''){$conditions[]='created_at>=?';$params[]=$from;}
        if($to!==''){$conditions[]='created_at<DATE_ADD(?,INTERVAL 1 DAY)';$params[]=$to;}
        $where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM inventory_movements'.$where);$stmt->execute($params);$total=(int)$stmt->fetchColumn();$pages=max(1,(int)ceil($total/25));$page=min($page,$pages);$offset=($page-1)*25;
        $stmt=$pdo->prepare("SELECT * FROM inventory_movements{$where} ORDER BY id DESC LIMIT 25 OFFSET {$offset}");$stmt->execute($params);$rows=$stmt->fetchAll();
    }
}catch(Throwable $e){http_response_code(503);$error='Stock history is unavailable. Please try again.';}
$filters=['ingredient_id'=>$ingredient?:'','from'=>$from,'to'=>$to];snc_inventory_page_start($user,$csrfToken,'Stock History');
?>
  <section class="snc-dashboard__header"><div><h1 class="snc-dashboard__title">Stock History</h1><p class="snc-dashboard__subtitle">A permanent record of opening stock, deliveries, usage, waste, and corrections.</p></div><a class="snc-menu-button" href="inventory.php">Back to Inventory</a></section>
  <section class="snc-card"><form class="snc-menu-filters" method="get" action="inventory-history.php"><label>Ingredient<select name="ingredient_id"><option value="">All Ingredients</option><?php foreach($ingredients as $item): ?><option value="<?= (int)$item['id'] ?>"<?= $ingredient===(int)$item['id']?' selected':'' ?>><?= snc_inventory_escape($item['name']) ?></option><?php endforeach; ?></select></label><label>From<input type="date" name="from" value="<?= snc_inventory_escape($from) ?>"></label><label>To<input type="date" name="to" value="<?= snc_inventory_escape($to) ?>"></label><button class="snc-menu-button" type="submit">Apply Filters</button><a class="snc-menu-button" href="inventory-history.php">Reset</a></form>
  <?php if($error): ?><p role="alert"><?= snc_inventory_escape($error) ?></p><?php else: ?>
    <div class="snc-menu-results"><h2>Stock Movements <span class="snc-menu-count"><?= $total ?></span></h2><span>Page <?= $page ?> of <?= $pages ?></span></div>
    <div class="snc-table-wrap" role="region" aria-label="Stock movement history" tabindex="0"><table class="snc-table snc-inventory-table snc-inventory-history"><thead><tr><th>Recorded at</th><th>Ingredient</th><th>Movement</th><th>Change</th><th>Before</th><th>After</th><th>Reason</th><th>Recorded by</th></tr></thead><tbody>
    <?php foreach($rows as $row): ?><tr><td><?= snc_inventory_escape((new DateTimeImmutable($row['created_at']))->format('M j, Y · g:i A')) ?></td><td><?= snc_inventory_escape($row['ingredient_name']) ?></td><td><?= SNC_INVENTORY_TYPES[$row['movement_type']] ?></td><td class="snc-inventory-number" data-direction="<?= (float)$row['quantity_delta']<0?'out':'in' ?>"><?= (float)$row['quantity_delta']>0?'+':'' ?><?= snc_inventory_qty($row['quantity_delta']) ?> <?= snc_inventory_escape($row['base_unit']) ?></td><td class="snc-inventory-number"><?= snc_inventory_qty($row['balance_before']) ?> <?= snc_inventory_escape($row['base_unit']) ?></td><td class="snc-inventory-number"><?= snc_inventory_qty($row['balance_after']) ?> <?= snc_inventory_escape($row['base_unit']) ?></td><td class="snc-inventory-reason"><?= snc_inventory_escape($row['reason']) ?><?php if($row['order_id']): ?> <a href="order.php?order_id=<?= (int)$row['order_id'] ?>">View Order</a><?php endif; ?></td><td><?= snc_inventory_escape($row['actor_name']) ?></td></tr><?php endforeach; ?>
    <?php if(!$rows): ?><tr><td colspan="8">No stock movements match these filters.</td></tr><?php endif; ?></tbody></table></div>
    <nav class="snc-menu-pagination" aria-label="Stock history pages"><?php if($page>1): ?><a class="snc-menu-button" href="?<?= snc_inventory_escape(http_build_query($filters+['page'=>$page-1])) ?>">Previous</a><?php endif; ?><?php if($page<$pages): ?><a class="snc-menu-button" href="?<?= snc_inventory_escape(http_build_query($filters+['page'=>$page+1])) ?>">Next</a><?php endif; ?></nav>
    <p class="snc-menu-hint">Quantities use each ingredient’s base unit. Correct mistakes with a new correction movement so the original entry stays in history.</p>
  <?php endif; ?></section>
<?php snc_inventory_page_end(); ?>
