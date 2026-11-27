-- SNC 2018 Café & Resto — Menu Items Seed
USE `snc_cafe_db`;

REPLACE INTO `menu_items` (`id`, `name`, `category`, `price`, `is_available`) VALUES
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
  (13, 'Tiramisu', 'Desserts', 170.00, 1);

INSERT IGNORE INTO menu_categories (name)
SELECT DISTINCT category FROM menu_items WHERE TRIM(category) <> '';
