-- Migration to add stats and pricing fields to products
USE ybt_digital;

ALTER TABLE products 
ADD COLUMN old_price DECIMAL(10, 2) DEFAULT NULL AFTER price,
ADD COLUMN views INT DEFAULT 0 AFTER status,
ADD COLUMN downloads INT DEFAULT 0 AFTER views;

-- Update existing products with some dummy data for demonstration
UPDATE products SET old_price = price * 1.25, views = FLOOR(RAND() * 100), downloads = FLOOR(RAND() * 50) WHERE old_price IS NULL;
