-- Update schema to fix missing columns for orders system
USE ybt_digital;

-- Fix order_items table - add missing quantity column
ALTER TABLE order_items ADD COLUMN quantity INT DEFAULT 1 AFTER product_id;

-- Fix orders table - add missing columns
ALTER TABLE orders 
ADD COLUMN status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending' AFTER payment_gateway,
ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0 AFTER total_amount,
ADD COLUMN shipping_cost DECIMAL(10, 2) DEFAULT 0 AFTER subtotal,
ADD COLUMN tax DECIMAL(10, 2) DEFAULT 0 AFTER shipping_cost,
ADD COLUMN payment_method VARCHAR(50) DEFAULT 'online' AFTER tax,
ADD COLUMN shipping_address TEXT AFTER payment_method,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Fix products table - add missing columns
ALTER TABLE products 
ADD COLUMN name VARCHAR(255) AFTER title,
ADD COLUMN stock INT DEFAULT 0 AFTER image_url,
CHANGE COLUMN title title VARCHAR(255),
CHANGE COLUMN image_url image VARCHAR(255),
CHANGE COLUMN file_path file VARCHAR(255);

-- Add phone and address to users table
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER password,
ADD COLUMN address TEXT AFTER phone;

-- Update products table to use name instead of title for consistency
UPDATE products SET name = title WHERE name IS NULL;

-- Add foreign key constraints for better data integrity
ALTER TABLE cart ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE cart ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE orders ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE order_items ADD FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;
ALTER TABLE order_items ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
