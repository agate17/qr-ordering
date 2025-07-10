-- Demo DB for QR Table Ordering System
CREATE DATABASE IF NOT EXISTS restaurant_demo;
USE restaurant_demo;

-- Menu items
CREATE TABLE IF NOT EXISTS menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(6,2) NOT NULL
);

INSERT INTO menu_items (name, price) VALUES
('Margherita Pizza', 8.00),
('Spaghetti', 9.00),
('Caesar Salad', 7.00),
('Cola', 2.00),
('Water', 1.50);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  status ENUM('pending','prepared','paid') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
); 