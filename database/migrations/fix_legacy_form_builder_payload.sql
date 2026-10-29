-- Normaliza formularios legacy para que fields_json siempre tenga formato:
-- {"fields":[...]}
-- Compatible con MySQL 5.7.8+

START TRANSACTION;

UPDATE forms
SET fields_json = JSON_OBJECT('fields', CAST(fields_json AS JSON))
WHERE JSON_VALID(fields_json) = 1
  AND JSON_TYPE(CAST(fields_json AS JSON)) = 'ARRAY';

COMMIT;
