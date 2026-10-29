-- Migration: Add dhl column to information_sheets table
-- Compatible with MySQL 5.7

SET FOREIGN_KEY_CHECKS = 0;

-- Add dhl column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'information_sheets';
SET @columnname = 'dhl';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT ''dhl column already exists''',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `dhl` VARCHAR(100) DEFAULT NULL COMMENT ''Guía DHL'' AFTER `amount_paid`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET FOREIGN_KEY_CHECKS = 1;
