-- Starter products for testing the SNC Cafe system.
USE `snc-sample-jus10`;

INSERT INTO `menu_items` (`id`, `name`, `category`, `price`, `is_available`) VALUES
  (1, 'Iced Spanish Latte', 'Beverages', 160.00, 1),
  (2, 'Bistre Cold Brew', 'Beverages', 150.00, 1),
  (3, 'Caramel Macchiato', 'Beverages', 165.00, 1),
  (4, 'Matcha Oat Latte', 'Beverages', 175.00, 1),
  (5, 'Truffle Cream Pasta', 'Kitchen', 320.00, 1),
  (6, 'Crispy Chicken Katsu', 'Kitchen', 295.00, 1),
  (7, 'Beef Tapa Rice Bowl', 'Kitchen', 265.00, 1),
  (8, 'Butter Croissant', 'Pastries', 110.00, 1),
  (9, 'Chocolate Chip Cookie', 'Pastries', 80.00, 1),
  (10, 'Blueberry Muffin', 'Pastries', 95.00, 1),
  (11, 'Classic Cheesecake', 'Desserts', 150.00, 1),
  (12, 'Chocolate Lava Cake', 'Desserts', 180.00, 1),
  (13, 'Tiramisu', 'Desserts', 170.00, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `category` = VALUES(`category`),
  `price` = VALUES(`price`),
  `is_available` = VALUES(`is_available`);

INSERT IGNORE INTO `menu_categories` (`name`)
SELECT DISTINCT `category`
FROM `menu_items`
WHERE TRIM(`category`) <> '';

SELECT
  DATABASE() AS seeded_database,
  COUNT(*) AS total_products,
  SUM(`is_available` = 1) AS available_products
FROM `menu_items`;
