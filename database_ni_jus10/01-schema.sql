CREATE DATABASE IF NOT EXISTS `snc-sample-jus10`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `snc-sample-jus10`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `role` ENUM('admin', 'cashier', 'kitchen') NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `username_attempted` VARCHAR(60) NOT NULL,
  `portal_selected` ENUM('admin', 'staff') NULL DEFAULT NULL,
  `event_type` ENUM('login_success', 'login_failed', 'portal_mismatch', 'inactive_account', 'logout') NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auth_audit_user` (`user_id`),
  KEY `idx_auth_audit_created` (`created_at`),
  CONSTRAINT `fk_auth_audit_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `category` VARCHAR(60) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_items_name` (`name`),
  KEY `idx_menu_category_avail` (`category`, `is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additive migration: preserve menu items and historical order snapshots.
CREATE TABLE IF NOT EXISTS menu_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_menu_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO menu_categories (name)
SELECT DISTINCT category FROM menu_items WHERE TRIM(category) <> '';


CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(32) NOT NULL,
  `cashier_id` INT UNSIGNED NULL DEFAULT NULL,
  `order_type` ENUM('dine_in', 'takeout') NOT NULL DEFAULT 'dine_in',
  `table_or_note` VARCHAR(80) NOT NULL DEFAULT '',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('unpaid', 'paid', 'voided') NOT NULL DEFAULT 'paid',
  `kitchen_status` ENUM('pending', 'preparing', 'ready', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_kitchen_status` (`kitchen_status`, `created_at`),
  KEY `idx_orders_cashier_created` (`cashier_id`, `created_at`),
  CONSTRAINT `fk_orders_cashier`
    FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NULL DEFAULT NULL,
  `item_name` VARCHAR(120) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_menu`
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `method` ENUM('cash', 'gcash', 'card') NOT NULL DEFAULT 'cash',
  `amount_tendered` DECIMAL(10,2) NOT NULL,
  `change_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cashier_id` INT UNSIGNED NULL DEFAULT NULL,
  `paid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  CONSTRAINT `fk_payments_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_cashier`
    FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
  CONSTRAINT fk_order_requests_cashier FOREIGN KEY(cashier_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_request_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additive staff account concurrency and session revocation.
ALTER TABLE users ADD COLUMN IF NOT EXISTS staff_revision INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_version INT UNSIGNED NOT NULL DEFAULT 1;
CREATE TABLE IF NOT EXISTS staff_management_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id INT UNSIGNED NULL,
  staff_id INT UNSIGNED NULL,
  actor_name VARCHAR(120) NOT NULL,
  staff_name VARCHAR(120) NOT NULL,
  event_type ENUM('created','updated','activated','deactivated') NOT NULL,
  before_details TEXT NULL,
  after_details TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id), KEY idx_staff_log_target(staff_id,id),
  CONSTRAINT fk_staff_log_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_staff_log_target FOREIGN KEY(staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing authentication, staff and stock ledgers remain the source for their events.
CREATE TABLE IF NOT EXISTS audit_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  module ENUM('menu','recipe','inventory','order','kitchen','settings') NOT NULL,
  action VARCHAR(60) NOT NULL,
  actor_id INT UNSIGNED NULL,
  actor_name VARCHAR(120) NOT NULL,
  target_type VARCHAR(30) NOT NULL,
  target_id BIGINT UNSIGNED NULL,
  target_name VARCHAR(160) NOT NULL,
  before_details MEDIUMTEXT NULL,
  after_details MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id), KEY idx_audit_date(created_at,id), KEY idx_audit_actor(actor_id,created_at),
  KEY idx_audit_target(target_type,target_id),
  CONSTRAINT fk_audit_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit_recording_info (
  id TINYINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO audit_recording_info(id) VALUES(1);

-- Records the original void decision without deleting orders or payments.
CREATE TABLE IF NOT EXISTS order_voids (
  order_id BIGINT UNSIGNED NOT NULL,
  actor_id INT UNSIGNED NULL,
  actor_name VARCHAR(120) NOT NULL,
  reason VARCHAR(240) NOT NULL,
  kitchen_status ENUM('pending','preparing','ready','completed') NOT NULL,
  request_key CHAR(32) NOT NULL,
  voided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(order_id), UNIQUE KEY uq_void_request(request_key),
  CONSTRAINT fk_void_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_void_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One pending request per order; rejected requests remain in history.
CREATE TABLE IF NOT EXISTS order_void_requests (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 order_id BIGINT UNSIGNED NOT NULL,
 requester_id INT UNSIGNED NULL,
 requester_name VARCHAR(120) NOT NULL,
 reason VARCHAR(240) NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 request_key CHAR(32) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewer_id INT UNSIGNED NULL,
 reviewer_name VARCHAR(120) NULL,
 decision_reason VARCHAR(240) NULL,
 reviewed_at DATETIME NULL,
 pending_order_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='pending' THEN order_id ELSE NULL END) STORED,
 PRIMARY KEY(id), UNIQUE KEY uq_void_request_submission(request_key),
 UNIQUE KEY uq_pending_void_order(pending_order_id), KEY idx_void_requests_status(status,created_at),
 CONSTRAINT fk_void_request_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT fk_order_void_requests_cashier FOREIGN KEY(requester_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_void_request_owner FOREIGN KEY(reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS in_app_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 recipient_id INT UNSIGNED NOT NULL,
 recipient_role ENUM('admin','cashier') NOT NULL,
 request_id BIGINT UNSIGNED NOT NULL,
 event_type ENUM('void_requested','void_approved','void_rejected') NOT NULL,
 title VARCHAR(120) NOT NULL,
 message VARCHAR(500) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 read_at DATETIME NULL,
 PRIMARY KEY(id), UNIQUE KEY uq_notification_delivery(recipient_id,request_id,event_type),
 KEY idx_notification_inbox(recipient_id,recipient_role,read_at,id),
 CONSTRAINT fk_notification_void_request FOREIGN KEY(request_id) REFERENCES order_void_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Recipient IDs deliberately have no user FK: delivering to an owner must not
-- lock that owner's account while a cashier holds an order lock.

CREATE TABLE IF NOT EXISTS system_settings (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 business_name VARCHAR(120) NOT NULL DEFAULT 'SNC 2018',
 business_address VARCHAR(240) NOT NULL DEFAULT '',
 contact_number VARCHAR(80) NOT NULL DEFAULT '',
 receipt_header VARCHAR(240) NOT NULL DEFAULT 'CAFÉ & RESTO',
 receipt_footer VARCHAR(240) NOT NULL DEFAULT 'Thank you for dining with us!',
 paper_width ENUM('58','80') NOT NULL DEFAULT '80',
 cash_enabled TINYINT(1) NOT NULL DEFAULT 1,
 gcash_enabled TINYINT(1) NOT NULL DEFAULT 1,
 card_enabled TINYINT(1) NOT NULL DEFAULT 1,
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT chk_single_settings CHECK(id=1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO system_settings(id) VALUES(1);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_profile TEXT NULL;
ALTER TABLE audit_events MODIFY COLUMN module ENUM('menu','recipe','inventory','order','kitchen','settings') NOT NULL;
