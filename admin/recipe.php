<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/recipe.php';
$user=snc_require_auth(['admin']);$csrfToken=snc_csrf_token();$flash=snc_pop_flash();
header('Cache-Control: no-store');
function snc_recipe_escape(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
$itemId=filter_input(INPUT_GET,'item_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$error='';$fatal=false;$item=null;$ingredients=[];$lines=[];$revision=0;$updated=null;$tracking='manual';
try {
    $pdo=snc_get_db();
    $stmt=$pdo->prepare('SELECT id,name,category,price FROM menu_items WHERE id=?');$stmt->execute([$itemId]);$item=$stmt->fetch();
    if (!$item) { http_response_code(404);$fatal=true;$error='Menu item not found. Return to Menu Management and choose an item.'; }
    else {
        $stmt=$pdo->prepare('SELECT r.revision,r.updated_at,r.stock_tracking,u.full_name FROM menu_recipes r LEFT JOIN users u ON u.id=r.updated_by WHERE r.menu_item_id=?');
        $stmt->execute([$itemId]);$updated=$stmt->fetch();$revision=$updated?(int)$updated['revision']:0;$tracking=$updated['stock_tracking']??'manual';
        $stmt=$pdo->prepare('SELECT ingredient_id,quantity FROM menu_recipe_ingredients WHERE menu_item_id=? ORDER BY ingredient_id');$stmt->execute([$itemId]);$lines=$stmt->fetchAll();
        $stmt=$pdo->prepare('SELECT i.id,i.name,i.base_unit,i.is_active FROM inventory_ingredients i WHERE i.is_active=1 OR EXISTS(SELECT 1 FROM menu_recipe_ingredients r WHERE r.ingredient_id=i.id AND r.menu_item_id=?) ORDER BY i.name');
        $stmt->execute([$itemId]);$ingredients=$stmt->fetchAll();
        if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
            if (!snc_verify_csrf(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)) {
                http_response_code(403);$error='Session expired. Reload this page before saving.';
            } else {
                try {
                    snc_recipe_save($pdo,$user,$itemId,$_POST['revision']??null,$_POST['ingredient_id']??[],$_POST['quantity']??[],$_POST['stock_tracking']??null);
                    snc_set_flash('success',empty($_POST['ingredient_id'])?'Recipe cleared.':'Recipe saved for one menu item.','Recipe Updated');
                    header('Location: recipe.php?item_id='.$itemId,true,303);exit;
                } catch (InvalidArgumentException $e) { http_response_code(422);$error=$e->getMessage(); }
                catch (RuntimeException $e) {
                    if ($e instanceof PDOException || $e->getCode()!==409) throw $e;
                    http_response_code(409);$error=$e->getMessage();
                }
                // Preserve the submitted form, including its revision, on a validation/conflict error.
                if (is_array($_POST['ingredient_id']??[]) && is_array($_POST['quantity']??[])) {
                    $lines=[];
                    foreach (array_slice($_POST['ingredient_id']??[],0,100,true) as $index=>$id) {
                        $quantity=$_POST['quantity'][$index]??'';
                        if (is_scalar($id)&&is_scalar($quantity)) $lines[]=['ingredient_id'=>(string)$id,'quantity'=>(string)$quantity];
                    }
                }
                $tracking=in_array($_POST['stock_tracking']??'', ['manual','recipe'],true)?$_POST['stock_tracking']:'manual';
                $revision=is_scalar($_POST['revision']??null)?(string)$_POST['revision']:'';
            }
        }
    }
} catch (Throwable $e) { http_response_code(503);$fatal=true;$error='Recipe management is unavailable. Please try again.'; }
$ingredientMap=array_column($ingredients,null,'id');
function snc_recipe_row(array $line,array $ingredients,array $ingredientMap): void {
    $id=(string)($line['ingredient_id']??'');$ingredient=$ingredientMap[$id]??null;
    $quantity=(string)($line['quantity']??'');
    if (preg_match('/^\d+(?:\.\d+)?$/D',$quantity)) $quantity=rtrim(rtrim(str_contains($quantity,'.')?$quantity:$quantity.'.000','0'),'.');
    ?>
    <div class="snc-recipe-row" data-recipe-row>
      <label>Ingredient<select name="ingredient_id[]" required><option value="">Choose an ingredient</option><?php foreach($ingredients as $entry): ?><option value="<?= (int)$entry['id'] ?>" data-unit="<?= snc_recipe_escape($entry['base_unit']) ?>" data-active="<?= (int)$entry['is_active'] ?>"<?= $id===(string)$entry['id']?' selected':'' ?>><?= snc_recipe_escape($entry['name']) ?><?= !(int)$entry['is_active']?' (Inactive)':'' ?></option><?php endforeach; ?></select></label>
      <label>Quantity per item<input name="quantity[]" type="number" min="<?= ($ingredient['base_unit']??'')==='pcs'?'1':'0.001' ?>" max="9999999.999" step="<?= ($ingredient['base_unit']??'')==='pcs'?'1':'0.001' ?>" value="<?= snc_recipe_escape($quantity) ?>" required></label>
      <div class="snc-recipe-unit"><span>Unit</span><strong data-recipe-unit><?= snc_recipe_escape($ingredient['base_unit']??'—') ?></strong></div>
      <button class="snc-menu-button" type="button" data-remove-ingredient>Remove</button>
      <p class="snc-menu-error snc-recipe-warning" data-ingredient-warning<?= !$ingredient|| (int)$ingredient['is_active']?' hidden':'' ?>>This ingredient is inactive. Replace it or activate it in Inventory before using this recipe.</p>
    </div>
    <?php
}
?>
<!DOCTYPE html><html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Recipe | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.2">
  <link rel="stylesheet" href="../assets/css/snc-menu.css?v=1.4">
  <link rel="stylesheet" href="../assets/css/snc-recipe.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-recipe.js?v=1.1" defer></script>
</head><body<?= $flash?' data-snc-flash="'.snc_recipe_escape(json_encode($flash)).'"':'' ?>>
<div class="snc-shell" id="snc-shell">
<?php $adminPage='menu'; require __DIR__.'/../includes/admin-shell.php'; ?>
<main class="snc-dashboard snc-recipe-workspace">
  <section class="snc-dashboard__header"><div><h1 class="snc-dashboard__title">Manage Recipe</h1><p class="snc-dashboard__subtitle">Set the ingredients needed to make one menu item.</p></div><a class="snc-menu-button" href="menu.php">Back to Menu</a></section>
  <?php if($fatal): ?><section class="snc-card"><p role="alert"><?= snc_recipe_escape($error) ?></p></section><?php else: ?>
  <section class="snc-card snc-recipe-product"><div><p class="snc-menu-hint"><?= snc_recipe_escape($item['category']) ?></p><h2><?= snc_recipe_escape($item['name']) ?></h2><p>Recipe quantities are for <strong>1 item</strong>.</p></div><div><span class="snc-menu-count"><?= count($lines)?'Recipe configured':'No recipe yet' ?></span><p class="snc-menu-hint">Menu price: ₱<?= number_format((float)$item['price'],2) ?></p></div></section>
  <section class="snc-card">
    <form method="post" action="recipe.php?item_id=<?= $itemId ?>" id="snc-recipe-form">
      <input type="hidden" name="csrf_token" value="<?= snc_recipe_escape($csrfToken) ?>"><input type="hidden" name="revision" value="<?= snc_recipe_escape($revision) ?>">
      <div class="snc-recipe-tracking"><label>Stock tracking<select name="stock_tracking" id="snc-stock-tracking"><option value="manual"<?= $tracking==='manual'?' selected':'' ?>>Manual tracking</option><option value="recipe"<?= $tracking==='recipe'?' selected':'' ?>>Deduct using recipe</option></select></label><p class="snc-menu-hint">Deduct using recipe subtracts these quantities for each item in a successfully saved paid order. Manual tracking leaves stock adjustments to staff.</p><p class="snc-menu-hint">Orders cannot be saved if a required ingredient is inactive or insufficient. Prepared ingredients are not automatically returned to stock.</p></div>
      <div class="snc-menu-results"><h2>Ingredients <span class="snc-menu-count" id="snc-recipe-count"><?= count($lines) ?></span></h2><button class="snc-menu-button" type="button" id="snc-add-recipe-ingredient"<?= !$ingredients?' disabled':'' ?>>Add Ingredient</button></div>
      <p class="snc-menu-hint">Enter quantities in each ingredient’s base unit: grams (g), milliliters (ml), or whole pieces (pcs).</p>
      <?php if($error): ?><p class="snc-menu-error" role="alert"><?= snc_recipe_escape($error) ?> <a href="recipe.php?item_id=<?= $itemId ?>">Reload Recipe</a></p><?php endif; ?>
      <div id="snc-recipe-lines"><?php foreach($lines as $line) snc_recipe_row($line,$ingredients,$ingredientMap); ?></div>
      <p class="snc-menu-hint" id="snc-recipe-empty"<?= $lines?' hidden':'' ?>>No ingredients in this recipe. Add ingredients above. Saving an empty recipe clears its ingredient links.</p>
      <?php if(!$ingredients): ?><p class="snc-menu-hint">Create active ingredients in <a href="inventory.php">Inventory</a> first.</p><?php endif; ?>
      <p class="snc-menu-error" id="snc-recipe-feedback" role="status"></p>
      <div class="snc-recipe-footer"><div><p class="snc-menu-hint">Recipe edits apply to future orders. Saving here does not change current stock or manual menu availability.</p><?php if($updated): ?><p class="snc-menu-hint">Last saved <?= snc_recipe_escape((new DateTimeImmutable($updated['updated_at']))->format('M j, Y · g:i A')) ?><?= $updated['full_name']?' by '.snc_recipe_escape($updated['full_name']):'' ?></p><?php endif; ?></div><div class="snc-menu-actions"><a class="snc-menu-button" href="menu.php">Cancel</a><button class="snc-menu-button snc-menu-button--primary" type="submit" id="snc-save-recipe">Save Recipe</button></div></div>
    </form>
    <template id="snc-recipe-row-template"><?php snc_recipe_row([],$ingredients,$ingredientMap); ?></template>
  </section>
  <?php endif; ?>
</main></div></div></body></html>
