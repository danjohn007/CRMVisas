-- Migration: Add login security and geo login configuration
-- Date: 2026-07-09

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `allowed_latitude` decimal(10,8) DEFAULT NULL,
  `allowed_longitude` decimal(11,8) DEFAULT NULL,
  `distance_meters` decimal(10,2) DEFAULT NULL,
  `location_status` varchar(50) DEFAULT NULL COMMENT 'not_required, missing, invalid, inside_range, outside_range',
  `user_agent` text,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_lookup` (`username`, `ip_address`, `attempted_at`),
  KEY `idx_login_attempts_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `login_attempts`
  ADD COLUMN IF NOT EXISTS `latitude` decimal(10,8) DEFAULT NULL AFTER `ip_address`,
  ADD COLUMN IF NOT EXISTS `longitude` decimal(11,8) DEFAULT NULL AFTER `latitude`,
  ADD COLUMN IF NOT EXISTS `allowed_latitude` decimal(10,8) DEFAULT NULL AFTER `longitude`,
  ADD COLUMN IF NOT EXISTS `allowed_longitude` decimal(11,8) DEFAULT NULL AFTER `allowed_latitude`,
  ADD COLUMN IF NOT EXISTS `distance_meters` decimal(10,2) DEFAULT NULL AFTER `allowed_longitude`,
  ADD COLUMN IF NOT EXISTS `location_status` varchar(50) DEFAULT NULL COMMENT 'not_required, missing, invalid, inside_range, outside_range' AFTER `distance_meters`;

INSERT INTO `global_config` (`config_key`, `config_value`, `config_type`) VALUES
('geo_login_enabled', '0', 'boolean'),
('geo_login_address', '', 'text'),
('geo_login_latitude', '', 'number'),
('geo_login_longitude', '', 'number'),
('geo_login_radius_meters', '100', 'number'),
('login_max_attempts', '5', 'number'),
('login_lockout_minutes', '15', 'number'),
('session_idle_timeout_minutes', '30', 'number')
ON DUPLICATE KEY UPDATE config_type = VALUES(config_type);
