<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
function snc_menu_json(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
$user = snc_current_user();
if (!$user) snc_menu_json(401, ['ok' => false, 'message' => 'Your session expired. Sign in again.']);
if (($user['role'] ?? '') !== 'admin') snc_menu_json(403, ['ok' => false, 'message' => 'Only the owner can manage the menu.']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    snc_menu_json(405, ['ok' => false, 'message' => 'Method not allowed.']);
}
if (!snc_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) snc_menu_json(403, ['ok' => false, 'message' => 'Session expired. Refresh and try again.']);
$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) snc_menu_json(400, ['ok' => false, 'message' => 'Invalid request.']);
$action = $data['action'] ?? '';
if (!in_array($action, ['save_item', 'availability', 'add_category', 'rename_category'], true)) snc_menu_json(422, ['ok' => false, 'message' => 'Invalid menu action.']);
$id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false || ($action === 'availability' && $id === 0) || ($action === 'rename_category' && $id === 0)) snc_menu_json(422, ['ok' => false, 'message' => 'Choose a valid item or category.']);
$name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
if ($action !== 'availability') {
    $maxLength = $action === 'save_item' ? 120 : 60;
    if ($name === '' || mb_strlen($name) > $maxLength || preg_match('/[\x00-\x1F\x7F]/u', $name)) snc_menu_json(422, ['ok' => false, 'message' => "Enter a name between 1 and {$maxLength} characters."]);
}
if (in_array($action, ['save_item', 'availability'], true) && !in_array($data['is_available'] ?? null, ['0', '1', 0, 1], true)) snc_menu_json(422, ['ok' => false, 'message' => 'Choose an availability.']);
if ($action === 'save_item') {
    $price = is_string($data['price'] ?? null) ? trim($data['price']) : '';
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) || (float) $price <= 0) snc_menu_json(422, ['ok' => false, 'message' => 'Enter a price above zero, with up to two decimal places (maximum ₱99,999,999.99).']);
    $categoryId = filter_var($data['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$categoryId) snc_menu_json(422, ['ok' => false, 'message' => 'Choose a category from the list.']);
}
$pdo = null;
try {
    $pdo = snc_get_db();
    $pdo->beginTransaction();
    $auditBefore=null;$auditAfter=null;$auditAction='';$auditType='menu_item';$auditTarget=$id;$auditName=$name;
    if ($action === 'add_category') {
        $stmt = $pdo->prepare('INSERT INTO menu_categories (name) VALUES (?)');
        $stmt->execute([$name]);
        $id = (int) $pdo->lastInsertId();
        $message = 'Category added.';
        $auditType='category';$auditTarget=$id;$auditAction='category_created';$auditAfter=['name'=>$name];
    } elseif ($action === 'rename_category') {
        $stmt = $pdo->prepare('SELECT name FROM menu_categories WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $oldName = $stmt->fetchColumn();
        if ($oldName === false) throw new RuntimeException('Category no longer exists. Refresh and try again.');
        $stmt = $pdo->prepare('UPDATE menu_categories SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
        $stmt = $pdo->prepare('UPDATE menu_items SET category = ? WHERE category = ?');
        $stmt->execute([$name, $oldName]);
        $message = 'Category renamed.';
        $auditType='category';$auditAction='category_renamed';$auditBefore=['name'=>$oldName];$auditAfter=['name'=>$name,'affected_items'=>$stmt->rowCount()];
    } elseif ($action === 'availability') {
        $stmt = $pdo->prepare('SELECT name,category,price,is_available FROM menu_items WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $oldItem=$stmt->fetch();if (!$oldItem) throw new RuntimeException('Item no longer exists. Refresh and try again.');
        $oldItem['is_available']=(int)$oldItem['is_available'];$auditBefore=$oldItem;
        $stmt = $pdo->prepare('UPDATE menu_items SET is_available = ? WHERE id = ?');
        $stmt->execute([(int) $data['is_available'], $id]);
        $message = (int) $data['is_available'] === 1 ? 'Item made available.' : 'Item made unavailable.';
        $auditAction='availability_changed';$auditName=$oldItem['name'];$auditAfter=array_replace($oldItem,['is_available'=>(int)$data['is_available']]);
    } else {
        $stmt = $pdo->prepare('SELECT name FROM menu_categories WHERE id = ? FOR UPDATE');
        $stmt->execute([$categoryId]);
        $category = $stmt->fetchColumn();
        if ($category === false) throw new RuntimeException('Category no longer exists. Refresh and try again.');
        $values = [$name, $category, $price, (int) $data['is_available']];
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT name,category,price,is_available FROM menu_items WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $oldItem=$stmt->fetch();if (!$oldItem) throw new RuntimeException('Item no longer exists. Refresh and try again.');
            $oldItem['is_available']=(int)$oldItem['is_available'];$auditBefore=$oldItem;
            $stmt = $pdo->prepare('UPDATE menu_items SET name = ?, category = ?, price = ?, is_available = ? WHERE id = ?');
            $stmt->execute([...$values, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO menu_items (name, category, price, is_available) VALUES (?, ?, ?, ?)');
            $stmt->execute($values);$auditTarget=(int)$pdo->lastInsertId();
        }
        $message = $id > 0 ? 'Menu item updated.' : 'Menu item added.';
        $auditAction=$id>0?'item_updated':'item_created';$auditAfter=['name'=>$name,'category'=>$category,'price'=>number_format((float)$price,2,'.',''),'is_available'=>(int)$data['is_available']];
    }
    if($action==='rename_category'&&$oldName===$name)$auditAfter=$auditBefore;
    snc_audit_write($pdo,$user,'menu',$auditAction,$auditType,$auditTarget,$auditName,$auditBefore,$auditAfter);
    if (in_array($action, ['add_category', 'rename_category'], true)) {
        $categories = $pdo->query('SELECT c.id, c.name, COUNT(m.id) AS item_count FROM menu_categories c LEFT JOIN menu_items m ON m.category = c.name GROUP BY c.id, c.name ORDER BY c.name')->fetchAll();
        $pdo->commit();
        snc_menu_json(200, ['ok' => true, 'message' => $message, 'categories' => $categories, 'category_id' => $id, 'old_name' => $oldName ?? null]);
    }
    $pdo->commit();
    snc_set_flash('success', $message, 'Menu Updated');
    snc_menu_json(200, ['ok' => true, 'message' => $message]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    if ($e instanceof PDOException && (string) $e->getCode() === '23000') snc_menu_json(409, ['ok' => false, 'message' => 'That name already exists. Use a different name.']);
    if ($e instanceof RuntimeException && !($e instanceof PDOException)) snc_menu_json(409, ['ok' => false, 'message' => $e->getMessage()]);
    snc_menu_json(503, ['ok' => false, 'message' => 'Unable to save the menu. Please try again.']);
}
