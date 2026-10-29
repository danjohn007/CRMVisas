-- ============================================================
-- CRM VISAS V4.2 - VENTAS MULTIPLES + CATALOGO ADMINISTRABLE
-- + FOLIO AUTOMATICO DE VENTA
-- Compatible con MySQL 5.7+
-- Ejecutar UNA sola vez sobre la BD actual.
-- ============================================================

SET NAMES utf8mb4;
SET @db_name := DATABASE();

-- 1) Permitir varias ventas/servicios para una misma solicitud
SET @has_sale_folio := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='sale_folio'
);
SET @sql := IF(
    @has_sale_folio=0,
    'ALTER TABLE income_sales ADD COLUMN sale_folio varchar(40) NULL AFTER id',
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE income_sales
SET sale_folio = CONCAT(
    'VTA-',
    YEAR(COALESCE(contract_date, DATE(created_at))),
    '-',
    LPAD(id, 6, '0')
)
WHERE sale_folio IS NULL OR sale_folio='';

ALTER TABLE income_sales
MODIFY COLUMN sale_folio varchar(40) NOT NULL;

SET @has_unique_app := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales'
      AND INDEX_NAME='uk_income_sale_application'
);
SET @sql := IF(
    @has_unique_app>0,
    'ALTER TABLE income_sales DROP INDEX uk_income_sale_application',
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_sale_folio_index := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales'
      AND INDEX_NAME='uk_income_sales_folio'
);
SET @sql := IF(
    @has_sale_folio_index=0,
    'ALTER TABLE income_sales ADD UNIQUE KEY uk_income_sales_folio (sale_folio)',
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_app_index := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales'
      AND INDEX_NAME='idx_income_sales_application'
);
SET @sql := IF(
    @has_app_index=0,
    'ALTER TABLE income_sales ADD KEY idx_income_sales_application (application_id)',
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Estados administrativos del Catálogo Maestro
SET @has_catalog_status := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_catalog'
      AND COLUMN_NAME='catalog_status'
);
SET @sql := IF(
    @has_catalog_status=0,
    "ALTER TABLE income_catalog ADD COLUMN catalog_status enum('ACTIVO','SUSPENDIDO','INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER is_active",
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE income_catalog
SET catalog_status='INACTIVO'
WHERE is_active=0 AND catalog_status='ACTIVO';

UPDATE income_catalog
SET is_active = CASE WHEN catalog_status='ACTIVO' THEN 1 ELSE 0 END;

SELECT 'V4.2 instalada: ventas múltiples, folio automático y catálogo administrable' AS resultado;
