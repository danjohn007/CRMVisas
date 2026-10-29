-- ============================================================
-- CRM VISAS - CONTROL DE INGRESOS V3
-- Alcance: INGRESOS solamente. Egresos se desarrollan por separado.
-- Flujo: Asesora registra venta -> Gerencia valida pagos ->
--        Expediente financiero -> Balance semanal LUNES A VIERNES.
-- Compatible con MySQL 5.7+
-- ============================================================

SET NAMES utf8mb4;
SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS `income_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `concept` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorized_price` decimal(14,2) DEFAULT NULL,
  `price_mode` enum('FIJO','POR_CONFIRMAR','SEGUN_DIFERENCIA','SEGUN_COTIZACION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FIJO',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_income_catalog_code` (`code`),
  KEY `idx_income_catalog_active` (`is_active`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `income_payment_concepts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `concept` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `treatment` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_commercial_payment` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_income_payment_concepts_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `income_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `catalog_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_name_snapshot` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_name_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `advisor_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(14,2) NOT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `contract_date` date NOT NULL,
  `next_followup_date` date DEFAULT NULL,
  `financial_status` enum('PENDIENTE','PARCIAL','LIQUIDADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_income_sale_application` (`application_id`),
  KEY `idx_income_sales_date` (`contract_date`),
  KEY `idx_income_sales_advisor` (`advisor_id`),
  KEY `idx_income_sales_status` (`financial_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `income_payments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `payment_concept_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('EFECTIVO','TRANSFERENCIA','DEPOSITO_BANCARIO','TERMINAL_TARJETA','OTRO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_account` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash_envelope_id` int(11) DEFAULT NULL,
  `reference` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `payment_date` date NOT NULL,
  `validation_status` enum('PENDIENTE','VALIDADO','RECHAZADO','CANCELADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `registered_by` int(11) NOT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `validation_notes` text COLLATE utf8mb4_unicode_ci,
  `legacy_payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_income_payment_legacy` (`legacy_payment_id`),
  KEY `idx_income_payments_sale` (`sale_id`,`validation_status`),
  KEY `idx_income_payments_date` (`payment_date`,`validation_status`),
  KEY `idx_income_payments_application` (`application_id`),
  KEY `idx_income_payments_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='total_deposit_income');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN total_deposit_income decimal(14,2) NOT NULL DEFAULT 0.00 AFTER total_transfer_income', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='total_sales_contracted');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN total_sales_contracted decimal(14,2) NOT NULL DEFAULT 0.00 AFTER total_other_income', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='sales_count');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN sales_count int(11) NOT NULL DEFAULT 0 AFTER total_sales_contracted', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='previous_sales_collections');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN previous_sales_collections decimal(14,2) NOT NULL DEFAULT 0.00 AFTER sales_count', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='payments_count');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN payments_count int(11) NOT NULL DEFAULT 0 AFTER previous_sales_collections', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='financial_weekly_closures' AND COLUMN_NAME='liquidations_count');
SET @sql := IF(@has_col=0, 'ALTER TABLE financial_weekly_closures ADD COLUMN liquidations_count int(11) NOT NULL DEFAULT 0 AFTER payments_count', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT IGNORE INTO income_catalog (code, category, concept, authorized_price, price_mode, is_active, notes) VALUES
('ING-001','VISA AMERICANA','Visa americana primera vez sin transporte',2720.00,'FIJO',1,NULL),
('ING-002','VISA AMERICANA','Visa americana renovación sin transporte',2720.00,'FIJO',1,NULL),
('ING-003','VISA AMERICANA','Visa americana primera vez con transporte',6520.00,'FIJO',1,NULL),
('ING-004','VISA AMERICANA','Visa americana renovación con transporte',5520.00,'FIJO',1,NULL),
('ING-005','VISA AMERICANA','Cita Express',4000.00,'FIJO',1,NULL),
('ING-006','VISA AMERICANA','Actualización de trámite / DS-160',900.00,'FIJO',1,NULL),
('ING-007','VISA AMERICANA','Reprogramación de cita sin transporte',2400.00,'FIJO',1,NULL),
('ING-008','VISA AMERICANA','Reactivación de trámite',2400.00,'FIJO',1,NULL),
('ING-009','VISA AMERICANA','Segunda entrevista consular / seguimiento',4000.00,'FIJO',1,NULL),
('ING-010','VISA AMERICANA','Nueva ficha de pago por vencimiento',450.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de reconfirmación'),
('ING-011','TRANSPORTES Y ACOMPAÑAMIENTOS','Traslado independiente a CDMX',2100.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de validación'),
('ING-012','TRANSPORTES Y ACOMPAÑAMIENTOS','Acompañante adicional en traslado',3800.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de validación'),
('ING-013','TRANSPORTES Y ACOMPAÑAMIENTOS','Recolección de visa / asistencia CAS CDMX',850.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de validación'),
('ING-014','TRANSPORTES Y ACOMPAÑAMIENTOS','Transporte adicional por cambio de cita',NULL,'POR_CONFIRMAR',0,NULL),
('ING-015','TRANSPORTES Y ACOMPAÑAMIENTOS','Diferencia por cambio de paquete sin transporte a paquete con transporte',NULL,'SEGUN_DIFERENCIA',0,NULL),
('ING-016','TRANSPORTES Y ACOMPAÑAMIENTOS','Traslado especial o servicio extraordinario',NULL,'SEGUN_COTIZACION',0,NULL),
('ING-017','VISAS INTERNACIONALES','Visa canadiense',6100.00,'FIJO',1,NULL),
('ING-018','VISAS INTERNACIONALES','eTA Canadá',1400.00,'FIJO',1,NULL),
('ING-019','VISAS INTERNACIONALES','Visa China',3800.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de confirmación'),
('ING-020','VISAS INTERNACIONALES','Visa Australia',8100.00,'FIJO',1,NULL),
('ING-021','VISAS INTERNACIONALES','Visa Cuba – honorarios',1400.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de confirmación'),
('ING-022','VISAS INTERNACIONALES','Visa Cuba – derecho consular',580.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de confirmación'),
('ING-023','VISAS INTERNACIONALES','Reapertura o nueva solicitud de visa internacional',NULL,'SEGUN_COTIZACION',0,NULL),
('ING-024','VISAS INTERNACIONALES','Servicio extraordinario de visa internacional',NULL,'SEGUN_COTIZACION',0,NULL),
('ING-025','PASAPORTES','Pasaporte mexicano primera vez',550.00,'FIJO',1,NULL),
('ING-026','PASAPORTES','Pasaporte mexicano renovación',550.00,'FIJO',1,NULL),
('ING-027','PASAPORTES','Pasaporte estadounidense primera vez',NULL,'POR_CONFIRMAR',0,NULL),
('ING-028','PASAPORTES','Pasaporte estadounidense renovación',NULL,'POR_CONFIRMAR',0,NULL),
('ING-029','PASAPORTES','Envío de pasaporte por mensajería',NULL,'SEGUN_COTIZACION',0,NULL),
('ING-030','PASAPORTES','Reprogramación de cita de pasaporte',NULL,'POR_CONFIRMAR',0,NULL),
('ING-031','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Acta de nacimiento',150.00,'FIJO',1,NULL),
('ING-032','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Impresión de documentos',7.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de confirmación'),
('ING-033','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Copias fotostáticas',3.00,'POR_CONFIRMAR',0,'Referencia histórica pendiente de confirmación'),
('ING-034','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Digitalización de documentos',NULL,'POR_CONFIRMAR',0,NULL),
('ING-035','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Servicio administrativo extraordinario',NULL,'SEGUN_COTIZACION',0,NULL),
('ING-036','DOCUMENTOS Y SERVICIOS ADMINISTRATIVOS','Envío DHL independiente',NULL,'SEGUN_COTIZACION',0,NULL);

INSERT IGNORE INTO income_payment_concepts (code, concept, treatment, is_commercial_payment, is_active) VALUES
('COB-001','Anticipo de cliente','Abono a venta',1,1),
('COB-002','Segundo abono','Abono a venta',1,1),
('COB-003','Liquidación de saldo','Abono a venta',1,1),
('COB-004','Pago total de contado','Cobro de venta',1,1),
('COB-005','Pago de diferencia por cambio de servicio','Ajuste a venta',1,1),
('COB-006','Recuperación de saldo vencido','Cobranza',1,1),
('COB-007','Pago recibido por servicio extraordinario','Vincular a servicio',1,1),
('MOV-001','Reembolso de proveedor','Movimiento no comercial',0,1),
('MOV-002','Devolución recibida de banco','Movimiento no comercial',0,1),
('MOV-003','Traspaso entre cuentas de la agencia','Movimiento interno',0,1),
('MOV-004','Corrección de ingreso registrado','Ajuste auditado',0,1),
('MOV-005','Fondo de caja recibido','Movimiento interno',0,1);

INSERT INTO income_sales
(application_id, catalog_id, catalog_code, client_name_snapshot, service_name_snapshot,
 advisor_id, quantity, unit_price, total_amount, contract_date, financial_status, notes, created_by)
SELECT
  a.id, NULL, 'LEGACY',
  COALESCE(NULLIF(a.client_name,''), CONCAT('Solicitud ', a.folio)),
  CONCAT_WS(' - ', a.type, NULLIF(a.subtype,'')),
  a.created_by, 1,
  COALESCE(fs.total_costs,0), COALESCE(fs.total_costs,0),
  DATE(a.created_at),
  CASE
    WHEN COALESCE(fs.balance,0) <= 0.009 AND COALESCE(fs.total_costs,0) > 0 THEN 'LIQUIDADO'
    WHEN COALESCE(fs.total_paid,0) > 0 THEN 'PARCIAL'
    ELSE 'PENDIENTE'
  END,
  'Migrado automáticamente desde el módulo financiero anterior.',
  a.created_by
FROM applications a
INNER JOIN financial_status fs ON fs.application_id=a.id
WHERE COALESCE(fs.total_costs,0) > 0
  AND NOT EXISTS (SELECT 1 FROM income_sales s WHERE s.application_id=a.id);

INSERT INTO income_payments
(sale_id, application_id, payment_concept_code, amount, payment_method, receiver_account,
 cash_envelope_id, reference, notes, payment_date, validation_status,
 registered_by, validated_by, validated_at, legacy_payment_id)
SELECT
  s.id, p.application_id, 'COB-004', p.amount,
  CASE
    WHEN UPPER(p.payment_method) LIKE '%EFECT%' THEN 'EFECTIVO'
    WHEN UPPER(p.payment_method) LIKE '%DEPOS%' THEN 'DEPOSITO_BANCARIO'
    WHEN UPPER(p.payment_method) LIKE '%TRANSFER%' THEN 'TRANSFERENCIA'
    WHEN UPPER(p.payment_method) LIKE '%TARJ%' OR UPPER(p.payment_method) LIKE '%TERMINAL%' THEN 'TERMINAL_TARJETA'
    ELSE 'OTRO'
  END,
  NULL, p.cash_envelope_id, p.reference, p.notes, p.payment_date, 'VALIDADO',
  p.registered_by, p.registered_by, p.created_at, p.id
FROM payments p
INNER JOIN income_sales s ON s.application_id=p.application_id
WHERE NOT EXISTS (SELECT 1 FROM income_payments ip WHERE ip.legacy_payment_id=p.id);

UPDATE financial_weekly_closures
SET week_end = DATE_ADD(week_start, INTERVAL 4 DAY)
WHERE status='ABIERTO';

SELECT 'Control de Ingresos V3 instalado correctamente' AS resultado;
