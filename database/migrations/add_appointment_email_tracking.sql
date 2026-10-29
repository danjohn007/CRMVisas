-- ============================================================
-- Migración: Tracking de emails de recordatorio de citas
-- Descripción: Agrega columnas para controlar si ya se envió
--              el email de recordatorio 2 días antes de la cita
-- ============================================================

SET NAMES utf8mb4;

-- appointment_reminder_sent (flujo estándar)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'appointment_reminder_sent'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `appointment_reminder_sent` TINYINT(1) DEFAULT 0
     COMMENT 'Flag: email de recordatorio enviado para cita consular'",
    "SELECT 'appointment_reminder_sent ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- biometric_reminder_sent (flujo canadiense)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'biometric_reminder_sent'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `biometric_reminder_sent` TINYINT(1) DEFAULT 0
     COMMENT 'Flag: email de recordatorio enviado para cita biométrica (Canadiense)'",
    "SELECT 'biometric_reminder_sent ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
