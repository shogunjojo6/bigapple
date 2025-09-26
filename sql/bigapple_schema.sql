-- Big Apple Restaurant schema and seed data
CREATE DATABASE IF NOT EXISTS bigapple_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bigapple_db;

DROP TABLE IF EXISTS order_status_logs;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menus;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE restaurant_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL UNIQUE,
    capacity TINYINT DEFAULT 2,
    qr_code_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,
    table_id INT NOT NULL,
    customer_note TEXT,
    payment_method ENUM('cash','promptpay','pay_later') DEFAULT 'cash',
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    order_status ENUM('pending','preparing','ready','served') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    promptpay_slip_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id)
);

CREATE TABLE order_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending','preparing','ready','served','cancelled') NOT NULL,
    note VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Seed admin user (password: 12345)
INSERT INTO users (username, password_hash, role) VALUES
('admin', SHA2('12345', 256), 'admin');

-- Seed restaurant tables
INSERT INTO restaurant_tables (table_number, capacity) VALUES
('T1', 2),
('T2', 2),
('T3', 4),
('T4', 4),
('T5', 6);

-- Seed categories
INSERT INTO categories (name, description, display_order) VALUES
('อาหารไทย', 'อาหารไทยรสชาติเข้มข้น', 1),
('Breakfast', 'เมนูเช้าสไตล์ยุโรป', 2),
('Lunch', 'เมนูมื้อกลางวัน', 3),
('Dinner', 'เมนูมื้อเย็นพิเศษ', 4),
('Beverage', 'เครื่องดื่มเย็นและร้อน', 5);

-- Seed menus (2 items per category)
INSERT INTO menus (category_id, name, description, price, image_path, is_featured) VALUES
(1, 'ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำข้นสูตรดั้งเดิม', 180.00, 'assets/images/tomyum.svg', 1),
(1, 'ผัดไทยกุ้งสด', 'เส้นเหนียวนุ่ม ผัดซอสเข้มข้น', 150.00, 'assets/images/padthai.svg', 0),
(2, 'Eggs Benedict', 'ตอกไข่บนมัฟฟินกับซอสฮอลแลนเดซ', 220.00, 'assets/images/eggs-benedict.svg', 1),
(2, 'Granola Yogurt Bowl', 'โยเกิร์ตกับกราโนล่าและผลไม้สด', 160.00, 'assets/images/granola.svg', 0),
(3, 'Chicken Caesar Wrap', 'แรปรสเลิศไก่และซอสซีซาร์', 190.00, 'assets/images/caesar-wrap.svg', 0),
(3, 'Truffle Mushroom Risotto', 'ริซอตโต้เห็ดทรัฟเฟิลหอมกรุ่น', 260.00, 'assets/images/risotto.svg', 1),
(4, 'Grilled Salmon', 'แซลมอนย่างซอสเลมอนบัตเตอร์', 320.00, 'assets/images/grilled-salmon.svg', 1),
(4, 'Beef Wellington', 'ฟิเลต์เนื้อในพัฟเพสทรี', 420.00, 'assets/images/beef-wellington.svg', 1),
(5, 'Iced Americano', 'กาแฟอเมริกาโน่เย็น', 90.00, 'assets/images/iced-americano.svg', 0),
(5, 'Lychee Rosemary Fizz', 'น้ำลิ้นจี่ผสมโซดากลิ่นโรสแมรี่', 110.00, 'assets/images/lychee-fizz.svg', 0);

