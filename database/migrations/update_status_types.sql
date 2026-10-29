-- Migration to update application status types
-- Date: 2026-02-10
-- Description: Add new status "Recepción de información y pago" and update "Finalizado" to "Finalizado (Trámite Entregado)"

-- Update the applications table to include new status types
ALTER TABLE `applications` 
MODIFY COLUMN `status` ENUM(
    'Creado',
    'Recepción de información y pago',
    'En revisión',
    'Información incompleta',
    'Documentación validada',
    'En proceso',
    'Aprobado',
    'Rechazado',
    'Finalizado (Trámite Entregado)'
) DEFAULT 'Creado';

-- Update existing records with old "Finalizado" status to new label
UPDATE `applications` 
SET `status` = 'Finalizado (Trámite Entregado)' 
WHERE `status` = 'Finalizado';
