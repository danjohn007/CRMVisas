-- ============================================================
-- CRM VISAS V5.9 - SOBRES HISTÓRICOS + VANESA + TRASLADOS
-- MySQL 5.7+
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `monthly_cash_envelopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `month_start` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_monthly_cash_envelopes_month` (`month_start`),
  KEY `idx_monthly_cash_envelopes_created_by` (`created_by`),
  CONSTRAINT `fk_monthly_cash_envelopes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_monthly_cash_envelopes_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `global_config` (`config_key`,`config_value`,`config_type`)
VALUES ('cash_envelope_system_start','2026-11-01','text')
ON DUPLICATE KEY UPDATE `config_value`=VALUES(`config_value`);

INSERT INTO `global_config` (`config_key`,`config_value`,`config_type`)
VALUES ('site_name','Tramite Visa Americana En Queretaro By Vanesa Torres','text')
ON DUPLICATE KEY UPDATE `config_value`=VALUES(`config_value`);

-- Corregir nombre visible de la propietaria en configuración y usuarios.
UPDATE `global_config`
SET `config_value`=REPLACE(REPLACE(`config_value`,'Vaneza','Vanesa'),'VANEZA','VANESA')
WHERE `config_value` LIKE '%Vaneza%' OR `config_value` LIKE '%VANEZA%';

UPDATE `users`
SET `full_name`=REPLACE(REPLACE(`full_name`,'Vaneza','Vanesa'),'VANEZA','VANESA')
WHERE `full_name` LIKE '%Vaneza%' OR `full_name` LIKE '%VANEZA%';

SELECT 'V5.9 instalada: Sobres históricos, Vanesa y control de traslados' AS resultado;
