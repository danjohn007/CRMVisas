-- ============================================================
-- CRM VISAS V5.8 - MÓDULO TRASLADOS
-- Operación logística sin información financiera.
-- MySQL 5.7+
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `travel_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_date` date NOT NULL,
  `capacity` smallint(5) unsigned NOT NULL,
  `vehicle_label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVO','FINALIZADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) NOT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `reopened_by` int(11) DEFAULT NULL,
  `reopened_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_travel_transfer_date` (`transfer_date`),
  KEY `idx_travel_transfer_status_date` (`status`,`transfer_date`),
  KEY `idx_travel_transfer_created_by` (`created_by`),
  KEY `idx_travel_transfer_closed_by` (`closed_by`),
  CONSTRAINT `fk_travel_transfer_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_travel_transfer_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_travel_transfer_reopened_by` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `travel_transfer_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `advisor_id` int(11) NOT NULL,
  `seat_number` smallint(5) unsigned NOT NULL,
  `client_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_appointment` datetime DEFAULT NULL,
  `is_ready` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_travel_transfer_seat` (`transfer_id`,`seat_number`),
  UNIQUE KEY `uk_travel_transfer_application` (`transfer_id`,`application_id`),
  KEY `idx_travel_transfer_clients_advisor` (`advisor_id`),
  KEY `idx_travel_transfer_clients_ready` (`transfer_id`,`is_ready`),
  KEY `idx_travel_transfer_clients_created_by` (`created_by`),
  CONSTRAINT `fk_travel_transfer_clients_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `travel_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_travel_transfer_clients_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_travel_transfer_clients_advisor` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_travel_transfer_clients_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_travel_transfer_clients_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Módulo TRASLADOS V5.8 instalado correctamente' AS resultado;
