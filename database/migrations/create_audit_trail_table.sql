-- Migration: Create audit_trail table
-- Date: 2026-01-30
-- Description: Creates the audit_trail table for system audit logging

-- Create audit_trail table if it doesn't exist
CREATE TABLE IF NOT EXISTS `audit_trail` (
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

-- Insert sample audit data (optional - comment out if not needed)
INSERT INTO `audit_trail` (`user_id`, `user_name`, `user_email`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
(1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '200.68.174.77', '2026-01-29 18:26:00'),
(1, 'Carlos Administrador', 'admin@crmvisas.com', 'create', 'Solicitudes', 'Solicitud creada: VISA-2026-000001', '200.68.174.77', '2026-01-29 12:07:00'),
(1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '189.128.214.175', '2026-01-29 12:01:00'),
(2, 'María González López', 'gerente@crmvisas.com', 'update', 'Solicitudes', 'Cambio de estatus: VISA-2026-000001 - En revisión', '187.194.216.48', '2026-01-28 18:22:00'),
(1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '189.141.15.215', '2026-01-28 18:16:00'),
(3, 'Juan Pérez Ramírez', 'asesor1@crmvisas.com', 'create', 'Solicitudes', 'Solicitud creada: VISA-2026-000002', '187.194.216.48', '2026-01-28 12:47:00')
ON DUPLICATE KEY UPDATE id=id;
