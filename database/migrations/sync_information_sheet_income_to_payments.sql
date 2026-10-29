-- Sincroniza ingresos capturados en information_sheets hacia payments
-- Compatible con MySQL 5.7

INSERT INTO `payments` (`application_id`, `amount`, `payment_method`, `reference`, `notes`, `registered_by`, `payment_date`)
SELECT
    i.`application_id`,
    i.`amount_paid`,
    'Sistema' AS `payment_method`,
    CONCAT('INFO-SHEET-', i.`application_id`) AS `reference`,
    'Pago sincronizado desde hoja de información' AS `notes`,
    i.`created_by` AS `registered_by`,
    i.`entry_date` AS `payment_date`
FROM `information_sheets` i
WHERE i.`amount_paid` IS NOT NULL
  AND i.`amount_paid` > 0
  AND i.`entry_date` IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `payments` p
      WHERE p.`application_id` = i.`application_id`
  );
