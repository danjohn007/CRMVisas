-- ============================================================
-- CRM VISAS V4 - WHATSAPP -> SOLICITUD FORMAL
-- Ejecutar UNA VEZ en la misma BD del CRM y del bot.
-- No modifica ni borra conversaciones, perfilamientos o solicitudes.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_application_links` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `conversation_id` bigint(20) DEFAULT NULL,
  `service_code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `prefill_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bot_application_contact_service` (`contact_id`,`service_code`),
  UNIQUE KEY `uk_bot_application_application` (`application_id`),
  KEY `idx_bot_application_conversation` (`conversation_id`),
  KEY `idx_bot_application_agent` (`assigned_agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibilidad con contactos que ya tenían una solicitud vinculada
-- mediante bot_contacts.application_id antes de esta mejora.
INSERT IGNORE INTO `bot_application_links`
(`contact_id`,`conversation_id`,`service_code`,`application_id`,`form_id`,`assigned_agent_id`,`created_by`,`prefill_json`)
SELECT
  bc.id,
  (
    SELECT c2.id
    FROM bot_conversations c2
    WHERE c2.contact_id = bc.id
    ORDER BY c2.id DESC
    LIMIT 1
  ),
  bc.service_code,
  bc.application_id,
  a.form_id,
  (
    SELECT c3.assigned_agent_id
    FROM bot_conversations c3
    WHERE c3.contact_id = bc.id
    ORDER BY c3.id DESC
    LIMIT 1
  ),
  a.created_by,
  NULL
FROM bot_contacts bc
INNER JOIN applications a ON a.id = bc.application_id
WHERE bc.application_id IS NOT NULL
  AND bc.service_code IS NOT NULL
  AND bc.service_code <> '';

SELECT 'V4 WhatsApp -> Solicitudes instalada correctamente' AS resultado;
