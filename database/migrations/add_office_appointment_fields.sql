-- ============================================================
-- Migración: Campos para agendar cita a oficinas (estado AZUL)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- office_appointment_date
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'office_appointment_date'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `office_appointment_date` DATETIME DEFAULT NULL
     COMMENT 'Fecha y hora de cita a oficinas para indicaciones previas a consulado/biométrica'
     AFTER `appointment_date`",
    "SELECT 'office_appointment_date ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- office_appointment_modality
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'office_appointment_modality'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `office_appointment_modality` ENUM('Zoom','Presencial') DEFAULT NULL
     COMMENT 'Modalidad de la cita a oficinas: Zoom o Presencial'
     AFTER `office_appointment_date`",
    "SELECT 'office_appointment_modality ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration add_office_appointment_fields completed!' as status;
