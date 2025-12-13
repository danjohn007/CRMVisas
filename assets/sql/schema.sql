-- CRM Visas Database Schema
-- MySQL 5.7 Compatible
-- Sistema completo de gestión de trámites de visas

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `crm_visas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crm_visas`;

-- ============================================
-- MÓDULO DE USUARIOS Y SEGURIDAD (RF59-RF70)
-- ============================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','supervisor','asesor','cliente') NOT NULL DEFAULT 'cliente',
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de auditoría (RF66)
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE CLIENTES (RF01-RF05)
-- ============================================

CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('M','F','other') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `notes` text,
  `status` enum('active','inactive','blacklisted') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documentos de clientes (RF03)
CREATE TABLE `client_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(20) NOT NULL,
  `file_size` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de interacciones (RF05)
CREATE TABLE `client_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `interaction_type` enum('call','email','meeting','note','whatsapp','other') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `interaction_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`interaction_date`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE SERVICIOS/VISAS (RF06-RF11)
-- ============================================

CREATE TABLE `visa_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text,
  `country` varchar(100) NOT NULL,
  `visa_type` varchar(100) NOT NULL,
  `processing_time` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) NOT NULL DEFAULT 'MXN',
  `required_documents` text,
  `status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_country` (`country`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Editor de formularios (RF07-RF11)
CREATE TABLE `form_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `service_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive','draft') NOT NULL DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`service_id`) REFERENCES `visa_services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos personalizados de formularios (RF08-RF09)
CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','number','email','date','select','radio','checkbox','file') NOT NULL,
  `field_options` text,
  `placeholder` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `validation_rules` text,
  `conditional_logic` text,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `help_text` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_order` (`display_order`),
  FOREIGN KEY (`template_id`) REFERENCES `form_templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE SOLICITUDES (RF12-RF15)
-- ============================================

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('draft','pending','in_process','documents_review','payment_pending','completed','cancelled','rejected') NOT NULL DEFAULT 'draft',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `submission_date` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `estimated_completion` date DEFAULT NULL,
  `notes` text,
  `internal_notes` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `idx_client` (`client_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_assigned` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `visa_services`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de estados de solicitudes (RF14)
CREATE TABLE `request_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuestas de formularios (RF16-RF23)
CREATE TABLE `form_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `response_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_field` (`field_id`),
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enlaces públicos de cuestionarios (RF19-RF23)
CREATE TABLE `public_form_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `unique_token` varchar(100) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `max_submissions` int(11) DEFAULT '1',
  `submission_count` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_accessed` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`unique_token`),
  KEY `idx_request` (`request_id`),
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE PAGOS (RF24-RF28)
-- ============================================

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'MXN',
  `payment_method` enum('cash','card','transfer','paypal','stripe','other') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text,
  `receipt_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `idx_request` (`request_id`),
  KEY `idx_status` (`payment_status`),
  KEY `idx_due_date` (`due_date`),
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO FINANCIERO (RF53, RF56, RF58)
-- ============================================

CREATE TABLE `financial_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `financial_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'MXN',
  `description` text NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_payment` (`payment_id`),
  FOREIGN KEY (`category_id`) REFERENCES `financial_categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comisiones de agentes (RF56)
CREATE TABLE `agent_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE CHECKLIST Y REVISIÓN (RF38-RF42)
-- ============================================

CREATE TABLE `document_checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `description` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  FOREIGN KEY (`service_id`) REFERENCES `visa_services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `request_document_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `checklist_item_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `status` enum('pending','submitted','approved','rejected','missing') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `comments` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_checklist` (`checklist_item_id`),
  KEY `idx_document` (`document_id`),
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`checklist_item_id`) REFERENCES `document_checklists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`document_id`) REFERENCES `client_documents`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE NOTIFICACIONES (RF14)
-- ============================================

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE CONFIGURACIÓN (RF75, RF77)
-- ============================================

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) NOT NULL,
  `setting_type` enum('text','textarea','number','boolean','color','email','url','json') NOT NULL DEFAULT 'text',
  `description` text,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_group` (`setting_group`),
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas de email (RF77)
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables` text,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MÓDULO DE CALENDARIO
-- ============================================

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_type` enum('meeting','call','deadline','reminder','other') NOT NULL DEFAULT 'meeting',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3788d8',
  `is_all_day` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_minutes` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_start` (`start_datetime`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE EJEMPLO - QUERÉTARO
-- ============================================

-- Usuario administrador por defecto
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `phone`, `status`) VALUES
('admin', 'admin@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Sistema', 'admin', '4421234567', 'active'),
('supervisor1', 'supervisor@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María García Supervisor', 'supervisor', '4429876543', 'active'),
('asesor1', 'asesor1@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez Asesor', 'asesor', '4425551234', 'active'),
('asesor2', 'asesor2@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana López Asesora', 'asesor', '4425559876', 'active');

-- Password para todos: password123

-- Configuración del sistema
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `description`) VALUES
('site_name', 'CRM Visas Querétaro', 'general', 'text', 'Nombre del sitio'),
('site_logo', '', 'general', 'text', 'Ruta del logo del sitio'),
('primary_email', 'contacto@crmvisas.com', 'email', 'email', 'Correo principal del sistema'),
('contact_phone', '442-123-4567', 'contact', 'text', 'Teléfono de contacto principal'),
('contact_phone_alt', '442-987-6543', 'contact', 'text', 'Teléfono de contacto alternativo'),
('business_hours', 'Lunes a Viernes 9:00 - 18:00, Sábados 9:00 - 14:00', 'contact', 'text', 'Horario de atención'),
('primary_color', '#3b82f6', 'theme', 'color', 'Color primario del sistema'),
('secondary_color', '#10b981', 'theme', 'color', 'Color secundario del sistema'),
('paypal_client_id', '', 'payment', 'text', 'PayPal Client ID'),
('paypal_secret', '', 'payment', 'text', 'PayPal Secret'),
('paypal_mode', 'sandbox', 'payment', 'text', 'PayPal Mode (sandbox/live)'),
('stripe_public_key', '', 'payment', 'text', 'Stripe Public Key'),
('stripe_secret_key', '', 'payment', 'text', 'Stripe Secret Key'),
('qr_api_key', '', 'api', 'text', 'API Key para generación de QR'),
('timezone', 'America/Mexico_City', 'general', 'text', 'Zona horaria del sistema'),
('currency', 'MXN', 'general', 'text', 'Moneda por defecto');

-- Servicios de visa de ejemplo
INSERT INTO `visa_services` (`name`, `code`, `description`, `country`, `visa_type`, `processing_time`, `base_price`, `currency`, `status`, `created_by`) VALUES
('Visa de Turista USA', 'USA-TOURIST', 'Visa de turista para Estados Unidos - Tipo B1/B2', 'Estados Unidos', 'Turista', '15-30 días', 5000.00, 'MXN', 'active', 1),
('Visa de Estudiante USA', 'USA-STUDENT', 'Visa de estudiante para Estados Unidos - Tipo F1', 'Estados Unidos', 'Estudiante', '30-60 días', 8000.00, 'MXN', 'active', 1),
('Visa de Turista Canadá', 'CAN-TOURIST', 'Visa de visitante para Canadá', 'Canadá', 'Turista', '20-30 días', 4500.00, 'MXN', 'active', 1),
('Visa Schengen', 'EUR-SCHENGEN', 'Visa Schengen para Europa', 'Zona Schengen', 'Turista', '15-20 días', 6000.00, 'MXN', 'active', 1);

-- Categorías financieras
INSERT INTO `financial_categories` (`name`, `type`, `description`, `status`) VALUES
('Pago de servicios de visa', 'income', 'Ingresos por servicios de trámite de visas', 'active'),
('Honorarios consulares', 'expense', 'Pagos a consulados y embajadas', 'active'),
('Gastos operativos', 'expense', 'Gastos generales de operación', 'active'),
('Comisiones de agentes', 'expense', 'Pago de comisiones a asesores', 'active'),
('Servicios adicionales', 'income', 'Ingresos por servicios complementarios', 'active');

-- Clientes de ejemplo de Querétaro
INSERT INTO `clients` (`first_name`, `last_name`, `birth_date`, `gender`, `nationality`, `email`, `phone`, `mobile`, `address`, `city`, `state`, `postal_code`, `country`, `status`, `created_by`) VALUES
('Carlos', 'Hernández Ruiz', '1985-03-15', 'M', 'Mexicana', 'carlos.hernandez@email.com', '4421234567', '4421112233', 'Av. Constituyentes 123, Centro', 'Querétaro', 'Querétaro', '76000', 'México', 'active', 1),
('Laura', 'Martínez Sánchez', '1990-07-22', 'F', 'Mexicana', 'laura.martinez@email.com', '4429876543', '4429998877', 'Blvd. Bernardo Quintana 456, Juriquilla', 'Querétaro', 'Querétaro', '76230', 'México', 'active', 1),
('Roberto', 'Flores García', '1978-11-30', 'M', 'Mexicana', 'roberto.flores@email.com', '4425551234', '4425554433', 'Prolongación Corregidora 789, El Marqués', 'El Marqués', 'Querétaro', '76240', 'México', 'active', 1);

-- Plantillas de email
INSERT INTO `email_templates` (`name`, `code`, `subject`, `body`, `variables`, `is_active`) VALUES
('Bienvenida Cliente', 'welcome_client', 'Bienvenido a CRM Visas', 'Hola {{client_name}},\n\nBienvenido a nuestro sistema de gestión de visas.\n\nSaludos,\nEquipo CRM Visas', 'client_name', 1),
('Cambio de Estado', 'status_change', 'Actualización de tu solicitud {{request_number}}', 'Hola {{client_name}},\n\nTu solicitud {{request_number}} ha cambiado de estado a: {{new_status}}\n\n{{notes}}\n\nSaludos,\nEquipo CRM Visas', 'client_name,request_number,new_status,notes', 1),
('Recordatorio de Pago', 'payment_reminder', 'Recordatorio de pago pendiente', 'Hola {{client_name}},\n\nTe recordamos que tienes un pago pendiente por ${{amount}} {{currency}} con vencimiento el {{due_date}}.\n\nReferencia: {{payment_reference}}\n\nSaludos,\nEquipo CRM Visas', 'client_name,amount,currency,due_date,payment_reference', 1),
('Enlace Cuestionario', 'questionnaire_link', 'Completa tu cuestionario de visa', 'Hola {{client_name}},\n\nPor favor completa tu cuestionario en el siguiente enlace:\n{{link}}\n\nEste enlace expira el: {{expiry_date}}\n\nSaludos,\nEquipo CRM Visas', 'client_name,link,expiry_date', 1);

-- Checklist de documentos para USA Tourist
INSERT INTO `document_checklists` (`service_id`, `document_name`, `description`, `is_required`, `display_order`) VALUES
(1, 'Pasaporte vigente', 'Pasaporte con vigencia mínima de 6 meses', 1, 1),
(1, 'Fotografía reciente', 'Foto tamaño pasaporte fondo blanco', 1, 2),
(1, 'Comprobante de ingresos', 'Estados de cuenta bancarios últimos 3 meses', 1, 3),
(1, 'Comprobante de domicilio', 'No mayor a 3 meses', 1, 4),
(1, 'Itinerario de viaje', 'Reservaciones de vuelo y hotel', 0, 5);

COMMIT;
