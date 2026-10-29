-- Migration: Add SMTP configuration keys to global_config
-- Date: 2026-02-24

INSERT INTO `global_config` (`config_key`, `config_value`, `config_type`) VALUES
('smtp_user',      'crmvisas@recursoshumanos.digital', 'text'),
('smtp_password',  ']*YuqUx#QAM4',                    'text'),
('smtp_host',      'recursoshumanos.digital',           'text'),
('smtp_port',      '587',                               'text'),
('smtp_imap_port', '993',                               'text'),
('smtp_pop3_port', '995',                               'text')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
