-- Update payment_method ENUM to support midtrans
ALTER TABLE orders MODIFY COLUMN payment_method ENUM('midtrans', 'cod') NOT NULL;
