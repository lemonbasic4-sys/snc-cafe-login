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
