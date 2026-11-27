-- Run after inventory.sql. Recipes describe quantities for one menu item.
CREATE TABLE IF NOT EXISTS menu_recipes (
  menu_item_id INT UNSIGNED NOT NULL,
  revision INT UNSIGNED NOT NULL DEFAULT 0,
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (menu_item_id),
  CONSTRAINT fk_recipe_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
  CONSTRAINT fk_recipe_actor FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_recipe_ingredients (
  menu_item_id INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  PRIMARY KEY (menu_item_id, ingredient_id),
  CONSTRAINT fk_recipe_line_recipe FOREIGN KEY (menu_item_id) REFERENCES menu_recipes(menu_item_id) ON DELETE RESTRICT,
  CONSTRAINT fk_recipe_line_ingredient FOREIGN KEY (ingredient_id) REFERENCES inventory_ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
