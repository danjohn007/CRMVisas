-- Corrección de generación de enlaces de formularios (compatibilidad de datos legacy)
-- Ejecutar en la base de datos actual: tramitev_crmvisas

-- 1) Mantener compatibilidad con solicitudes históricas:
--    Si ya tienen form_id pero no form_link_id, enlazar al mismo formulario.
UPDATE applications
SET form_link_id = form_id
WHERE form_link_id IS NULL
  AND form_id IS NOT NULL;

-- 2) Asegurar token público para formularios existentes sin token.
--    RANDOM_BYTES(32) -> HEX = 64 caracteres (compatible con forms.public_token VARCHAR(64)).
UPDATE forms
SET public_token = LOWER(HEX(RANDOM_BYTES(32)))
WHERE public_token IS NULL
   OR public_token = '';

-- 3) Sincronizar public_enabled con is_published para todos los formularios publicados.
--    Esto corrige formularios publicados que quedaron con public_enabled = 0 tras
--    ejecutar la migración add_enhancements_features.sql en una base de datos existente.
UPDATE forms
SET public_enabled = 1
WHERE is_published = 1
  AND (public_enabled IS NULL OR public_enabled = 0);

-- 4) También habilitar acceso público para formularios vinculados a solicitudes enviadas/completadas
--    que pudieran estar publicados pero aún con public_enabled = 0.
UPDATE forms f
INNER JOIN (
    SELECT DISTINCT form_link_id AS linked_form_id
    FROM applications
    WHERE form_link_id IS NOT NULL
      AND form_link_status IN ('enviado', 'completado')
    UNION
    SELECT DISTINCT form_id AS linked_form_id
    FROM applications
    WHERE form_id IS NOT NULL
      AND form_link_status IN ('enviado', 'completado')
) linked_forms ON linked_forms.linked_form_id = f.id
SET f.public_enabled = 1
WHERE f.is_published = 1
  AND (f.public_enabled IS NULL OR f.public_enabled = 0);
