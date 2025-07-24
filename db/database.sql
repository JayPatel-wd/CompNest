-- Create database
CREATE DATABASE IF NOT EXISTS computer_store;
USE computer_store;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


-- Trigger to automatically format image paths on INSERT
DELIMITER //
CREATE TRIGGER format_image_path_insert
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    -- Check if image_url is not null and doesn't already start with 'images/'
    IF NEW.image_url IS NOT NULL AND NEW.image_url NOT LIKE 'images/%' THEN
        SET NEW.image_url = CONCAT('images/', NEW.image_url);
    END IF;
END//

-- Trigger to automatically format image paths on UPDATE
CREATE TRIGGER format_image_path_update
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
    -- Check if image_url is not null and doesn't already start with 'images/'
    IF NEW.image_url IS NOT NULL AND NEW.image_url NOT LIKE 'images/%' THEN
        SET NEW.image_url = CONCAT('images/', NEW.image_url);
    END IF;
END//
DELIMITER ;

-- Insert sample products
INSERT INTO products (name, description, price, image_url, category, stock) VALUES
('Gaming Laptop Pro', 'High-performance gaming laptop with RTX 4080', 999.99, 'images/laptop1.jpeg', 'laptops', 15),
('Business Desktop', 'Reliable desktop for office work', 899.99, 'images/desktop1.jpg', 'desktops', 25),
('RTX 4090 Graphics Card', 'Top-tier graphics card for gaming', 599.99, 'images/gpu1.jpg', 'graphic_cards', 8),
('32GB DDR5 RAM', 'High-speed memory for gaming and productivity', 299.99, 'images/ram1.jpg', 'memories', 50),
('Gaming Keyboard RGB', 'Mechanical keyboard with RGB lighting', 149.99, 'images/keyboard1.jpg', 'accessories', 30),
('4K Gaming Monitor', '27-inch 4K monitor with 144Hz refresh rate', 699.99, 'images/monitor1.jpg', 'accessories', 20),
('SSD 1TB NVMe', 'Fast storage solution for quick boot times', 129.99, 'images/ssd1.jpg', 'storage', 40),
('Gaming Mouse Pro', 'High-precision gaming mouse', 79.99, 'images/mouse1.jpg', 'accessories', 35);

