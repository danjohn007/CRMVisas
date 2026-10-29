-- CRM de Solicitudes de Visas y Pasaportes
-- Base de datos con datos de ejemplo del estado de Querétaro

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `crm_visas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crm_visas`;

-- Tabla de Usuarios
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Administrador','Gerente','Asesor') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios de ejemplo
-- Contraseña para todos: password123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `phone`, `is_active`) VALUES
('admin', 'admin@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Administrador', 'Administrador', '4421234567', 1),
('gerente01', 'gerente@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María González López', 'Gerente', '4421234568', 1),
('asesor01', 'asesor1@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez Ramírez', 'Asesor', '4421234569', 1),
('asesor02', 'asesor2@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Martínez Sánchez', 'Asesor', '4421234570', 1);

-- Tabla de Formularios Dinámicos
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` enum('Visa','Pasaporte') NOT NULL,
  `subtype` varchar(50) DEFAULT NULL COMMENT 'Primera vez, Renovación, etc',
  `version` int(11) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  `fields_json` longtext NOT NULL COMMENT 'Estructura del formulario en JSON',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formularios de ejemplo
INSERT INTO `forms` (`name`, `description`, `type`, `subtype`, `version`, `is_published`, `fields_json`, `created_by`) VALUES
('Visa Americana - Primera Vez', 'Formulario para solicitud de visa americana por primera vez', 'Visa', 'Primera Vez', 1, 1, '{"fields":[{"id":"nombre","type":"text","label":"Nombre Completo","required":true},{"id":"pasaporte","type":"text","label":"Número de Pasaporte","required":true},{"id":"fecha_nacimiento","type":"date","label":"Fecha de Nacimiento","required":true},{"id":"motivo","type":"select","label":"Motivo del Viaje","options":["Turismo","Negocios","Estudios","Trabajo"],"required":true},{"id":"documento_pasaporte","type":"file","label":"Copia del Pasaporte","required":true}]}', 1),
('Pasaporte Mexicano - Renovación', 'Formulario para renovación de pasaporte mexicano', 'Pasaporte', 'Renovación', 1, 1, '{"fields":[{"id":"nombre","type":"text","label":"Nombre Completo","required":true},{"id":"curp","type":"text","label":"CURP","required":true},{"id":"pasaporte_anterior","type":"text","label":"Número de Pasaporte Anterior","required":true},{"id":"acta_nacimiento","type":"file","label":"Acta de Nacimiento","required":true}]}', 1);

-- Tabla de Solicitudes
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) NOT NULL COMMENT 'VISA-YYYY-NNNNNN',
  `form_id` int(11) NOT NULL,
  `form_version` int(11) NOT NULL,
  `type` enum('Visa','Pasaporte') NOT NULL,
  `subtype` varchar(50) DEFAULT NULL,
  `status` enum('Nuevo','Validando respuestas','Listo para solicitud','En espera de pago consular','Cita programada','En espera de resultado','Trámite cerrado','Formulario recibido','Pago verificado','En elaboración de hoja de información','En revisión','Rechazado (requiere corrección)','Aprobado','Cita solicitada','Cita confirmada','Proceso en embajada','Finalizado') DEFAULT 'Nuevo',
  `data_json` longtext NOT NULL COMMENT 'Datos del formulario en JSON',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `form_id` (`form_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitudes de ejemplo
INSERT INTO `applications` (`folio`, `form_id`, `form_version`, `type`, `subtype`, `status`, `data_json`, `created_by`) VALUES
('VISA-2026-000001', 1, 1, 'Visa', 'Primera Vez', 'En revisión', '{"nombre":"Roberto García Méndez","pasaporte":"M123456789","fecha_nacimiento":"1985-05-15","motivo":"Turismo"}', 3),
('VISA-2026-000002', 1, 1, 'Visa', 'Primera Vez', 'Pago verificado', '{"nombre":"Laura Hernández Torres","pasaporte":"M987654321","fecha_nacimiento":"1990-08-22","motivo":"Negocios"}', 3),
('VISA-2026-000003', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Pedro Ramírez Luna","curp":"RALP850315HQTMND01","pasaporte_anterior":"M111222333"}', 4),
('VISA-2026-000004', 1, 1, 'Visa', 'Primera Vez', 'Aprobado', '{"nombre":"Diana Flores Castro","pasaporte":"M555666777","fecha_nacimiento":"1995-03-10","motivo":"Estudios"}', 3),
('VISA-2026-000005', 2, 1, 'Pasaporte', 'Renovación', 'Proceso en embajada', '{"nombre":"Miguel Ángel Ortiz","curp":"OIGM920612HQTRTG03","pasaporte_anterior":"M444555666"}', 4);

-- Tabla de Historial de Estatus
DROP TABLE IF EXISTS `status_history`;
CREATE TABLE `status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comment` text,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de ejemplo
INSERT INTO `status_history` (`application_id`, `previous_status`, `new_status`, `comment`, `changed_by`) VALUES
(1, 'Formulario recibido', 'En revisión', 'Documentos recibidos, iniciando revisión', 2),
(2, 'Formulario recibido', 'Pago verificado', 'Pago confirmado', 2),
(3, 'Formulario recibido', 'Proceso en embajada', 'Trámite en SRE', 2),
(3, 'Proceso en embajada', 'Aprobado', 'Pasaporte listo', 2),
(3, 'Aprobado', 'Finalizado', 'Entregado al cliente', 2),
(4, 'Formulario recibido', 'En revisión', 'Revisando documentación', 2),
(4, 'En revisión', 'Aprobado', 'Visa aprobada', 2);

-- Tabla de Documentos
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `version` int(11) DEFAULT 1,
  `is_validated` tinyint(1) DEFAULT 0,
  `validation_comment` text,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Costos Financieros
DROP TABLE IF EXISTS `financial_costs`;
CREATE TABLE `financial_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `concept` varchar(200) NOT NULL COMMENT 'Honorarios, Derechos, Servicios adicionales',
  `amount` decimal(10,2) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `financial_costs_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_costs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costos de ejemplo
INSERT INTO `financial_costs` (`application_id`, `concept`, `amount`, `created_by`) VALUES
(1, 'Honorarios de gestión', 2500.00, 2),
(1, 'Derechos consulares', 3500.00, 2),
(2, 'Honorarios de gestión', 2500.00, 2),
(2, 'Derechos consulares', 3500.00, 2),
(3, 'Honorarios de renovación', 1800.00, 2),
(3, 'Derechos SRE', 1345.00, 2),
(4, 'Honorarios de gestión', 2500.00, 2),
(4, 'Derechos consulares', 3500.00, 2);

-- Tabla de Pagos
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'Efectivo, Transferencia, Tarjeta, PayPal',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `registered_by` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `registered_by` (`registered_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos de ejemplo
INSERT INTO `payments` (`application_id`, `amount`, `payment_method`, `reference`, `registered_by`, `payment_date`) VALUES
(1, 3000.00, 'Transferencia', 'TRANS-20260115-001', 2, '2026-01-15'),
(2, 6000.00, 'Efectivo', NULL, 2, '2026-01-18'),
(3, 3145.00, 'Tarjeta', 'CARD-****1234', 2, '2026-01-10'),
(4, 6000.00, 'PayPal', 'PP-20260120-456', 2, '2026-01-20');

-- Tabla de Egresos Generales
DROP TABLE IF EXISTS `financial_expenses`;
CREATE TABLE `financial_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `concept` varchar(200) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_financial_expenses_expense_date` (`expense_date`),
  KEY `idx_financial_expenses_concept` (`concept`),
  KEY `idx_financial_expenses_created_by` (`created_by`),
  CONSTRAINT `financial_expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Estado Financiero por Solicitud
DROP TABLE IF EXISTS `financial_status`;
CREATE TABLE `financial_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `total_costs` decimal(10,2) DEFAULT 0.00,
  `total_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pendiente','Parcial','Pagado') DEFAULT 'Pendiente',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  CONSTRAINT `financial_status_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estado financiero de ejemplo
INSERT INTO `financial_status` (`application_id`, `total_costs`, `total_paid`, `balance`, `status`) VALUES
(1, 6000.00, 3000.00, 3000.00, 'Parcial'),
(2, 6000.00, 6000.00, 0.00, 'Pagado'),
(3, 3145.00, 3145.00, 0.00, 'Pagado'),
(4, 6000.00, 6000.00, 0.00, 'Pagado');

-- Tabla de Configuración Global
DROP TABLE IF EXISTS `global_config`;
CREATE TABLE `global_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(50) DEFAULT 'text' COMMENT 'text, json, file',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuraciones por defecto
INSERT INTO `global_config` (`config_key`, `config_value`, `config_type`) VALUES
('site_name', 'CRM Visas y Pasaportes Querétaro', 'text'),
('site_logo', '', 'file'),
('email_from', 'noreply@crmvisas.com', 'text'),
('contact_phone', '442-123-4567', 'text'),
('contact_phone_2', '442-765-4321', 'text'),
('business_hours', 'Lunes a Viernes: 9:00 AM - 6:00 PM, Sábados: 9:00 AM - 2:00 PM', 'text'),
('primary_color', '#3b82f6', 'text'),
('secondary_color', '#1e40af', 'text'),
('paypal_client_id', '', 'text'),
('paypal_secret', '', 'text'),
('qr_api_key', '', 'text'),
('qr_api_url', '', 'text'),
('smtp_user', 'crmvisas@recursoshumanos.digital', 'text'),
('smtp_password', '', 'text'),
('smtp_host', 'recursoshumanos.digital', 'text'),
('smtp_port', '587', 'text'),
('smtp_imap_port', '993', 'text'),
('smtp_pop3_port', '995', 'text'),
('geo_login_enabled', '0', 'boolean'),
('geo_login_address', '', 'text'),
('geo_login_latitude', '', 'number'),
('geo_login_longitude', '', 'number'),
('geo_login_radius_meters', '100', 'number'),
('login_max_attempts', '5', 'number'),
('login_lockout_minutes', '15', 'number'),
('session_idle_timeout_minutes', '30', 'number');

-- Tabla de Intentos de Login
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `allowed_latitude` decimal(10,8) DEFAULT NULL,
  `allowed_longitude` decimal(11,8) DEFAULT NULL,
  `distance_meters` decimal(10,2) DEFAULT NULL,
  `location_status` varchar(50) DEFAULT NULL COMMENT 'not_required, missing, invalid, inside_range, outside_range',
  `user_agent` text,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_lookup` (`username`, `ip_address`, `attempted_at`),
  KEY `idx_login_attempts_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Sucursales (ubicaciones permitidas para el login geolocalizado de asesores)
DROP TABLE IF EXISTS `sucursales`;
CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `radio_metros` int(11) NOT NULL DEFAULT 100,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Dispositivos HikVision
DROP TABLE IF EXISTS `hikvision_devices`;
CREATE TABLE `hikvision_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `port` int(11) DEFAULT 80,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Dispositivos Shelly Cloud
DROP TABLE IF EXISTS `shelly_devices`;
CREATE TABLE `shelly_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Auditoría del Sistema
DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'login, logout, create, update, delete, etc',
  `module` varchar(100) NOT NULL COMMENT 'usuarios, solicitudes, formularios, etc',
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `metadata_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Centro de Control Administrativo
DROP TABLE IF EXISTS `admin_control_events`;
CREATE TABLE `admin_control_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(80) NOT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata_json` text DEFAULT NULL,
  `priority` enum('baja','normal','alta','critica') NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_control_module` (`module`),
  KEY `idx_admin_control_action` (`action`),
  KEY `idx_admin_control_application` (`application_id`),
  KEY `idx_admin_control_folio` (`folio`),
  KEY `idx_admin_control_user` (`user_id`),
  KEY `idx_admin_control_priority` (`priority`),
  KEY `idx_admin_control_read_created` (`is_read`,`created_at`),
  CONSTRAINT `admin_control_events_application_fk`
    FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admin_control_events_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Crear índices adicionales para optimización
CREATE INDEX idx_applications_created_by_status ON applications(created_by, status);
CREATE INDEX idx_applications_created_at ON applications(created_at);
CREATE INDEX idx_status_history_created_at ON status_history(created_at);
CREATE INDEX idx_payments_payment_date ON payments(payment_date);
