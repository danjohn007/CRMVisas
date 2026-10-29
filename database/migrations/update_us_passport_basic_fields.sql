-- Ajuste para el trámite:
-- CUESTIONARIO ÚNICO - PASAPORTE AMERICANO (Pasaporte - Única Vez)
-- Se actualizan los campos básicos a:
-- 1) Nombre del cliente
-- 2) El pago
-- 3) Fecha de la cita

SET @target_forms = (
  SELECT COUNT(*)
  FROM `forms`
  WHERE `name` = 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO'
    AND `type` = 'Pasaporte'
    AND `subtype` = 'Única Vez'
);

SELECT @target_forms AS `target_forms`;

UPDATE `forms`
SET
  `fields_json` = '{"fields":[{"id":"nombre_cliente","type":"text","label":"Nombre del cliente","required":true},{"id":"pago","type":"text","label":"El pago","required":true},{"id":"fecha_cita","type":"date","label":"Fecha de la cita","required":true}]}',
  `version` = `version` + 1,
  `updated_at` = CURRENT_TIMESTAMP
WHERE `name` = 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO'
  AND `type` = 'Pasaporte'
  AND `subtype` = 'Única Vez'
  AND @target_forms = 1;

SELECT ROW_COUNT() AS `updated_forms`;
