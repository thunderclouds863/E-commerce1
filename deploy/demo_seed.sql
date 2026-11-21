-- Akun & data demo (password role non-admin: demo123; admin: admin/admin123 hardcode di index.php)
USE ecommerce_db;
INSERT INTO users (username, password, role, status) VALUES
('customer1', '$2y$10$52hboDnXS8GD5GyOOao2AO6mH7qI2M0ROpfd4EL8tO.W5hBADvWv6', 'customer', 'actived'),
('employee1', '$2y$10$52hboDnXS8GD5GyOOao2AO6mH7qI2M0ROpfd4EL8tO.W5hBADvWv6', 'employee', 'actived'),
('courier1',  '$2y$10$52hboDnXS8GD5GyOOao2AO6mH7qI2M0ROpfd4EL8tO.W5hBADvWv6', 'courier',  'actived'),
('employee_baru', '$2y$10$52hboDnXS8GD5GyOOao2AO6mH7qI2M0ROpfd4EL8tO.W5hBADvWv6', 'employee', 'pending');
INSERT INTO products (id, name, description, price, stock, image) VALUES
(1, 'Kaos Polos Premium', 'Cotton combed 30s, nyaman dipakai harian', 85000, 40, 'uploads/produk1.png'),
(2, 'Kemeja Flanel', 'Flanel kotak-kotak, bahan tebal', 175000, 25, 'uploads/produk2.png'),
(3, 'Celana Chino', 'Slim fit, warna krem', 210000, 18, 'uploads/produk3.png'),
(4, 'Hoodie Basic', 'Fleece lembut dengan kantong depan', 230000, 15, 'uploads/produk4.png'),
(5, 'Topi Baseball', 'Adjustable strap', 60000, 50, 'uploads/produk5.png'),
(6, 'Tas Ransel', 'Muat laptop 14 inci', 320000, 10, 'uploads/produk6.png'),
(7, 'Sneakers Kanvas', 'Sol karet anti slip', 275000, 12, 'uploads/produk7.png');
INSERT INTO orders (id, user_id, total_amount, address, payment_code, payment_method, status) VALUES
(1, 1, 255000, 'Jl. Contoh No. 1, Semarang', 880012345678, 'bank_transfer', 'Selesai'),
(2, 1, 175000, 'Jl. Contoh No. 1, Semarang', 880012345679, 'bank_transfer', 'Dikemas'),
(3, 1, 60000,  'Jl. Contoh No. 1, Semarang', NULL, 'cash_on_delivery', 'awaiting payment');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 3, 85000), (2, 2, 1, 175000), (3, 5, 1, 60000);
