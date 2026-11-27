-- Additive inventory foundation. Does not change menu availability or deduct sales.
CREATE TABLE IF NOT EXISTS inventory_ingredients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  base_unit ENUM('g','ml','pcs') NOT NULL,
  current_stock DECIMAL(10,3) NOT NULL DEFAULT 0,
  low_stock_threshold DECIMAL(10,3) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_inventory_ingredient_name (name),
  KEY idx_inventory_active (is_active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ingredient_id INT UNSIGNED NOT NULL,
  ingredient_name VARCHAR(120) NOT NULL,
  base_unit ENUM('g','ml','pcs') NOT NULL,
  movement_type ENUM('opening','delivery','usage','waste','correction_in','correction_out') NOT NULL,
  quantity_delta DECIMAL(10,3) NOT NULL,
  balance_before DECIMAL(10,3) NOT NULL,
  balance_after DECIMAL(10,3) NOT NULL,
  reason VARCHAR(240) NOT NULL,
  actor_id INT UNSIGNED NULL,
  actor_name VARCHAR(120) NOT NULL,
  request_key CHAR(32) NOT NULL,
  request_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_inventory_request (request_key),
  KEY idx_inventory_movements (ingredient_id, id), KEY idx_inventory_movement_date (created_at),
  CONSTRAINT fk_inventory_movement_ingredient FOREIGN KEY (ingredient_id) REFERENCES inventory_ingredients(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inventory_movement_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
