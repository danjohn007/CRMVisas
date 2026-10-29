-- ============================================================
-- Migración: Flujo especializado para Visa Canadiense
-- Descripción: Hace form_id nullable y agrega columnas para
--              el flujo de Visa/ETA Canadiense
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. Hacer form_id nullable en applications
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'form_id'
);
SET @sql = IF(@col_exists > 0,
    "ALTER TABLE `applications` MODIFY COLUMN `form_id` INT(11) DEFAULT NULL COMMENT 'NULL para tramites especializados como Visa Canadiense'",
    "SELECT 'form_id not found'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. Agregar flag is_canadian_visa
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'is_canadian_visa'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `is_canadian_visa` TINYINT(1) DEFAULT 0 COMMENT 'Flujo especializado Visa Canadiense' AFTER `subtype`",
    "SELECT 'is_canadian_visa ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. canadian_tipo (Visa Canadiense / ETA Canadiense)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_tipo'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_tipo` VARCHAR(50) DEFAULT NULL COMMENT 'Visa Canadiense o ETA Canadiense' AFTER `is_canadian_visa`",
    "SELECT 'canadian_tipo ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. canadian_modalidad (Primera vez / Renovación)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_modalidad'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_modalidad` VARCHAR(50) DEFAULT NULL COMMENT 'Primera vez o Renovacion' AFTER `canadian_tipo`",
    "SELECT 'canadian_modalidad ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5. Estado ROJO canadiense: docs cargados en portal
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_docs_uploaded_portal'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_docs_uploaded_portal` TINYINT(1) DEFAULT 0 COMMENT 'Documentos cargados en portal Canada' AFTER `canadian_modalidad`",
    "SELECT 'canadian_docs_uploaded_portal ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 6. Número de aplicación canadiense (opcional)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_application_number'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_application_number` VARCHAR(100) DEFAULT NULL COMMENT 'Numero de aplicacion canadiense (opcional)' AFTER `canadian_docs_uploaded_portal`",
    "SELECT 'canadian_application_number ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. Estado AMARILLO canadiense: cita biométrica generada
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_biometric_appointment_generated'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_biometric_appointment_generated` TINYINT(1) DEFAULT 0 COMMENT 'Cita para biometricos generada' AFTER `canadian_application_number`",
    "SELECT 'canadian_biometric_appointment_generated ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 8. Fecha de biométricos (con hora)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_biometric_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_biometric_date` DATETIME DEFAULT NULL COMMENT 'Fecha y hora de biometricos' AFTER `canadian_biometric_appointment_generated`",
    "SELECT 'canadian_biometric_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 9. Lugar de biométricos
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_biometric_location'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_biometric_location` VARCHAR(200) DEFAULT NULL COMMENT 'Lugar de la cita de biometricos' AFTER `canadian_biometric_date`",
    "SELECT 'canadian_biometric_location ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 10. Estado AZUL canadiense: asistencia a biométricos
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_client_attended_biometrics'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_client_attended_biometrics` TINYINT(1) DEFAULT 0 COMMENT 'Cliente asistio a biometricos' AFTER `canadian_biometric_location`",
    "SELECT 'canadian_client_attended_biometrics ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 11. Fecha de asistencia a biométricos
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_biometric_attended_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_biometric_attended_date` DATE DEFAULT NULL COMMENT 'Fecha de asistencia a biometricos' AFTER `canadian_client_attended_biometrics`",
    "SELECT 'canadian_biometric_attended_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 12. Resultado de visa canadiense (aprobada / negada)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_visa_result'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_visa_result` VARCHAR(20) DEFAULT NULL COMMENT 'aprobada o negada' AFTER `canadian_biometric_attended_date`",
    "SELECT 'canadian_visa_result ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 13. Fecha de resolución (opcional)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_resolution_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_resolution_date` DATE DEFAULT NULL COMMENT 'Fecha de resolucion de la visa canadiense' AFTER `canadian_visa_result`",
    "SELECT 'canadian_resolution_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 14. Número de guía (opcional)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_guide_number'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_guide_number` VARCHAR(100) DEFAULT NULL COMMENT 'Numero de guia (si aplica)' AFTER `canadian_resolution_date`",
    "SELECT 'canadian_guide_number ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 15. Observaciones finales (opcional)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'canadian_final_observations'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications` ADD COLUMN `canadian_final_observations` TEXT DEFAULT NULL COMMENT 'Observaciones finales de la resolucion' AFTER `canadian_guide_number`",
    "SELECT 'canadian_final_observations ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration add_canadian_visa_flow completed!' as status;
