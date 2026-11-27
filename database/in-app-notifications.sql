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
