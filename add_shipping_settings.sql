-- Add shipping cost settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('kurir_toko_cost', '10000'),
('ekspedisi_cost_per_kg', '10000'),
('ekspedisi_tier_1', '5000'),
('ekspedisi_tier_2', '10000'),
('ekspedisi_tier_3', '15000')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
