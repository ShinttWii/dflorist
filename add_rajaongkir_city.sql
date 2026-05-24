-- Tambah kolom city_id untuk RajaOngkir di tabel addresses
ALTER TABLE addresses ADD COLUMN IF NOT EXISTS city_id INT NULL AFTER longitude;
ALTER TABLE addresses ADD COLUMN IF NOT EXISTS city_name VARCHAR(100) NULL AFTER city_id;

-- Tambah kolom city_id untuk RajaOngkir di tabel outlets
ALTER TABLE outlets ADD COLUMN IF NOT EXISTS city_id INT NULL AFTER longitude;
ALTER TABLE outlets ADD COLUMN IF NOT EXISTS city_name VARCHAR(100) NULL AFTER city_id;

-- Tambah kolom untuk menyimpan kurir & service yang dipilih di orders
ALTER TABLE orders ADD COLUMN IF NOT EXISTS courier VARCHAR(20) NULL AFTER delivery_method;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS courier_service VARCHAR(50) NULL AFTER courier;

-- Tambah setting RajaOngkir API key
INSERT INTO settings (setting_key, setting_value) 
VALUES ('rajaongkir_api_key', '') 
ON DUPLICATE KEY UPDATE setting_value = setting_value;
