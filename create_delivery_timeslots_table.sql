-- Create delivery_timeslots table
CREATE TABLE IF NOT EXISTS `delivery_timeslots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_slot` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default time slots
INSERT INTO `delivery_timeslots` (`time_slot`, `is_active`, `sort_order`) VALUES
('09.00 - 12.00 WIB', 1, 1),
('12.00 - 15.00 WIB', 1, 2),
('15.00 - 18.00 WIB', 1, 3);
