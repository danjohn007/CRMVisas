-- ============================================================
-- LIMPIEZA SEGURA DE PRUEBAS FINANCIERAS
-- Base revisada: tramitev_crmvisas(1).sql
--
-- Limpia exclusivamente los ejercicios financieros detectados:
-- 1) Juan López - 09/10/2026 (prueba antigua de $1,600)
-- 2) Santi      - 14/10/2026 (venta $6,520 con 2 pagos)
--
-- NO borra:
-- - Solicitudes 247 y 248
-- - Cliente Santi
-- - Conversación de WhatsApp / perfilamiento del bot
-- - Usuarios
-- - Formularios
-- - Catálogo de ingresos
-- - Egresos históricos reales
-- ============================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- 1. Eliminar movimientos físicos de efectivo generados por las pruebas.
DELETE FROM financial_envelope_movements
WHERE envelope_id = 2
   OR (application_id IN (247,248)
       AND movement_date BETWEEN '2026-10-09' AND '2026-10-14');

-- 2. Reiniciar el sobre PRUEBA para poder reutilizarlo desde cero.
UPDATE financial_envelopes
SET opening_balance = 0.00,
    opened_at = '2026-10-14',
    status = 'ACTIVO',
    closed_by = NULL,
    closed_at = NULL,
    notes = 'Sobre de prueba reiniciado para repetir validaciones.',
    updated_at = NOW()
WHERE id = 2 AND name = 'PRUEBA';

-- 3. Eliminar pagos espejo/legacy de los ejercicios.
DELETE FROM payments
WHERE (application_id = 247 AND payment_group_key = 'PAGO-20261009141223-582ba63f')
   OR (application_id = 248 AND source_key IN ('INCOME-CONTROL-1','INCOME-CONTROL-2'));

-- 4. Eliminar pagos del nuevo Control de Ingresos de Santi.
DELETE FROM income_payments
WHERE application_id = 248
  AND sale_id = 1
  AND payment_date = '2026-10-14';

-- 5. Eliminar exclusivamente la venta de prueba de Santi.
DELETE FROM income_sales
WHERE id = 1
  AND application_id = 248
  AND catalog_code = 'ING-003'
  AND total_amount = 6520.00
  AND contract_date = '2026-10-14';

-- 6. Regresar los dos expedientes usados en pruebas a estado financiero limpio.
UPDATE financial_status
SET total_costs = 0.00,
    total_paid = 0.00,
    balance = 0.00,
    status = 'Pendiente',
    updated_at = NOW()
WHERE application_id IN (247,248);

-- 7. Quitar eventos administrativos generados exclusivamente por las pruebas financieras.
DELETE FROM admin_control_events
WHERE id IN (58,60,61,63,64,65)
  AND module IN ('balance_semanal','control_ingresos');

-- 8. Reiniciar los cierres ABIERTOS de las semanas usadas para prueba.
-- Se volverán a crear automáticamente al entrar al Balance Semanal.
DELETE FROM financial_weekly_closures
WHERE status = 'ABIERTO'
  AND closed_at IS NULL
  AND week_start IN ('2026-10-05','2026-10-12');

COMMIT;

-- Verificación final: estos resultados deben estar en cero para Santi.
SELECT COUNT(*) AS ventas_santi_restantes
FROM income_sales
WHERE application_id = 248;

SELECT COUNT(*) AS pagos_santi_restantes
FROM income_payments
WHERE application_id = 248;

SELECT total_costs,total_paid,balance,status
FROM financial_status
WHERE application_id = 248;

SELECT id,name,opening_balance,status
FROM financial_envelopes
WHERE id = 2;
