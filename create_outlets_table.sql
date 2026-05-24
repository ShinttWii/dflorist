-- Tabel untuk menyimpan data outlet/cabang toko
CREATE TABLE IF NOT EXISTS outlets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert outlet default (contoh)
INSERT INTO outlets (name, address, latitude, longitude, phone) VALUES
('D\'florist Pusat', 'Jl. Merdeka No. 123, Jakarta Pusat', -6.200000, 106.816666, '021-12345678');
