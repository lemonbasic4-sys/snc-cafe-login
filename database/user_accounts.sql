USE `snc_cafe_db`;

REPLACE INTO `users` (`username`, `full_name`, `role`, `password_hash`, `is_active`) VALUES
  ('admin', 'SNC Business Owner', 'admin', '$2y$10$WAHudk2/Z40OEsojXdLUIudaEOaQmXauFIjRoRYP.55Wyp4gjBjDS', 1),
  ('cashier1', 'Front Cashier', 'cashier', '$2y$10$E0HOvD/eip0ZYKpL/2sZg.AL22Hz8IqlOjxw8LCmCFCMQnP/kxRly', 1),
  ('kitchen1', 'Kitchen Lead', 'kitchen', '$2y$10$xvBR67ScCJ.0hSDUynscJuDXKKb4tXSkKQC6VnpSphd1D6EaEfrLG', 1);