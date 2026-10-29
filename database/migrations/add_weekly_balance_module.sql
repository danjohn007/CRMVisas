-- ============================================================
-- CRM VISAS - BALANCE SEMANAL / CONTROL DE EFECTIVO
-- MySQL 5.7+
-- Ejecutar UNA VEZ antes de subir los archivos PHP.
-- ============================================================
SET NAMES utf8mb4;
SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS `financial_envelopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `opened_at` date NOT NULL,
  `status` enum('ACTIVO','CERRADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `is_free_cash` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) NOT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_financial_envelope_name` (`name`),
  KEY `idx_financial_envelope_status` (`status`),
  KEY `idx_financial_envelope_opened` (`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_envelope_movements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `envelope_id` int(11) NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('ENTRADA','SALIDA','TRASPASO_ENTRADA','TRASPASO_SALIDA','AJUSTE_ENTRADA','AJUSTE_SALIDA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `concept` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `client_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_name` varchar(220) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `source_id` bigint(20) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `authorized_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fem_envelope_date` (`envelope_id`,`movement_date`),
  KEY `idx_fem_source` (`source_type`,`source_id`),
  KEY `idx_fem_application` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_weekly_closures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `status` enum('ABIERTO','CERRADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ABIERTO',
  `total_income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_cash_income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_transfer_income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_card_income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_other_income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_expenses` decimal(14,2) NOT NULL DEFAULT '0.00',
  `weekly_result` decimal(14,2) NOT NULL DEFAULT '0.00',
  `accounts_receivable` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_envelopes` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_free_cash` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_theoretical_cash` decimal(14,2) NOT NULL DEFAULT '0.00',
  `office_counted_cash` decimal(14,2) DEFAULT NULL,
  `office_difference` decimal(14,2) DEFAULT NULL,
  `closing_notes` text COLLATE utf8mb4_unicode_ci,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `correction_authorized_by` int(11) DEFAULT NULL,
  `correction_authorized_until` datetime DEFAULT NULL,
  `correction_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_financial_week_start` (`week_start`),
  KEY `idx_financial_week_status` (`status`),
  KEY `idx_financial_week_dates` (`week_start`,`week_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_weekly_envelope_counts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `weekly_closure_id` int(11) NOT NULL,
  `envelope_id` int(11) NOT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `entries` decimal(14,2) NOT NULL DEFAULT '0.00',
  `exits` decimal(14,2) NOT NULL DEFAULT '0.00',
  `theoretical_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `counted_cash` decimal(14,2) DEFAULT NULL,
  `difference` decimal(14,2) DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `evidence_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_week_envelope_count` (`weekly_closure_id`,`envelope_id`),
  KEY `idx_week_count_envelope` (`envelope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_correction_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `weekly_closure_id` int(11) NOT NULL,
  `action_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata_json` longtext COLLATE utf8mb4_unicode_ci,
  `authorized_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_financial_correction_week` (`weekly_closure_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Caja libre: se administra con el mismo libro mayor, pero no cuenta como "sobre".
INSERT INTO `financial_envelopes`
  (`name`, `opening_balance`, `opened_at`, `status`, `is_free_cash`, `notes`, `created_by`)
SELECT 'CAJA LIBRE', 0.00, CURDATE(), 'ACTIVO', 1,
       'Contenedor del sistema para efectivo físico que aún no está asignado a un sobre.',
       COALESCE((SELECT id FROM users WHERE role = 'Administrador' ORDER BY id ASC LIMIT 1), 1)
WHERE NOT EXISTS (SELECT 1 FROM financial_envelopes WHERE is_free_cash = 1);

-- ------------------------------------------------------------
-- Columnas nuevas: se agregan de forma compatible con MySQL 5.7
-- ------------------------------------------------------------

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND COLUMN_NAME='cash_envelope_id');
SET @sql := IF(@has_col=0, 'ALTER TABLE payments ADD COLUMN cash_envelope_id int(11) DEFAULT NULL AFTER payment_method', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND COLUMN_NAME='client_name_snapshot');
SET @sql := IF(@has_col=0, 'ALTER TABLE payments ADD COLUMN client_name_snapshot varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER cash_envelope_id', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND COLUMN_NAME='service_snapshot');
SET @sql := IF(@has_col=0, 'ALTER TABLE payments ADD COLUMN service_snapshot varchar(220) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER client_name_snapshot', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND COLUMN_NAME='payment_group_key');
SET @sql := IF(@has_col=0, 'ALTER TABLE payments ADD COLUMN payment_group_key varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER service_snapshot', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND COLUMN_NAME='source_key');
SET @sql := IF(@has_col=0, 'ALTER TABLE payments ADD COLUMN source_key varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER payment_group_key', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='payment_method');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_expenses ADD COLUMN payment_method varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER amount', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='cash_envelope_id');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_expenses ADD COLUMN cash_envelope_id int(11) DEFAULT NULL AFTER payment_method', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_expenses' AND COLUMN_NAME='reference');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_expenses ADD COLUMN reference varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER cash_envelope_id', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='application_id');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN application_id int(11) DEFAULT NULL AFTER folio', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='client_name');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN client_name varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER application_id', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='service_name');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN service_name varchar(220) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER client_name', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='payment_method');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN payment_method varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER service_name', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='cash_envelope_id');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN cash_envelope_id int(11) DEFAULT NULL AFTER payment_method', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='advisor_income_records' AND COLUMN_NAME='reference');
SET @sql := IF(@has_col=0, 'ALTER TABLE advisor_income_records ADD COLUMN reference varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER cash_envelope_id', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='payment_method');
SET @sql := IF(@has_col=0, 'ALTER TABLE information_sheets ADD COLUMN payment_method varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER amount_paid', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='payment_reference');
SET @sql := IF(@has_col=0, 'ALTER TABLE information_sheets ADD COLUMN payment_reference varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER payment_method', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='information_sheets' AND COLUMN_NAME='cash_envelope_id');
SET @sql := IF(@has_col=0, 'ALTER TABLE information_sheets ADD COLUMN cash_envelope_id int(11) DEFAULT NULL AFTER payment_reference', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Marcar pagos automáticos históricos sin perder su referencia original.
UPDATE payments
SET source_key = reference
WHERE source_key IS NULL
  AND reference LIKE 'INFO-SHEET-%';

-- Índices útiles (se crean sólo si no existen).
SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_cash_envelope');
SET @sql := IF(@has_idx=0, 'ALTER TABLE payments ADD KEY idx_payments_cash_envelope (cash_envelope_id)', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_group');
SET @sql := IF(@has_idx=0, 'ALTER TABLE payments ADD KEY idx_payments_group (payment_group_key)', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_source_key');
SET @sql := IF(@has_idx=0, 'ALTER TABLE payments ADD KEY idx_payments_source_key (source_key)', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT 'Migración Balance Semanal instalada correctamente' AS resultado;
