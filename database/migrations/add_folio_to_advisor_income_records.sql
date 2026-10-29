-- ============================================================
-- Migracion: folio para ingresos de asesores (MySQL 5.7)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Agrega columna folio solo si no existe.
SET @folio_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advisor_income_records'
      AND COLUMN_NAME = 'folio'
);

SET @add_folio_sql := IF(
    @folio_column_exists = 0,
    'ALTER TABLE advisor_income_records ADD COLUMN folio VARCHAR(30) NULL AFTER id',
    'SELECT "folio column already exists"'
);
PREPARE stmt_add_folio FROM @add_folio_sql;
EXECUTE stmt_add_folio;
DEALLOCATE PREPARE stmt_add_folio;

-- 2) Backfill de folios para registros existentes sin folio.
UPDATE advisor_income_records
SET folio = CONCAT('ING-', DATE_FORMAT(COALESCE(created_at, income_datetime), '%Y%m%d'), '-', LPAD(id, 6, '0'))
WHERE (folio IS NULL OR folio = '');

-- 3) Crea indice unico para folio solo si no existe.
SET @folio_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advisor_income_records'
      AND INDEX_NAME = 'ux_advisor_income_records_folio'
);

SET @add_folio_index_sql := IF(
    @folio_index_exists = 0,
    'ALTER TABLE advisor_income_records ADD UNIQUE KEY ux_advisor_income_records_folio (folio)',
    'SELECT "folio unique index already exists"'
);
PREPARE stmt_add_folio_index FROM @add_folio_index_sql;
EXECUTE stmt_add_folio_index;
DEALLOCATE PREPARE stmt_add_folio_index;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migration add_folio_to_advisor_income_records completed!' AS status;
