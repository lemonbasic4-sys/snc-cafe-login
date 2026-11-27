-- Existing authentication, staff and stock ledgers remain the source for their events.
CREATE TABLE IF NOT EXISTS audit_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  module ENUM('menu','recipe','inventory','order','kitchen') NOT NULL,
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
