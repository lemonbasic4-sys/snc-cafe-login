<?php
declare(strict_types=1);
require_once __DIR__.'/inventory.php';
require_once __DIR__.'/audit.php';

/** Replaces one complete recipe; never changes ingredient balances or past orders. */
function snc_recipe_save(PDO $pdo, array $user, int $itemId, mixed $revision, mixed $ids, mixed $quantities, mixed $tracking = 'manual'): void
{
    if (!in_array($tracking,['manual','recipe'],true)) throw new InvalidArgumentException('Choose a stock tracking setting.');
    $revision = filter_var($revision, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
    if ($revision === false || !is_array($ids) || !is_array($quantities) || !array_is_list($ids) || !array_is_list($quantities) || count($ids)!==count($quantities) || count($ids)>100) {
        throw new InvalidArgumentException('Enter a valid recipe with up to 100 ingredients.');
    }
    $lines=[];
    foreach ($ids as $index=>$id) {
        $id=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new InvalidArgumentException('Choose an ingredient for every row, or remove the unused row.');
        if (isset($lines[$id])) throw new InvalidArgumentException('Each ingredient can appear only once. Combine its quantities into one row.');
        $quantity=snc_inventory_parse($quantities[$index]);
        if ($quantity<=0) throw new InvalidArgumentException('Each ingredient quantity must be above zero.');
        $lines[$id]=$quantity;
    }
    ksort($lines); // Stable ingredient lock ordering.
    if ($tracking==='recipe'&&!$lines) throw new InvalidArgumentException('Add at least one ingredient before enabling automatic deduction, or choose Manual tracking.');
    $owns=!$pdo->inTransaction();
    if ($owns) $pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT id,name FROM menu_items WHERE id=? FOR UPDATE');
        $stmt->execute([$itemId]);
        $menuItem=$stmt->fetch();if (!$menuItem) throw new RuntimeException('Menu item no longer exists.',409);
        $stmt=$pdo->prepare('SELECT revision,stock_tracking FROM menu_recipes WHERE menu_item_id=? FOR UPDATE');
        $stmt->execute([$itemId]);$oldRecipe=$stmt->fetch();$saved=$oldRecipe?$oldRecipe['revision']:false;
        if (($saved===false?0:(int)$saved)!==$revision) throw new RuntimeException('This recipe changed since you opened it. Reload the page and review the latest recipe before saving.',409);
        $stmt=$pdo->prepare('SELECT ingredient_id FROM menu_recipe_ingredients WHERE menu_item_id=?');
        $stmt->execute([$itemId]);$previous=$stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt=$pdo->prepare('SELECT i.name,i.base_unit AS unit,r.quantity FROM menu_recipe_ingredients r JOIN inventory_ingredients i ON i.id=r.ingredient_id WHERE r.menu_item_id=? ORDER BY r.ingredient_id');
        $stmt->execute([$itemId]);$previousParts=$stmt->fetchAll();$nextParts=[];
        foreach ($lines as $id=>$quantity) {
            $stmt=$pdo->prepare('SELECT name,base_unit,is_active FROM inventory_ingredients WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);$ingredient=$stmt->fetch();
            if (!$ingredient) throw new InvalidArgumentException('An ingredient no longer exists. Reload the page to refresh the list.');
            if (!(int)$ingredient['is_active'] && !in_array($id,array_map('intval',$previous),true)) throw new InvalidArgumentException('Only active ingredients can be added to a recipe.');
            if ($tracking==='recipe'&&!(int)$ingredient['is_active']) throw new InvalidArgumentException('Activate or remove '.$ingredient['name'].' before enabling automatic deduction.');
            if ($ingredient['base_unit']==='pcs' && $quantity%1000) throw new InvalidArgumentException($ingredient['name'].' must use whole pieces.');
            $nextParts[]=['name'=>$ingredient['name'],'unit'=>$ingredient['base_unit'],'quantity'=>snc_inventory_decimal($quantity)];
        }
        if ($saved===false) {
            $stmt=$pdo->prepare('INSERT INTO menu_recipes(menu_item_id,revision,updated_by,updated_at,stock_tracking) VALUES(?,1,?,NOW(),?)');
            $stmt->execute([$itemId,$user['id'],$tracking]);
        } else {
            $stmt=$pdo->prepare('UPDATE menu_recipes SET revision=revision+1,updated_by=?,updated_at=NOW(),stock_tracking=? WHERE menu_item_id=?');
            $stmt->execute([$user['id'],$tracking,$itemId]);
        }
        $pdo->prepare('DELETE FROM menu_recipe_ingredients WHERE menu_item_id=?')->execute([$itemId]);
        $stmt=$pdo->prepare('INSERT INTO menu_recipe_ingredients(menu_item_id,ingredient_id,quantity) VALUES(?,?,?)');
        foreach ($lines as $id=>$quantity) $stmt->execute([$itemId,$id,snc_inventory_decimal($quantity)]);
        snc_audit_write($pdo,$user,'recipe',$lines?'saved':'cleared','recipe',$itemId,$menuItem['name'],$oldRecipe?['stock_tracking'=>$oldRecipe['stock_tracking'],'ingredients'=>$previousParts]:null,['stock_tracking'=>$tracking,'ingredients'=>$nextParts]);
        if ($owns) $pdo->commit();
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
