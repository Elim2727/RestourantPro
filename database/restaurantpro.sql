-- RestaurantPro database setup
-- Safe to run more than once: existing tables and matching sample records are kept.

CREATE DATABASE IF NOT EXISTS restaurantpro_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE restaurantpro_db;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'waiter', 'kitchen', 'customer') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_name (full_name),
    INDEX idx_customers_email (email),
    INDEX idx_customers_phone (phone),
    CONSTRAINT fk_customers_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255) NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_menu_items_category (category_id),
    INDEX idx_menu_items_available (is_available),
    CONSTRAINT fk_menu_items_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(32) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    order_type ENUM('dine_in', 'takeaway', 'delivery') NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled')
        NOT NULL DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_created_by (created_by),
    INDEX idx_orders_status (status),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_orders_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_menu_item (menu_item_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu_item
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    method ENUM('cash', 'mobile_money', 'card') NOT NULL,
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    recorded_by BIGINT UNSIGNED NULL,
    transaction_reference VARCHAR(100) NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payments_status (status),
    INDEX idx_payments_recorded_by (recorded_by),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_payments_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add a few example categories without replacing existing categories.
INSERT INTO categories (name, description)
SELECT 'Starters', 'Small dishes served before the main meal'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Starters');

INSERT INTO categories (name, description)
SELECT 'Main Meals', 'Filling meals prepared by the kitchen'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Main Meals');

INSERT INTO categories (name, description)
SELECT 'Drinks', 'Cold and hot drinks'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Drinks');

-- Add example menu items only when an item with the same name is not present.
INSERT INTO menu_items (category_id, name, description, price, is_available)
SELECT categories.id, 'Tomato Soup', 'A simple bowl of tomato soup', 5.50, 1
FROM categories
WHERE categories.name = 'Starters'
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Tomato Soup');

INSERT INTO menu_items (category_id, name, description, price, is_available)
SELECT categories.id, 'Grilled Chicken', 'Grilled chicken served with rice', 14.00, 1
FROM categories
WHERE categories.name = 'Main Meals'
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Grilled Chicken');

INSERT INTO menu_items (category_id, name, description, price, is_available)
SELECT categories.id, 'Fresh Lemonade', 'Freshly prepared lemon drink', 3.50, 1
FROM categories
WHERE categories.name = 'Drinks'
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Fresh Lemonade');
