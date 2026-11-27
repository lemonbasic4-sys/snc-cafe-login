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
