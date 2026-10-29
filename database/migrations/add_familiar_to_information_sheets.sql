-- Migration: Add information_sheet_familiar table for family member info
-- This table stores family-member data linked to an information sheet (one sheet can have multiple family members).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `information_sheet_familiar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `information_sheet_id` int(11) NOT NULL,
  `entry_date` date DEFAULT NULL COMMENT 'Fecha de ingreso del familiar',
  `nombre_completo` varchar(200) DEFAULT NULL COMMENT 'Nombre completo del familiar',
  `parentesco` varchar(100) DEFAULT NULL COMMENT 'Parentesco (cónyuge, hijo, etc.)',
  `fecha_nacimiento` date DEFAULT NULL COMMENT 'Fecha de nacimiento del familiar',
  `pasaporte` varchar(100) DEFAULT NULL COMMENT 'Número de pasaporte del familiar',
  `residence_place` varchar(200) DEFAULT NULL COMMENT 'Lugar de residencia del familiar',
  `address` text COMMENT 'Domicilio completo del familiar',
  `client_email` varchar(150) DEFAULT NULL COMMENT 'Email del familiar',
  `embassy_email` varchar(150) DEFAULT NULL COMMENT 'Email de la embajada',
  `amount_paid` decimal(10,2) DEFAULT NULL COMMENT 'Honorarios pagados por el familiar',
  `dhl` varchar(100) DEFAULT NULL,
  `observations` text,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `information_sheet_id` (`information_sheet_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `isf_ibfk_1`
    FOREIGN KEY (`information_sheet_id`) REFERENCES `information_sheets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `isf_ibfk_2`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
