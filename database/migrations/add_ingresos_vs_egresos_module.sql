-- Módulo Ingresos vs Egresos
-- Crear tabla para registrar egresos del sistema

CREATE TABLE IF NOT EXISTS `financial_expenses` (
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
