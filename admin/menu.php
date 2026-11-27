<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$user = snc_require_auth(['admin']);
$csrfToken = snc_csrf_token();
$flash = snc_pop_flash();
header('Cache-Control: no-store');
function snc_menu_escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
$search = is_string($_GET['search'] ?? null) ? trim(substr($_GET['search'], 0, 120)) : '';
$category = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
$availability = in_array($_GET['availability'] ?? '', ['0', '1'], true) ? $_GET['availability'] : '';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$items = $categories = [];
$error = '';
$total = 0;
$pages = 1;
try {
    $pdo = snc_get_db();
    $categories = $pdo->query('SELECT c.id, c.name, COUNT(m.id) AS item_count FROM menu_categories c LEFT JOIN menu_items m ON m.category = c.name GROUP BY c.id, c.name ORDER BY c.name')->fetchAll();
    $conditions = [];
    $params = [];
    if ($search !== '') { $conditions[] = 'LOCATE(?, name) > 0'; $params[] = $search; }
    if ($category !== '') { $conditions[] = 'category = ?'; $params[] = $category; }
    if ($availability !== '') { $conditions[] = 'is_available = ?'; $params[] = (int) $availability; }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items' . $where);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    $pages = max(1, (int) ceil($total / 25));
    $page = min($page, $pages);
    $offset = ($page - 1) * 25;
    $stmt = $pdo->prepare("SELECT id, name, category, price, is_available, (SELECT COUNT(*) FROM menu_recipe_ingredients r WHERE r.menu_item_id=menu_items.id) AS recipe_count, COALESCE((SELECT stock_tracking FROM menu_recipes r WHERE r.menu_item_id=menu_items.id),'manual') AS stock_tracking FROM menu_items{$where} ORDER BY category, name LIMIT 25 OFFSET {$offset}");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
} catch (Throwable $e) { http_response_code(503); $error = 'Menu management is unavailable. Please try again.'; }
$filters = ['search' => $search, 'category' => $category, 'availability' => $availability];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Menu Management | SNC 2018 Café &amp; Resto</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/snc-dreamcatcher-emblem.svg">
  <link rel="stylesheet" href="../assets/css/snc-tokens.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-navigation.css?v=1.1">
  <link rel="stylesheet" href="../assets/css/snc-dashboard.css?v=1.0">
  <link rel="stylesheet" href="../assets/css/snc-menu.css?v=1.4">
  <link rel="stylesheet" href="../assets/css/snc-notifications.css?v=1.0">
  <script src="../assets/js/snc-notifications.js?v=1.0" defer></script>
  <script src="../assets/js/snc-navigation.js?v=1.0" defer></script>
  <script src="../assets/js/snc-menu.js?v=1.1" defer></script>
</head>
<body<?= $flash ? ' data-snc-flash="' . snc_menu_escape(json_encode($flash)) . '"' : '' ?>>
  <div class="snc-shell" id="snc-shell">
    <?php $adminPage = 'menu'; require __DIR__ . '/../includes/admin-shell.php'; ?>
    <main class="snc-dashboard snc-menu-workspace">
      <section class="snc-dashboard__header">
        <div><h1 class="snc-dashboard__title">Menu Management</h1><p class="snc-dashboard__subtitle">Manage products, prices, and availability on the cashier POS.</p></div>
        <?php if (!$error): ?><div class="snc-menu-actions"><button class="snc-menu-button" id="snc-manage-categories" type="button">Manage Categories</button><button class="snc-menu-button snc-menu-button--primary" id="snc-add-item" type="button">Add Item</button></div><?php endif; ?>
      </section>
      <section class="snc-card snc-menu-panel">
        <?php if ($error): ?><p role="alert"><?= snc_menu_escape($error) ?></p><?php else: ?>
        <form class="snc-menu-filters" method="get" action="menu.php">
          <label>Search items<input name="search" maxlength="120" value="<?= snc_menu_escape($search) ?>" placeholder="Search by item name"></label>
          <label>Category<select name="category"><option value="">All Categories</option><?php foreach ($categories as $cat): ?><option value="<?= snc_menu_escape($cat['name']) ?>"<?= $category === $cat['name'] ? ' selected' : '' ?>><?= snc_menu_escape($cat['name']) ?></option><?php endforeach; ?></select></label>
          <label>Availability<select name="availability"><option value="">All Items</option><option value="1"<?= $availability === '1' ? ' selected' : '' ?>>Available</option><option value="0"<?= $availability === '0' ? ' selected' : '' ?>>Unavailable</option></select></label>
          <button class="snc-menu-button" type="submit">Search</button><a class="snc-menu-button" href="menu.php">Reset</a>
        </form>
        <div class="snc-menu-results"><h2>Menu Items <span class="snc-menu-count"><?= $total ?></span></h2><span>Page <?= $page ?> of <?= $pages ?></span></div>
        <div class="snc-table-wrap" role="region" aria-label="Menu items" tabindex="0">
          <table class="snc-table snc-menu-table">
            <thead><tr><th>Item Name</th><th>Category</th><th>Price</th><th>Availability</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($items as $item): ?>
              <tr><td><?= snc_menu_escape($item['name']) ?><br><small class="snc-menu-hint"><?= (int)$item['recipe_count'] ? (int)$item['recipe_count'].' recipe ingredient'.((int)$item['recipe_count']===1?'':'s') : 'No recipe yet' ?> · <?= $item['stock_tracking']==='recipe'?'Automatic deduction':'Manual tracking' ?></small></td><td><?= snc_menu_escape($item['category']) ?></td><td>₱<?= number_format((float) $item['price'], 2) ?></td><td><span class="snc-menu-status<?= (int) $item['is_available'] ? ' is-available' : '' ?>"><?= (int) $item['is_available'] ? 'Available' : 'Unavailable' ?></span></td><td><div class="snc-menu-actions">
                <button class="snc-menu-button" type="button" data-edit-item="<?= snc_menu_escape(json_encode($item)) ?>" aria-label="Edit <?= snc_menu_escape($item['name']) ?>">Edit</button>
                <a class="snc-menu-button" href="recipe.php?item_id=<?= (int)$item['id'] ?>" aria-label="Manage recipe for <?= snc_menu_escape($item['name']) ?>">Manage Recipe</a>
                <form data-menu-save><input type="hidden" name="action" value="availability"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="is_available" value="<?= (int) $item['is_available'] ? '0' : '1' ?>"><button class="snc-menu-button" type="submit" aria-label="<?= (int) $item['is_available'] ? 'Make unavailable: ' : 'Make available: ' ?><?= snc_menu_escape($item['name']) ?>"><?= (int) $item['is_available'] ? 'Make Unavailable' : 'Make Available' ?></button><span class="snc-menu-error" role="status" data-form-message></span></form>
              </div></td></tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="5"><?= $search !== '' || $category !== '' || $availability !== '' ? 'No items match your filters.' : 'No menu items yet. Add your first item to get started.' ?></td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="snc-menu-pagination"><?php if ($page > 1): ?><a class="snc-menu-button" href="?<?= snc_menu_escape(http_build_query($filters + ['page' => $page - 1])) ?>">Previous</a><?php endif; ?><?php if ($page < $pages): ?><a class="snc-menu-button" href="?<?= snc_menu_escape(http_build_query($filters + ['page' => $page + 1])) ?>">Next</a><?php endif; ?></div>
        <p class="snc-menu-hint snc-menu-note">Availability is set manually. Changes apply to future orders; past orders retain their recorded names and prices.</p>
        <?php endif; ?>
      </section>
    </main>
    </div>
  </div>
  <input type="hidden" id="snc-menu-csrf" value="<?= snc_menu_escape($csrfToken) ?>">
  <dialog class="snc-menu-dialog" id="snc-item-dialog" aria-labelledby="snc-item-title">
    <div class="snc-menu-dialog__head"><div><h2 id="snc-item-title">Add Item</h2><p class="snc-menu-hint">Set the product details shown on your menu.</p></div><button class="snc-menu-button" type="button" data-close-dialog>Close</button></div>
    <form data-menu-save id="snc-item-form">
      <input type="hidden" name="action" value="save_item"><input type="hidden" name="id" value="0">
      <div class="snc-item-fields"><label class="snc-item-fields__wide">Item name<input name="name" maxlength="120" required placeholder="e.g. Iced Spanish Latte"></label>
      <label class="snc-item-fields__wide">Category<select name="category_id" required><option value="">Choose a category</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>" data-category-name="<?= snc_menu_escape($cat['name']) ?>"><?= snc_menu_escape($cat['name']) ?></option><?php endforeach; ?></select></label>
      <?php if (!$categories): ?><p class="snc-menu-hint">Add a category using Manage Categories first.</p><?php endif; ?>
      <label>Price (₱)<input name="price" type="number" min="0.01" max="99999999.99" step="0.01" required placeholder="0.00"></label>
      <label>Availability<select name="is_available"><option value="1">Available</option><option value="0">Unavailable</option></select></label></div>
      <p class="snc-menu-error" role="status" data-form-message></p>
      <div class="snc-menu-actions"><button class="snc-menu-button" type="button" data-close-dialog>Cancel</button><button class="snc-menu-button snc-menu-button--primary" type="submit">Save Item</button></div>
    </form>
  </dialog>
  <dialog class="snc-menu-dialog snc-category-dialog" id="snc-category-dialog" aria-labelledby="snc-category-title">
    <div class="snc-menu-dialog__head"><div><h2 id="snc-category-title">Manage Categories</h2><p class="snc-menu-hint">Keep your menu organized and easy to browse.</p></div><button class="snc-menu-button" type="button" data-close-dialog>Close</button></div>
    <form data-menu-save class="snc-category-row snc-category-row--new"><input type="hidden" name="action" value="add_category"><label>New category<input name="name" maxlength="60" required placeholder="Category name"></label><button class="snc-menu-button snc-menu-button--primary" type="submit">Add Category</button><span class="snc-menu-error" role="status" data-form-message></span></form>
    <p class="snc-category-feedback" id="snc-category-feedback" role="status"></p>
    <div class="snc-menu-results"><h3>Existing Categories</h3><span id="snc-category-total"><?= count($categories) ?> total</span></div>
    <div class="snc-category-list" role="region" aria-label="Existing categories" tabindex="0">
      <div class="snc-category-columns" aria-hidden="true"><span>Category name</span><span>Items</span><span>Action</span></div>
      <?php foreach ($categories as $cat): ?>
      <form data-menu-save class="snc-category-row snc-category-row--existing">
        <input type="hidden" name="action" value="rename_category"><input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
        <input name="name" maxlength="60" required value="<?= snc_menu_escape($cat['name']) ?>" aria-label="Category name: <?= snc_menu_escape($cat['name']) ?>">
        <span class="snc-menu-count" aria-label="<?= (int) $cat['item_count'] ?> items in <?= snc_menu_escape($cat['name']) ?>"><?= (int) $cat['item_count'] ?></span>
        <button class="snc-menu-button" type="submit" aria-label="Rename <?= snc_menu_escape($cat['name']) ?>">Rename</button>
        <span class="snc-menu-error" role="status" data-form-message></span>
      </form>
      <?php endforeach; ?>
      <?php if (!$categories): ?><p class="snc-menu-hint">No categories yet. Add your first category above.</p><?php endif; ?>
    </div>
    <p class="snc-menu-hint snc-menu-note">Category renames apply to menu items. Past orders stay unchanged.</p>
  </dialog>
</body>
</html>
