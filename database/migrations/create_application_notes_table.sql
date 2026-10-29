-- Crear tabla para indicaciones/notas de solicitudes
-- Esta tabla permite a Administradores y Gerentes agregar indicaciones
-- que los Asesores pueden ver

USE `recursos_visas`;

-- Tabla de Indicaciones/Notas de Solicitudes
DROP TABLE IF EXISTS `application_notes`;
CREATE TABLE `application_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `note_text` text NOT NULL,
  `is_important` tinyint(1) DEFAULT 0 COMMENT 'Marcar como importante',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `created_by` (`created_by`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `application_notes_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunas notas de ejemplo
INSERT INTO `application_notes` (`application_id`, `note_text`, `is_important`, `created_by`) VALUES
(1, 'El cliente solicitó agendar cita para el próximo lunes a las 10:00 AM', 1, 2),
(1, 'Pendiente validar domicilio en comprobante', 0, 2),
(2, 'Documentación completa, proceder con el trámite', 0, 2),
(4, 'Cliente preguntó por tiempo estimado de entrega. Informar 4-6 semanas', 1, 2);
