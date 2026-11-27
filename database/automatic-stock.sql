-- Run after inventory.sql and recipes.sql. Existing recipes stay manual.
ALTER TABLE menu_recipes ADD COLUMN IF NOT EXISTS stock_tracking ENUM('manual','recipe') NOT NULL DEFAULT 'manual';
ALTER TABLE inventory_movements MODIFY movement_type ENUM('opening','delivery','usage','waste','correction_in','correction_out','sale') NOT NULL;
ALTER TABLE inventory_movements ADD COLUMN IF NOT EXISTS order_id BIGINT UNSIGNED NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_inventory_order_ingredient ON inventory_movements(order_id,ingredient_id);
-- An order reference is retained even if its recipe is subsequently edited.
CREATE TABLE IF NOT EXISTS order_recipe_usage (
  order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(120) NOT NULL,
  quantity_sold INT UNSIGNED NOT NULL,
  recipe_revision INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NOT NULL,
  base_unit ENUM('g','ml','pcs') NOT NULL,
  quantity_per_item DECIMAL(10,3) NOT NULL,
  quantity_used DECIMAL(10,3) NOT NULL,
  PRIMARY KEY(order_id,menu_item_id,ingredient_id),
  CONSTRAINT fk_recipe_usage_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_recipe_usage_ingredient FOREIGN KEY(ingredient_id) REFERENCES inventory_ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS order_requests (
  request_key CHAR(32) NOT NULL,
  request_hash CHAR(64) NOT NULL,
  cashier_id INT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(request_key), UNIQUE KEY uq_request_order(order_id),
  CONSTRAINT fk_request_cashier FOREIGN KEY(cashier_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_request_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
