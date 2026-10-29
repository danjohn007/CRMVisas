-- ============================================================
-- Migración: Ingresos adicionales para asesores
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `advisor_income_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `income_type` varchar(200) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_advisor_income_catalog_type` (`income_type`),
  KEY `idx_advisor_income_catalog_created_by` (`created_by`),
  KEY `idx_advisor_income_catalog_active` (`is_active`),
  CONSTRAINT `advisor_income_catalog_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advisor_income_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `income_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `income_datetime` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_advisor_income_records_type` (`income_type_id`),
  KEY `idx_advisor_income_records_date` (`income_datetime`),
  KEY `idx_advisor_income_records_created_by` (`created_by`),
  CONSTRAINT `advisor_income_records_ibfk_1` FOREIGN KEY (`income_type_id`) REFERENCES `advisor_income_catalog` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `advisor_income_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration add_advisor_incomes_module completed!' as status;
