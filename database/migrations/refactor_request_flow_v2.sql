-- ============================================================
-- Migración: Refactorización flujo de solicitudes v2
-- Validaciones estrictas, control de estados, permisos por rol
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ds160_confirmation_number
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'ds160_confirmation_number'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `ds160_confirmation_number` VARCHAR(100) DEFAULT NULL
     COMMENT 'Número de confirmación DS-160 (opcional)'
     AFTER `delivery_date`",
    "SELECT 'ds160_confirmation_number ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- client_name (para búsqueda/visualización rápida)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'applications'
      AND COLUMN_NAME = 'client_name'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE `applications`
     ADD COLUMN `client_name` VARCHAR(200) DEFAULT NULL
     COMMENT 'Nombre completo del solicitante para búsqueda rápida'
     AFTER `ds160_confirmation_number`",
    "SELECT 'client_name ya existe'"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration refactor_request_flow_v2 completed!' as status;
