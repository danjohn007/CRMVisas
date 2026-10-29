-- ============================================================
-- CRM VISAS V5.0.2 - FLUJO FINAL FINANCIERO (PHPMyAdmin SAFE)
-- Hoja de Información -> Venta -> Validación -> Cuentas
-- Egresos sólo Gerencia / Efectivo
-- Resumen quincenal asesora + horas extra
-- Resumen mensual con detalle por servicio
-- MySQL 5.7+
-- ============================================================
SET NAMES utf8mb4;
SET @db_name := DATABASE();

-- ------------------------------------------------------------
-- PRE-REQUISITOS SEGUROS
-- La V5 normalmente se instala sobre V4.3. Estas validaciones permiten
-- recuperar automáticamente dos cambios críticos si faltaran.
-- ------------------------------------------------------------
SET @has_sale_folio := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='sale_folio'
);
SET @sql := IF(@has_sale_folio=0,
  'ALTER TABLE income_sales ADD COLUMN sale_folio varchar(40) NULL AFTER id',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE income_sales
SET sale_folio=CONCAT('VTA-',YEAR(COALESCE(contract_date,DATE(created_at))),'-',LPAD(id,6,'0'))
WHERE sale_folio IS NULL OR sale_folio='';

ALTER TABLE income_sales MODIFY COLUMN sale_folio varchar(40) NOT NULL;

SET @has_old_unique := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales'
    AND INDEX_NAME='uk_income_sale_application'
);
SET @sql := IF(@has_old_unique>0,
  'ALTER TABLE income_sales DROP INDEX uk_income_sale_application',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_sale_folio_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales'
    AND INDEX_NAME='uk_income_sales_folio'
);
SET @sql := IF(@has_sale_folio_idx=0,
  'ALTER TABLE income_sales ADD UNIQUE KEY uk_income_sales_folio (sale_folio)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_catalog_status := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_catalog' AND COLUMN_NAME='catalog_status'
);
SET @sql := IF(@has_catalog_status=0,
  "ALTER TABLE income_catalog ADD COLUMN catalog_status enum('ACTIVO','SUSPENDIDO','INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER is_active",
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE income_catalog
SET catalog_status=CASE WHEN is_active=1 THEN 'ACTIVO' ELSE 'INACTIVO' END
WHERE catalog_status IS NULL OR catalog_status='';

-- ------------------------------------------------------------
-- 1. Catálogo: definir dónde puede venderse cada concepto.
-- ------------------------------------------------------------
SET @has_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_catalog' AND COLUMN_NAME='sale_channel'
);
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_catalog ADD COLUMN sale_channel enum('SOLICITUD','MANUAL','AMBOS') NOT NULL DEFAULT 'SOLICITUD' AFTER catalog_status",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Servicios rápidos / administrativos: se capturan manualmente.
UPDATE income_catalog
SET sale_channel='MANUAL'
WHERE code IN ('ING-006','ING-031','ING-032','ING-033','ING-034','ING-035','ING-036');

-- ------------------------------------------------------------
-- 2. Clientes rápidos para copias, actas, impresiones, etc.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `income_quick_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_folio` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_quick_clients'
    AND INDEX_NAME='uk_income_quick_client_folio'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `income_quick_clients` ADD UNIQUE KEY `uk_income_quick_client_folio` (`client_folio`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_quick_clients'
    AND INDEX_NAME='idx_income_quick_client_name'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `income_quick_clients` ADD KEY `idx_income_quick_client_name` (`full_name`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_quick_clients'
    AND INDEX_NAME='idx_income_quick_client_created_by'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `income_quick_clients` ADD KEY `idx_income_quick_client_created_by` (`created_by`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 3. Venta: permitir cliente formal o cliente rápido.
-- ------------------------------------------------------------
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='quick_client_id');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN quick_client_id int(11) DEFAULT NULL AFTER application_id",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='client_folio_snapshot');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN client_folio_snapshot varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER client_name_snapshot",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='sale_origin');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN sale_origin enum('HOJA_INFORMACION','MANUAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL' AFTER catalog_code",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='information_sheet_id');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN information_sheet_id int(11) DEFAULT NULL AFTER sale_origin",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Se agrega como VALIDADA para no bloquear ventas históricas.
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='approval_status');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN approval_status enum('PENDIENTE','VALIDADA','RECHAZADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VALIDADA' AFTER financial_status",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='approved_by');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN approved_by int(11) DEFAULT NULL AFTER approval_status",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='approved_at');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN approved_at datetime DEFAULT NULL AFTER approved_by",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND COLUMN_NAME='rejection_reason');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_sales ADD COLUMN rejection_reason text COLLATE utf8mb4_unicode_ci AFTER approved_at",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Nuevas ventas quedan pendientes por defecto.
ALTER TABLE income_sales
  MODIFY approval_status enum('PENDIENTE','VALIDADA','RECHAZADA')
  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE';

-- application_id deja de ser obligatorio para ventas rápidas.
ALTER TABLE income_sales MODIFY application_id int(11) DEFAULT NULL;

UPDATE income_sales s
LEFT JOIN applications a ON a.id=s.application_id
SET s.client_folio_snapshot = COALESCE(NULLIF(s.client_folio_snapshot,''), a.folio, s.sale_folio)
WHERE s.client_folio_snapshot IS NULL OR s.client_folio_snapshot='';

SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND INDEX_NAME='uk_income_sales_info_sheet');
SET @sql := IF(@has_idx=0,
  "ALTER TABLE income_sales ADD UNIQUE KEY uk_income_sales_info_sheet (information_sheet_id)",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_sales' AND INDEX_NAME='idx_income_sales_quick_client');
SET @sql := IF(@has_idx=0,
  "ALTER TABLE income_sales ADD KEY idx_income_sales_quick_client (quick_client_id)",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 4. Pagos: permitir ventas rápidas y origen de captura.
-- ------------------------------------------------------------
ALTER TABLE income_payments MODIFY application_id int(11) DEFAULT NULL;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_payments' AND COLUMN_NAME='quick_client_id');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_payments ADD COLUMN quick_client_id int(11) DEFAULT NULL AFTER application_id",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_payments' AND COLUMN_NAME='source_type');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_payments ADD COLUMN source_type varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER legacy_payment_id",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_payments' AND COLUMN_NAME='source_id');
SET @sql := IF(@has_col=0,
  "ALTER TABLE income_payments ADD COLUMN source_id int(11) DEFAULT NULL AFTER source_type",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='income_payments' AND INDEX_NAME='idx_income_payment_source');
SET @sql := IF(@has_idx=0,
  "ALTER TABLE income_payments ADD KEY idx_income_payment_source (source_type,source_id)",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 5. Hoja de Información se convierte en origen de la venta formal.
-- ------------------------------------------------------------
SET @cols := 'service_catalog_id,service_name,service_quantity,service_unit_price,service_total_amount,payment_cash,payment_terminal,payment_transfer,financial_sale_id';

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='service_catalog_id');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN service_catalog_id int(11) DEFAULT NULL AFTER embassy_email", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='service_name');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN service_name varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER service_catalog_id", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='service_quantity');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN service_quantity int(11) NOT NULL DEFAULT 1 AFTER service_name", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='service_unit_price');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN service_unit_price decimal(14,2) DEFAULT NULL AFTER service_quantity", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='service_total_amount');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN service_total_amount decimal(14,2) DEFAULT NULL AFTER service_unit_price", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='payment_cash');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN payment_cash decimal(14,2) NOT NULL DEFAULT 0.00 AFTER amount_paid", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='payment_terminal');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN payment_terminal decimal(14,2) NOT NULL DEFAULT 0.00 AFTER payment_cash", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='payment_transfer');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN payment_transfer decimal(14,2) NOT NULL DEFAULT 0.00 AFTER payment_terminal", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='financial_sale_id');
SET @sql := IF(@has_col=0, "ALTER TABLE information_sheets ADD COLUMN financial_sale_id int(11) DEFAULT NULL AFTER payment_reference", "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Migrar el pago viejo de hoja de información a una sola de las 3 columnas.
UPDATE information_sheets
SET payment_cash = CASE WHEN UPPER(COALESCE(payment_method,''))='EFECTIVO' THEN COALESCE(amount_paid,0) ELSE payment_cash END,
    payment_terminal = CASE WHEN UPPER(COALESCE(payment_method,'')) IN ('TERMINAL_TARJETA','TARJETA','TERMINAL') THEN COALESCE(amount_paid,0) ELSE payment_terminal END,
    payment_transfer = CASE WHEN UPPER(COALESCE(payment_method,''))='TRANSFERENCIA' THEN COALESCE(amount_paid,0) ELSE payment_transfer END
WHERE COALESCE(amount_paid,0)>0
  AND COALESCE(payment_cash,0)+COALESCE(payment_terminal,0)+COALESCE(payment_transfer,0)=0;

-- Normalizar el método histórico "Depósito bancario" al esquema final de 3 cuentas.
-- Se considera parte de Transferencia para no dejar dinero histórico fuera del balance.
UPDATE income_payments
SET payment_method='TRANSFERENCIA'
WHERE payment_method='DEPOSITO_BANCARIO';

UPDATE payments
SET payment_method='TRANSFERENCIA'
WHERE UPPER(COALESCE(payment_method,'')) LIKE '%DEPOS%';

-- ------------------------------------------------------------
-- 6. Tres cuentas: EFECTIVO, TERMINAL y TRANSFERENCIA.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` enum('EFECTIVO','TERMINAL','TRANSFERENCIA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='finance_accounts'
    AND INDEX_NAME='uk_finance_accounts_code'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `finance_accounts` ADD UNIQUE KEY `uk_finance_accounts_code` (`code`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

INSERT INTO finance_accounts(code,name,opening_balance,is_active)
VALUES
 ('EFECTIVO','Efectivo / Ahorro',0,1),
 ('TERMINAL','Terminal',0,1),
 ('TRANSFERENCIA','Transferencia',0,1)
ON DUPLICATE KEY UPDATE name=VALUES(name),is_active=1;

CREATE TABLE IF NOT EXISTS `finance_account_movements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('INGRESO','EGRESO','AJUSTE_ENTRADA','AJUSTE_SALIDA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `concept` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_folio` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='finance_account_movements'
    AND INDEX_NAME='uk_finance_account_source'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `finance_account_movements` ADD UNIQUE KEY `uk_finance_account_source` (`account_id`,`source_type`,`source_id`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='finance_account_movements'
    AND INDEX_NAME='idx_finance_account_date'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `finance_account_movements` ADD KEY `idx_finance_account_date` (`account_id`,`movement_date`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='finance_account_movements'
    AND INDEX_NAME='idx_finance_account_source'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `finance_account_movements` ADD KEY `idx_finance_account_source` (`source_type`,`source_id`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Respaldar los ingresos ya validados en las 3 cuentas.
INSERT IGNORE INTO finance_account_movements
(account_id,movement_date,movement_type,amount,concept,client_name,client_folio,service_name,reference,source_type,source_id,created_by)
SELECT
  fa.id,
  p.payment_date,
  'INGRESO',
  p.amount,
  COALESCE(pc.concept,'Pago de cliente'),
  s.client_name_snapshot,
  s.client_folio_snapshot,
  s.service_name_snapshot,
  p.reference,
  'INCOME_PAYMENT',
  p.id,
  p.registered_by
FROM income_payments p
INNER JOIN income_sales s ON s.id=p.sale_id
LEFT JOIN income_payment_concepts pc ON pc.code=p.payment_concept_code
INNER JOIN finance_accounts fa ON fa.code=
  CASE
    WHEN p.payment_method='EFECTIVO' THEN 'EFECTIVO'
    WHEN p.payment_method='TERMINAL_TARJETA' THEN 'TERMINAL'
    WHEN p.payment_method='TRANSFERENCIA' THEN 'TRANSFERENCIA'
    ELSE 'EFECTIVO'
  END
WHERE p.validation_status='VALIDADO'
  AND p.payment_method IN ('EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA');

-- ------------------------------------------------------------
-- 7. Egresos: sólo efectivo, folio y evidencia.
-- ------------------------------------------------------------
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='expense_folio');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_expenses ADD COLUMN expense_folio varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER id",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='evidence_path');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_expenses ADD COLUMN evidence_path varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER reference",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='expense_time');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_expenses ADD COLUMN expense_time time DEFAULT NULL AFTER expense_date",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE financial_expenses
SET expense_folio=CONCAT('EGR-',YEAR(expense_date),'-',LPAD(id,6,'0'))
WHERE expense_folio IS NULL OR expense_folio='';

SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND INDEX_NAME='uk_financial_expense_folio');
SET @sql := IF(@has_idx=0,
  "ALTER TABLE financial_expenses ADD UNIQUE KEY uk_financial_expense_folio (expense_folio)",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE financial_expenses
SET payment_method='EFECTIVO'
WHERE payment_method IS NULL OR payment_method<>'EFECTIVO';

-- Respaldar egresos históricos en cuenta Efectivo.
INSERT IGNORE INTO finance_account_movements
(account_id,movement_date,movement_type,amount,concept,reference,source_type,source_id,created_by)
SELECT fa.id,e.expense_date,'EGRESO',e.amount,e.concept,e.reference,'EXPENSE',e.id,e.created_by
FROM financial_expenses e
INNER JOIN finance_accounts fa ON fa.code='EFECTIVO';

-- ------------------------------------------------------------
-- 8. Horas extra por asesora.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `advisor_overtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `advisor_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_overtime'
    AND INDEX_NAME='idx_advisor_overtime_date'
);
SET @sql := IF(@has_idx=0,
  'ALTER TABLE `advisor_overtime` ADD KEY `idx_advisor_overtime_date` (`advisor_id`,`work_date`)',
  'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 9. Saldos de cuentas al cerrar la semana.
-- ------------------------------------------------------------
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='cash_account_balance');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_weekly_closures ADD COLUMN cash_account_balance decimal(14,2) NOT NULL DEFAULT 0.00 AFTER accounts_receivable",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='terminal_account_balance');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_weekly_closures ADD COLUMN terminal_account_balance decimal(14,2) NOT NULL DEFAULT 0.00 AFTER cash_account_balance",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='transfer_account_balance');
SET @sql := IF(@has_col=0,
  "ALTER TABLE financial_weekly_closures ADD COLUMN transfer_account_balance decimal(14,2) NOT NULL DEFAULT 0.00 AFTER terminal_account_balance",
  "SELECT 1");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT 'CRM VISAS V5.0 instalado correctamente' AS resultado;
