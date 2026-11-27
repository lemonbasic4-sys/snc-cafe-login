-- Additive migration: preserve menu items and historical order snapshots.
CREATE TABLE IF NOT EXISTS menu_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_menu_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO menu_categories (name)
SELECT DISTINCT category FROM menu_items WHERE TRIM(category) <> '';
