-- Hapus database lama jika perlu
DROP DATABASE IF EXISTS ecommerce_db;

-- Buat database baru
CREATE DATABASE ecommerce_db;

-- Gunakan database yang baru dibuat
USE ecommerce_db;

-- Tabel pengguna (users)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer', 'employee', 'courier') NOT NULL,
    status ENUM('actived', 'rejected', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel produk (products)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image BLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel pesanan (orders)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    address TEXT NOT NULL,
    payment_code BIGINT,
    payment_method ENUM('bank_transfer', 'credit_card', 'cash_on_delivery') NOT NULL,
    status ENUM('Pending', 'awaiting payment', 'paid', 'Dikonfirmasi', 'Dikemas', 'Siap Dikirim', 'Dikirim', 'Sampai Tujuan', 'Selesai') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel detail pesanan (order_items)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Trigger untuk memastikan status 'actived' untuk customer
DELIMITER $$
CREATE TRIGGER set_customer_status
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'customer' THEN
        SET NEW.status = 'actived';
    END IF;
END$$
DELIMITER ;
