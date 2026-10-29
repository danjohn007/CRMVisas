-- Migration: Add System Enhancements
-- Description: Adds cost field, pagination, progress tracking, public forms, and customer journey features
-- Date: 2026-02-04

USE `recursos_visas`;

SET FOREIGN_KEY_CHECKS = 0;

-- ====================
-- 1. Enhancements to forms table
-- ====================

-- Add cost and PayPal fields to forms
ALTER TABLE `forms` 
ADD COLUMN `cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cost for this form/service' AFTER `fields_json`,
ADD COLUMN `paypal_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Enable PayPal payment for this form' AFTER `cost`,
ADD COLUMN `pagination_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Enable pagination/sections in form' AFTER `paypal_enabled`,
ADD COLUMN `pages_json` LONGTEXT NULL COMMENT 'Page structure in JSON format if pagination enabled' AFTER `pagination_enabled`,
ADD COLUMN `public_token` VARCHAR(64) NULL COMMENT 'Unique token for public form access' AFTER `pages_json`,
ADD COLUMN `public_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Allow public access to this form' AFTER `public_token`;

-- ====================
-- 2. Enhancements to applications table
-- ====================

-- Add progress tracking fields to applications
ALTER TABLE `applications`
ADD COLUMN `current_page` INT DEFAULT 1 COMMENT 'Current page if form has pagination' AFTER `data_json`,
ADD COLUMN `progress_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Completion percentage' AFTER `current_page`,
ADD COLUMN `is_draft` TINYINT(1) DEFAULT 0 COMMENT 'Is this a draft/incomplete submission' AFTER `progress_percentage`,
ADD COLUMN `last_saved_at` TIMESTAMP NULL COMMENT 'Last auto-save timestamp' AFTER `is_draft`;

-- ====================
-- 3. Create customer_journey table
-- ====================

DROP TABLE IF EXISTS `customer_journey`;
CREATE TABLE `customer_journey` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `touchpoint_type` VARCHAR(50) NOT NULL COMMENT 'email, call, meeting, status_change, payment, document_upload, etc',
  `touchpoint_title` VARCHAR(200) NOT NULL,
  `touchpoint_description` TEXT,
  `contact_method` VARCHAR(50) NULL COMMENT 'email, phone, in-person, online',
  `user_id` INT(11) NULL COMMENT 'User who performed the action',
  `metadata_json` TEXT NULL COMMENT 'Additional data in JSON format',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  KEY `touchpoint_type` (`touchpoint_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `customer_journey_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_journey_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- 4. Create public_form_submissions table
-- ====================

DROP TABLE IF EXISTS `public_form_submissions`;
CREATE TABLE `public_form_submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` INT(11) NOT NULL,
  `application_id` INT(11) NULL COMMENT 'Linked application if converted',
  `submission_data` LONGTEXT NOT NULL COMMENT 'Form data in JSON',
  `progress_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `current_page` INT DEFAULT 1,
  `is_completed` TINYINT(1) DEFAULT 0,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `application_id` (`application_id`),
  KEY `is_completed` (`is_completed`),
  CONSTRAINT `public_form_submissions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `public_form_submissions_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- 5. Generate public tokens for existing forms
-- ====================

-- Generate unique tokens for each form individually to avoid duplicates
-- Using MD5 hash of id, name, created_at, and a unique salt per row
UPDATE `forms` 
SET `public_token` = LOWER(CONCAT(
    MD5(CONCAT(id, name, COALESCE(created_at, NOW()), RAND())),
    MD5(CONCAT(created_by, COALESCE(updated_at, NOW()), id * 1000))
))
WHERE `public_token` IS NULL OR `public_token` = '';

-- Create unique index on public_token AFTER generating tokens
CREATE UNIQUE INDEX IF NOT EXISTS idx_forms_public_token ON forms(public_token);

-- ====================
-- 6. Insert sample customer journey data
-- ====================

INSERT INTO `customer_journey` (`application_id`, `touchpoint_type`, `touchpoint_title`, `touchpoint_description`, `contact_method`, `user_id`) VALUES
(1, 'status_change', 'Solicitud creada', 'Solicitud de visa americana creada en el sistema', 'online', 3),
(1, 'status_change', 'En revisión', 'Documentos recibidos, iniciando revisión', 'online', 2),
(1, 'email', 'Correo de confirmación', 'Se envió correo de confirmación al solicitante', 'email', 2),
(2, 'status_change', 'Solicitud creada', 'Solicitud de visa americana creada', 'online', 3),
(2, 'status_change', 'En revisión', 'En proceso de validación', 'online', 2),
(2, 'status_change', 'Documentación validada', 'Documentos aprobados', 'online', 2),
(2, 'call', 'Llamada de seguimiento', 'Se contactó al cliente para confirmar cita consular', 'phone', 2);

SET FOREIGN_KEY_CHECKS = 1;

-- ====================
-- Verification queries
-- ====================

-- Verify forms table changes
SELECT 'Forms table columns' as verification;
SHOW COLUMNS FROM forms LIKE '%cost%';
SHOW COLUMNS FROM forms LIKE '%pagination%';
SHOW COLUMNS FROM forms LIKE '%public%';

-- Verify applications table changes
SELECT 'Applications table columns' as verification;
SHOW COLUMNS FROM applications LIKE '%progress%';
SHOW COLUMNS FROM applications LIKE '%current_page%';

-- Verify new tables
SELECT 'New tables created' as verification;
SHOW TABLES LIKE 'customer_journey';
SHOW TABLES LIKE 'public_form_submissions';

-- Show sample data
SELECT 'Sample customer journey entries' as verification;
SELECT COUNT(*) as journey_count FROM customer_journey;

SELECT 'Migration completed successfully!' as status;
