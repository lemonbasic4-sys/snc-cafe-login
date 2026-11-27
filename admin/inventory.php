<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/inventory.php';
require_once __DIR__.'/../includes/inventory-view.php';
$user=snc_require_auth(['admin']); $csrfToken=snc_csrf_token(); $flash=snc_pop_flash();
$search=is_string($_GET['search']??null)?mb_substr(trim($_GET['search']),0,120):'';
$status=in_array($_GET['status']??'', ['active','all','healthy','low','out','inactive'],true)?$_GET['status']:'active';
$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;
$items=[];$stats=null;$error='';$total=0;$pages=1;
try {
    $pdo=snc_get_db();
    $stats=$pdo->query('SELECT COALESCE(SUM(is_active=1),0) AS active, COALESCE(SUM(is_active=1 AND current_stock>0 AND current_stock<=low_stock_threshold),0) AS low, COALESCE(SUM(is_active=1 AND current_stock=0),0) AS out_count FROM inventory_ingredients')->fetch();
    $conditions=[];$params=[];
    if($search!==''){$conditions[]='LOCATE(?,name)>0';$params[]=$search;}
    $states=['active'=>'is_active=1','healthy'=>'is_active=1 AND current_stock>low_stock_threshold','low'=>'is_active=1 AND current_stock>0 AND current_stock<=low_stock_threshold','out'=>'is_active=1 AND current_stock=0','inactive'=>'is_active=0'];
    if(isset($states[$status]))$conditions[]=$states[$status];
    $where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM inventory_ingredients'.$where);$stmt->execute($params);$total=(int)$stmt->fetchColumn();
    $pages=max(1,(int)ceil($total/25));$page=min($page,$pages);$offset=($page-1)*25;
    $stmt=$pdo->prepare("SELECT * FROM inventory_ingredients{$where} ORDER BY name,id LIMIT 25 OFFSET {$offset}");$stmt->execute($params);$items=$stmt->fetchAll();
}catch(Throwable $e){http_response_code(503);$error='Inventory is unavailable. Please try again.';}
$filters=['search'=>$search,'status'=>$status];
snc_inventory_page_start($user,$csrfToken,'Inventory',$flash);
?>
  <section class="snc-dashboard__header"><div><h1 class="snc-dashboard__title">Inventory</h1><p class="snc-dashboard__subtitle">Track ingredient stock and every change in quantity.</p></div><div class="snc-menu-actions"><a class="snc-menu-button" href="inventory-history.php">Stock History</a><?php if(!$error): ?><button class="snc-menu-button snc-menu-button--primary" type="button" id="snc-add-ingredient">Add Ingredient</button><?php endif; ?></div></section>
  <?php if($error): ?><section class="snc-card"><p role="alert"><?= snc_inventory_escape($error) ?></p></section><?php else: ?>
  <section class="snc-kpi-grid snc-inventory-stats" aria-label="Stock summary across all active ingredients">
    <?php foreach([['Active Ingredients',$stats['active'],'Ingredients currently in use'],['Low Stock',$stats['low'],'Above zero and at or below the threshold'],['Out of Stock',$stats['out_count'],'Active ingredients with zero stock']] as [$label,$value,$hint]): ?><article class="snc-kpi-card"><h2 class="snc-kpi-card__label"><?= $label ?></h2><p class="snc-kpi-card__value"><?= (int)$value ?></p><p class="snc-kpi-card__meta"><?= $hint ?></p></article><?php endforeach; ?>
  </section>
  <section class="snc-card">
    <form class="snc-menu-filters" method="get" action="inventory.php"><label>Search ingredients<input name="search" maxlength="120" value="<?= snc_inventory_escape($search) ?>" placeholder="Ingredient name"></label><label>Stock status<select name="status"><?php foreach(['active'=>'All Active','all'=>'All Ingredients','healthy'=>'In Stock','low'=>'Low Stock','out'=>'Out of Stock','inactive'=>'Inactive'] as $key=>$label): ?><option value="<?= $key ?>"<?= $status===$key?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label><button class="snc-menu-button" type="submit">Search</button><a class="snc-menu-button" href="inventory.php">Reset</a></form>
    <div class="snc-menu-results"><h2>Ingredients <span class="snc-menu-count"><?= $total ?></span></h2><span>Page <?= $page ?> of <?= $pages ?></span></div>
    <div class="snc-table-wrap" role="region" aria-label="Ingredients" tabindex="0"><table class="snc-table snc-inventory-table"><thead><tr><th>Ingredient</th><th>Base Unit</th><th>Current Stock</th><th>Low-stock Threshold</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($items as $item): $stockStatus=!(int)$item['is_active']?'inactive':((float)$item['current_stock']===0.0?'out':((float)$item['current_stock']<=(float)$item['low_stock_threshold']?'low':'healthy')); ?>
      <tr><td><?= snc_inventory_escape($item['name']) ?></td><td><?= snc_inventory_escape($item['base_unit']) ?></td><td class="snc-inventory-number"><?= snc_inventory_qty($item['current_stock']) ?> <?= snc_inventory_escape($item['base_unit']) ?></td><td class="snc-inventory-number"><?= snc_inventory_qty($item['low_stock_threshold']) ?> <?= snc_inventory_escape($item['base_unit']) ?></td><td><span class="snc-inventory-status" data-stock-status="<?= $stockStatus ?>"><?= ['inactive'=>'Inactive','out'=>'Out of Stock','low'=>'Low Stock','healthy'=>'In Stock'][$stockStatus] ?></span></td><td><div class="snc-menu-actions"><button class="snc-menu-button" type="button" data-edit-ingredient="<?= snc_inventory_escape(json_encode($item)) ?>" aria-label="Edit <?= snc_inventory_escape($item['name']) ?>">Edit</button><button class="snc-menu-button" type="button" data-stock-movement="<?= snc_inventory_escape(json_encode($item)) ?>"<?= !(int)$item['is_active']?' disabled':'' ?> aria-label="Adjust stock for <?= snc_inventory_escape($item['name']) ?>">Adjust Stock</button><a class="snc-menu-button" href="inventory-history.php?ingredient_id=<?= (int)$item['id'] ?>">History</a></div></td></tr>
    <?php endforeach; ?><?php if(!$items): ?><tr><td colspan="6">No ingredients match this view. Add an ingredient or change the filters.</td></tr><?php endif; ?>
    </tbody></table></div>
    <nav class="snc-menu-pagination" aria-label="Ingredient pages"><?php if($page>1): ?><a class="snc-menu-button" href="?<?= snc_inventory_escape(http_build_query($filters+['page'=>$page-1])) ?>">Previous</a><?php endif; ?><?php if($page<$pages): ?><a class="snc-menu-button" href="?<?= snc_inventory_escape(http_build_query($filters+['page'=>$page+1])) ?>">Next</a><?php endif; ?></nav>
    <p class="snc-menu-hint">All stock adjustments are recorded in Stock History.</p>
  </section>
  <?php endif; ?>
  <input type="hidden" id="snc-inventory-csrf" value="<?= snc_inventory_escape($csrfToken) ?>">
  <dialog class="snc-menu-dialog" id="snc-ingredient-dialog" aria-labelledby="snc-ingredient-title">
    <div class="snc-menu-dialog__head"><div><h2 id="snc-ingredient-title">Add Ingredient</h2><p class="snc-menu-hint" id="snc-ingredient-subtitle">Choose a base unit for stock and future recipes.</p></div><button class="snc-menu-button" type="button" data-inventory-close>Close</button></div>
    <form data-inventory-save id="snc-ingredient-form"><input type="hidden" name="action" value="save_ingredient"><input type="hidden" name="id" value="0"><input type="hidden" name="request_key">
      <div class="snc-item-fields"><label class="snc-item-fields__wide">Ingredient name<input name="name" maxlength="120" required placeholder="e.g. Fresh Milk"></label>
      <label>Base unit<select name="base_unit"><option value="g">Grams (g)</option><option value="ml">Milliliters (ml)</option><option value="pcs">Pieces (pcs)</option></select></label><label>Active status<select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></label>
      <label data-opening-field>Opening stock<input name="opening_stock" type="number" min="0" max="9999999.999" step="0.001" value="0" required></label><label><span id="snc-threshold-label">Low-stock threshold (g)</span><input name="low_stock_threshold" type="number" min="0" max="9999999.999" step="0.001" value="0" required></label>
      <label class="snc-item-fields__wide" data-opening-field>Opening stock note (optional)<input name="reason" maxlength="240" placeholder="e.g. Initial stock count"></label></div>
      <p class="snc-menu-hint" id="snc-unit-hint">Opening stock and threshold use the selected base unit.</p><p class="snc-menu-error" role="status" data-form-message></p>
      <div class="snc-menu-actions"><button class="snc-menu-button" type="button" data-inventory-close>Cancel</button><button class="snc-menu-button snc-menu-button--primary" type="submit">Save Ingredient</button></div>
    </form>
  </dialog>
  <dialog class="snc-menu-dialog" id="snc-movement-dialog" aria-labelledby="snc-movement-title">
    <div class="snc-menu-dialog__head"><div><h2 id="snc-movement-title">Adjust Stock</h2><p class="snc-menu-hint" id="snc-movement-ingredient"></p></div><button class="snc-menu-button" type="button" data-inventory-close>Close</button></div>
    <form data-inventory-save id="snc-movement-form"><input type="hidden" name="action" value="movement"><input type="hidden" name="id"><input type="hidden" name="request_key">
      <div class="snc-item-fields"><label class="snc-item-fields__wide">Movement type<select name="movement_type"><?php foreach(SNC_INVENTORY_TYPES as $key=>$label): if(in_array($key,['opening','sale'],true))continue; ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?></select></label>
      <label>Quantity<input name="quantity" type="number" min="0.001" max="9999999.999" step="0.001" required></label><label>Entry unit<select name="entry_unit"></select></label>
      <label class="snc-item-fields__wide">Reason<input name="reason" maxlength="240" required placeholder="e.g. Delivery reference or waste reason"></label></div>
      <p class="snc-menu-hint" id="snc-movement-hint"></p><div class="snc-stock-preview" aria-live="polite" aria-atomic="true"><span>Resulting balance</span><strong id="snc-stock-preview">Enter a quantity to preview the balance.</strong><small>Based on the displayed stock. The latest balance is checked when saving.</small></div><p class="snc-menu-error" role="status" data-form-message></p>
      <div class="snc-menu-actions"><button class="snc-menu-button" type="button" data-inventory-close>Cancel</button><button class="snc-menu-button snc-menu-button--primary" type="submit">Save Adjustment</button></div>
    </form>
  </dialog>
<?php snc_inventory_page_end(); ?>
