-- Sample Data untuk D'Florist
-- Jalankan setelah database.sql berhasil diimport

-- Insert Categories (jika belum ada)
INSERT INTO categories (name, description) VALUES
('Small', 'Buket bunga ukuran kecil, cocok untuk hadiah personal'),
('Medium', 'Buket bunga ukuran sedang, pilihan populer untuk berbagai acara'),
('Big', 'Buket bunga ukuran besar, sempurna untuk acara spesial')
ON DUPLICATE KEY UPDATE name=name;

-- Insert Sample Products
INSERT INTO products (category_id, name, description, price, promo_price, is_promo, stock, is_active, image) VALUES
(1, 'Buket Mawar Merah Mini', 'Buket cantik berisi 5 tangkai mawar merah segar dengan wrapping elegan. Cocok untuk ungkapan cinta sederhana.', 75000, NULL, 0, 50, 1, NULL),
(1, 'Buket Tulip Pink', 'Buket manis berisi 7 tangkai tulip pink dengan baby breath. Sempurna untuk hadiah ulang tahun.', 85000, 70000, 1, 30, 1, NULL),
(2, 'Buket Mawar Campur', 'Kombinasi 12 tangkai mawar merah, pink, dan putih dengan greenery. Elegan dan romantis.', 150000, 135000, 1, 40, 1, NULL),
(2, 'Buket Lily Putih', 'Buket mewah berisi 8 tangkai lily putih dengan eucalyptus. Cocok untuk ucapan duka cita atau pernikahan.', 175000, NULL, 0, 25, 1, NULL),
(2, 'Buket Sunflower', 'Buket ceria berisi 10 tangkai bunga matahari dengan filler. Membawa kebahagiaan dan energi positif.', 140000, 125000, 1, 35, 1, NULL),
(3, 'Buket Mawar Merah Premium', 'Buket mewah berisi 24 tangkai mawar merah premium dengan wrapping luxury. Untuk momen spesial.', 300000, NULL, 0, 20, 1, NULL),
(3, 'Buket Mixed Flower Deluxe', 'Kombinasi premium berbagai jenis bunga: mawar, lily, gerbera, dan eustoma. Sangat mewah dan elegan.', 350000, 315000, 1, 15, 1, NULL),
(3, 'Buket Anniversary Special', 'Buket anniversary berisi 50 tangkai mawar merah dengan baby breath dan greenery. Unforgettable moment.', 500000, 450000, 1, 10, 1, NULL);

-- Insert Admin User
-- Email: dewishinta0128@gmail.com
-- Password: admin123
DELETE FROM users WHERE email = 'admin@dflorist.com' OR email = 'dewishinta0128@gmail.com';
INSERT INTO users (name, email, phone, password, role, is_verified) VALUES
('Administrator', 'dewishinta0128@gmail.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Insert Sample Customer (password: customer123)
INSERT INTO users (name, email, phone, password, role, is_verified) VALUES
('Budi Santoso', 'budi@example.com', '081234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1),
('Siti Nurhaliza', 'siti@example.com', '081234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1)
ON DUPLICATE KEY UPDATE email=email;

-- Insert Sample Address untuk customer
-- Koordinat toko: -6.200000, 106.816666 (Jakarta Pusat)
INSERT INTO addresses (user_id, label, recipient_name, recipient_phone, address, notes, latitude, longitude, is_primary) VALUES
(2, 'Rumah', 'Budi Santoso', '081234567891', 'Jl. Sudirman No. 123, Jakarta Pusat', 'Rumah warna putih, pagar hitam', -6.208763, 106.845599, 1),
(2, 'Kantor', 'Budi Santoso', '081234567891', 'Jl. Thamrin No. 45, Jakarta Pusat', 'Gedung Menara Thamrin Lt. 15', -6.195157, 106.822922, 0),
(3, 'Rumah', 'Siti Nurhaliza', '081234567892', 'Jl. Gatot Subroto No. 78, Jakarta Selatan', 'Komplek Perumahan Blok A No. 5', -6.225014, 106.830162, 1);

-- Insert Sample Reviews
INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES
(2, 1, 1, 5, 'Bunga sangat segar dan cantik! Packaging juga rapi. Pasti order lagi.'),
(2, 3, 1, 5, 'Kombinasi warnanya bagus banget, penerima sangat suka. Terima kasih D\'Florist!'),
(3, 2, 2, 4, 'Bunga bagus, tapi pengiriman agak telat 15 menit. Overall puas.'),
(3, 5, 2, 5, 'Sunflower-nya segar dan besar-besar. Bikin hari jadi cerah!');

-- Insert Sample Orders (untuk review)
INSERT INTO orders (user_id, order_number, address_id, delivery_method, delivery_date, delivery_time, subtotal, delivery_fee, total, payment_method, payment_status, order_status) VALUES
(2, 'DF20260101001', 1, 'Kurir Toko', '2026-01-15', '09.00 - 12.00', 225000, 15000, 240000, 'DANA', 'Lunas', 'Selesai'),
(3, 'DF20260102001', 3, 'Kurir Toko', '2026-01-16', '13.00 - 16.00', 210000, 15000, 225000, 'Bank Jago', 'Lunas', 'Selesai');

-- Insert Order Items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 75000),
(1, 3, 1, 150000),
(2, 2, 1, 85000),
(2, 5, 1, 125000);

-- Update Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'D\'Florist'),
('store_phone', '+62 812-3456-7890'),
('store_email', 'info@dflorist.com'),
('store_address', 'Jl. Bunga Raya No. 123, Jakarta Pusat'),
('store_latitude', '-6.200000'),
('store_longitude', '106.816666'),
('kurir_radius', '10'),
('max_quota_per_date', '5'),
('min_preorder_days', '2')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

SELECT 'Sample data berhasil diinsert!' as status;
