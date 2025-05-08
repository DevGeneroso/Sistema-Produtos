-- Create database
CREATE DATABASE IF NOT EXISTS product_management;
USE product_management;

-- Create users table with email field and password reset fields
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table with user_id column
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_code_per_user (code, user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password) VALUES 
('admin', 'admin@example.com', '$2y$10$zS.r6Ktzl3xDcPJ9dEc0c.Q5ItsPx51u6rvYhQL490ZcTwKMoYFHG');

-- Insert sample products for admin user (id=1)
INSERT INTO products (code, name, description, price, quantity, status, user_id) VALUES
('P001', 'Notebook Dell Inspiron', 'Notebook com processador Intel Core i5, 8GB RAM, 256GB SSD', 3499.99, 10, 1, 1),
('P002', 'Monitor LG 24"', 'Monitor LED Full HD com 24 polegadas', 899.99, 15, 1, 1),
('P003', 'Teclado Mecânico', 'Teclado mecânico com switches blue', 249.99, 20, 1, 1),
('P004', 'Mouse Sem Fio', 'Mouse sem fio com 1600 DPI', 89.99, 30, 1, 1),
('P005', 'Headset Gamer', 'Headset gamer com microfone e som surround', 199.99, 12, 1, 1),
('P006', 'Webcam HD', 'Webcam com resolução HD e microfone embutido', 149.99, 8, 1, 1),
('P007', 'Caixa de Som Bluetooth', 'Caixa de som portátil com conexão Bluetooth', 129.99, 5, 1, 1),
('P008', 'Pen Drive 32GB', 'Pen drive USB 3.0 com 32GB de capacidade', 49.99, 25, 1, 1),
('P009', 'HD Externo 1TB', 'HD externo portátil com 1TB de capacidade', 299.99, 7, 1, 1),
('P010', 'Carregador Portátil', 'Power bank com 10000mAh', 119.99, 0, 0, 1);
