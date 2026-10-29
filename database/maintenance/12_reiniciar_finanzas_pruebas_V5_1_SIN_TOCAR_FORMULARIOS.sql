-- ============================================================
-- CRM VISAS V5.1
-- REINICIO COMPLETO DEL FLUJO FINANCIERO DE PRUEBAS
--
-- IMPORTANTE:
-- Este script NO elimina ni modifica:
--   forms
--   public_form_submissions
--   applications
--   documents
--   form_links
--   bot_contacts / conversaciones de WhatsApp
--   users
--   income_catalog
--
-- Sí reinicia ventas, pagos, egresos, balances y horas extra para
-- poder repetir el flujo financiero desde cero.
-- Base revisada: tramitev_crmvisas (2).sql - 16/10/2026
-- ============================================================

SET NAMES utf8mb4;

-- Conteos de seguridad: se muestran al final para comprobar que los
-- formularios y sus respuestas no fueron tocados.
SET @forms_before := (SELECT COUNT(*) FROM forms);
SET @submissions_before := (SELECT COUNT(*) FROM public_form_submissions);
SET @applications_before := (SELECT COUNT(*) FROM applications);
SET @documents_before := (SELECT COUNT(*) FROM documents);

START TRANSACTION;

-- 1. Auditoría/cierres financieros dependientes.
DELETE FROM financial_correction_log;
DELETE FROM financial_weekly_envelope_counts;
DELETE FROM financial_weekly_closures;

-- 2. Movimientos de las cuentas y del sistema anterior de sobres.
DELETE FROM finance_account_movements;
DELETE FROM financial_envelope_movements;

-- 3. Pagos del nuevo Control de Ingresos y espejo legacy.
DELETE FROM income_payments;
DELETE FROM payments;

-- 4. Ventas financieras.
DELETE FROM income_sales;

-- 5. Egresos y costos financieros de pruebas.
DELETE FROM financial_expenses;
DELETE FROM financial_costs;

-- 6. Registros de módulos financieros antiguos que podrían aparecer
--    en reportes heredados.
DELETE FROM advisor_income_records;

-- 7. Productividad de prueba y clientes rápidos de prueba.
DELETE FROM advisor_overtime;
DELETE FROM income_quick_clients;

-- 8. Las Hojas de Información SE CONSERVAN.
--    Sólo se elimina su parte financiera para que puedan volver a
--    generar una venta desde cero. No se toca domicilio, correos,
--    observaciones, DHL ni familiares.
UPDATE information_sheets
SET service_catalog_id = NULL,
    service_name = NULL,
    service_quantity = 1,
    service_unit_price = NULL,
    service_total_amount = NULL,
    amount_paid = 0.00,
    payment_cash = 0.00,
    payment_terminal = 0.00,
    payment_transfer = 0.00,
    payment_method = NULL,
    payment_reference = NULL,
    financial_sale_id = NULL,
    cash_envelope_id = NULL,
    updated_at = NOW();

-- 9. Estado financiero de cada Solicitud a cero, sin borrar la Solicitud.
UPDATE financial_status
SET total_costs = 0.00,
    total_paid = 0.00,
    balance = 0.00,
    status = 'Pendiente',
    updated_at = NOW();

-- 10. Las tres cuentas quedan en cero para iniciar la prueba completa.
UPDATE finance_accounts
SET opening_balance = 0.00,
    is_active = 1,
    updated_at = NOW()
WHERE code IN ('EFECTIVO','TERMINAL','TRANSFERENCIA');

-- 11. El sistema anterior de sobres queda neutro.
UPDATE financial_envelopes
SET opening_balance = 0.00,
    status = 'ACTIVO',
    closed_by = NULL,
    closed_at = NULL,
    updated_at = NOW();

COMMIT;

-- ============================================================
-- VERIFICACIÓN
-- ============================================================

SELECT
  @forms_before AS formularios_antes,
  (SELECT COUNT(*) FROM forms) AS formularios_despues,
  @submissions_before AS respuestas_antes,
  (SELECT COUNT(*) FROM public_form_submissions) AS respuestas_despues,
  @applications_before AS solicitudes_antes,
  (SELECT COUNT(*) FROM applications) AS solicitudes_despues,
  @documents_before AS documentos_antes,
  (SELECT COUNT(*) FROM documents) AS documentos_despues;

SELECT
  (SELECT COUNT(*) FROM income_sales) AS ventas,
  (SELECT COUNT(*) FROM income_payments) AS pagos_control_ingresos,
  (SELECT COUNT(*) FROM payments) AS pagos_legacy,
  (SELECT COUNT(*) FROM finance_account_movements) AS movimientos_cuentas,
  (SELECT COUNT(*) FROM financial_expenses) AS egresos,
  (SELECT COUNT(*) FROM advisor_overtime) AS horas_extra,
  (SELECT COUNT(*) FROM income_quick_clients) AS clientes_rapidos;

SELECT code,name,opening_balance
FROM finance_accounts
ORDER BY id;

SELECT 'Reinicio financiero terminado. Formularios y respuestas conservados.' AS resultado;
