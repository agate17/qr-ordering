-- Demo DB for QR Table Ordering System
CREATE DATABASE IF NOT EXISTS restaurant_demo;
USE restaurant_demo;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

-- Example: Add a 'Specials' category
INSERT INTO categories (name) VALUES ('Specials');

-- Menu items
CREATE TABLE IF NOT EXISTS menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(6,2) NOT NULL,
  category_id INT,
  image_path VARCHAR(255),
  options TEXT,
  available BOOLEAN DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO menu_items (name, description, price, category_id, image_path, options, available) VALUES
('Margherita Pizza', 'Classic pizza with tomato, mozzarella, and basil.', 8.00, 1, NULL, NULL, 1),
('Spaghetti', 'Spaghetti pasta with homemade tomato sauce.', 9.00, NULL, NULL, NULL, 1),
('Caesar Salad', 'Crisp romaine lettuce with Caesar dressing.', 7.00, NULL, NULL, NULL, 1),
('Cola', 'Chilled soft drink.', 2.00, NULL, NULL, NULL, 1),
('Water', 'Bottled mineral water.', 1.50, NULL, NULL, NULL, 1);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  status ENUM('pending','preparing','done','paid') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  quantity INT NOT NULL,
  customizations TEXT,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
); 