-- =====================================================================
-- Migration: Nuevo estatus "Validando respuestas"
-- Agrega el estatus "Validando respuestas" entre "Nuevo" y
-- "Listo para solicitud" en la columna status de applications.
--
-- Ejecutar en la base de datos: tramitev_crmvisas
-- Fecha: 2026-05-19
-- =====================================================================

-- Paso 1: Ampliar el ENUM de la columna status para incluir el nuevo valor.
-- Se reemplaza la definición completa del ENUM conservando todos los valores
-- existentes y añadiendo 'Validando respuestas' después de 'Nuevo'.
ALTER TABLE `applications`
  MODIFY COLUMN `status` ENUM(
    'Nuevo',
    'Validando respuestas',
    'Listo para solicitud',
    'En espera de pago consular',
    'Cita programada',
    'En espera de resultado',
    'Trámite cerrado',
    'Formulario recibido',
    'Pago verificado',
    'En elaboración de hoja de información',
    'En revisión',
    'Rechazado (requiere corrección)',
    'Aprobado',
    'Cita solicitada',
    'Cita confirmada',
    'Proceso en embajada',
    'Finalizado'
  ) DEFAULT 'Nuevo';

-- Verificación: muestra la distribución actual de estatus tras el cambio.
SELECT status, COUNT(*) AS total
FROM applications
GROUP BY status
ORDER BY FIELD(status,
  'Nuevo',
  'Validando respuestas',
  'Listo para solicitud',
  'En espera de pago consular',
  'Cita programada',
  'En espera de resultado',
  'Trámite cerrado'
);
