-- Migration: Create admin control events table
-- Internal intranet control center for administrators.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_control_events` (
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
