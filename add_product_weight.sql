-- Add weight column to products table
ALTER TABLE `products` ADD COLUMN `weight` DECIMAL(10,2) DEFAULT 0.5 COMMENT 'Berat produk dalam kg' AFTER `stock`;

-- Update existing products with default weight 0.5 kg
UPDATE `products` SET `weight` = 0.5 WHERE `weight` = 0 OR `weight` IS NULL;
