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
 CONSTRAINT fk_void_request_cashier FOREIGN KEY(requester_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_void_request_owner FOREIGN KEY(reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
