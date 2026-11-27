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
