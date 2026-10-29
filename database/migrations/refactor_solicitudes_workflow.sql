-- ============================================================
-- Migración COMPLETA segura compatible con MySQL 5.7
-- Refactorización del flujo de solicitudes
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1️⃣ Modificar columna status (si existe)
-- ============================================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'status'
);

SET @sql = IF(@col_exists > 0,
    "ALTER TABLE `applications`
     MODIFY COLUMN `status` VARCHAR(60) NOT NULL DEFAULT 'Nuevo'
     COMMENT 'Estados: Nuevo(gris), Listo para solicitud(rojo), En espera de pago consular(amarillo), Cita programada(azul), En espera de resultado(morado), Trámite cerrado(verde)'",
    "SELECT 'Columna status no existe'"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2️⃣ Actualizar datos antiguos
-- ============================================================

UPDATE `applications`
SET `status` = 'Nuevo'
WHERE `status` = 'Formulario recibido';

-- ============================================================
-- 3️⃣ Agregar doc_type en documents (si no existe)
-- ============================================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documents'
      AND COLUMN_NAME = 'doc_type'
);

SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `documents`
     ADD COLUMN `doc_type` VARCHAR(50) DEFAULT 'adicional'
     COMMENT 'pasaporte_vigente, visa_anterior, ficha_pago_consular, adicional'
     AFTER `name`",
    "SELECT 'doc_type ya existe'"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4️⃣ Agregar columnas workflow en applications (una por una)
-- ============================================================

-- Macro reutilizable mentalmente: repetir patrón por columna

-- form_link_id
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'form_link_id'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `form_link_id` INT(11) DEFAULT NULL
     COMMENT 'ID del formulario enviado al cliente'
     AFTER `data_json`",
    "SELECT 'form_link_id ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- form_link_status
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'form_link_status'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `form_link_status` VARCHAR(20) DEFAULT NULL
     COMMENT 'pendiente, enviado, completado'
     AFTER `form_link_id`",
    "SELECT 'form_link_status ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- form_link_sent_at
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'form_link_sent_at'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `form_link_sent_at` TIMESTAMP NULL DEFAULT NULL
     AFTER `form_link_status`",
    "SELECT 'form_link_sent_at ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- official_application_done
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'official_application_done'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `official_application_done` TINYINT(1) DEFAULT 0
     AFTER `form_link_sent_at`",
    "SELECT 'official_application_done ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- consular_fee_sent
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'consular_fee_sent'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `consular_fee_sent` TINYINT(1) DEFAULT 0
     AFTER `official_application_done`",
    "SELECT 'consular_fee_sent ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- consular_payment_confirmed
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'consular_payment_confirmed'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `consular_payment_confirmed` TINYINT(1) DEFAULT 0
     AFTER `consular_fee_sent`",
    "SELECT 'consular_payment_confirmed ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- appointment_date
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'appointment_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `appointment_date` DATETIME DEFAULT NULL
     AFTER `consular_payment_confirmed`",
    "SELECT 'appointment_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- appointment_confirmation_file
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'appointment_confirmation_file'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `appointment_confirmation_file` VARCHAR(500) DEFAULT NULL
     AFTER `appointment_date`",
    "SELECT 'appointment_confirmation_file ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- client_attended
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'client_attended'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `client_attended` TINYINT(1) DEFAULT 0
     AFTER `appointment_confirmation_file`",
    "SELECT 'client_attended ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- client_attended_date
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'client_attended_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `client_attended_date` DATE DEFAULT NULL
     AFTER `client_attended`",
    "SELECT 'client_attended_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- appointment_confirmed_day_before
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'appointment_confirmed_day_before'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `appointment_confirmed_day_before` TINYINT(1) DEFAULT 0
     AFTER `client_attended_date`",
    "SELECT 'appointment_confirmed_day_before ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- dhl_tracking
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'dhl_tracking'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `dhl_tracking` VARCHAR(100) DEFAULT NULL
     AFTER `appointment_confirmed_day_before`",
    "SELECT 'dhl_tracking ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- delivery_date
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'delivery_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `delivery_date` DATE DEFAULT NULL
     AFTER `dhl_tracking`",
    "SELECT 'delivery_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5️⃣ Crear tabla information_sheets
-- ============================================================

CREATE TABLE IF NOT EXISTS `information_sheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `entry_date` date NOT NULL COMMENT 'Fecha de ingreso',
  `residence_place` varchar(200) DEFAULT NULL COMMENT 'Ciudad, Estado, País',
  `address` text,
  `client_email` varchar(150) DEFAULT NULL,
  `embassy_email` varchar(150) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `observations` text,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `information_sheets_ibfk_1`
    FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `information_sheets_ibfk_2`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
