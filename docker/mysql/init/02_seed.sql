USE `pos`;

-- Seed user (username: staff, password: password)
INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
('staff', '1234', 'staff')
ON DUPLICATE KEY UPDATE username = username;

-- Seed products
INSERT INTO `products` (`name`, `price`, `stock`) VALUES
('water 600ml', 10.00, 100),
('coca-cola', 18.00, 80),
('Green Tea', 25.00, 60),
('Bread', 15.00, 120),
('Cake', 12.00, 150),
('Chocolate', 20.00, 90),
('Coffee', 22.00, 70),
('Milk', 18.00, 110);

-- Example sale with items
INSERT INTO `sales` (`sale_date`, `total_amount`) VALUES (NOW(), 10.00 + 18.00);
SET @last_sale_id = LAST_INSERT_ID();
INSERT INTO `sale_items` (`sale_id`, `product_id`, `quantity`, `price`) VALUES
(@last_sale_id, 1, 1, 10.00), -- น้ำดื่ม
(@last_sale_id, 2, 1, 18.00); -- โค้ก

-- Reduce stock accordingly for the example sale
UPDATE `products` SET `stock` = `stock` - 1 WHERE `id` IN (1,2);

