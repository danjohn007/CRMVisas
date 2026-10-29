-- Migration to update application status values
-- Date: 2026-02-11
-- Description: Update status enum in applications table to new status values
-- Note: This migration works on the currently selected database. 
--       Make sure you're connected to the correct database before running.

-- Status Mapping:
-- 'Creado' -> 'Formulario recibido'
-- 'Recepción de información y pago' -> 'Pago verificado'
-- 'En revisión' -> 'En revisión' (unchanged)
-- 'Información incompleta' -> 'Rechazado (requiere corrección)'
-- 'Documentación validada' -> 'En revisión'
-- 'En proceso' -> 'Proceso en embajada'
-- 'Aprobado' -> 'Aprobado' (unchanged)
-- 'Rechazado' -> 'Rechazado (requiere corrección)'
-- 'Finalizado (Trámite Entregado)' -> 'Finalizado'

-- Note: Due to ENUM limitations, we need to do this in steps
-- Step 1: Add new enum values to allow coexistence with old values

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
    'Finalizado (Trámite Entregado)',
    'Formulario recibido',
    'Pago verificado',
    'En elaboración de hoja de información',
    'Rechazado (requiere corrección)',
    'Cita solicitada',
    'Cita confirmada',
    'Proceso en embajada',
    'Finalizado'
) DEFAULT 'Creado';

-- Step 2: Update records to new status values
-- Note: 'Documentación validada' and original 'En revisión' both map to 'En revisión'
-- This is intentional per requirements - the new status set consolidates these states
-- Historical data is preserved in status_history table for audit purposes
UPDATE `applications` SET `status` = 'Formulario recibido' WHERE `status` = 'Creado';
UPDATE `applications` SET `status` = 'Pago verificado' WHERE `status` = 'Recepción de información y pago';
UPDATE `applications` SET `status` = 'Rechazado (requiere corrección)' WHERE `status` = 'Información incompleta';
UPDATE `applications` SET `status` = 'En revisión' WHERE `status` = 'Documentación validada';
UPDATE `applications` SET `status` = 'Proceso en embajada' WHERE `status` = 'En proceso';
UPDATE `applications` SET `status` = 'Rechazado (requiere corrección)' WHERE `status` = 'Rechazado';
UPDATE `applications` SET `status` = 'Finalizado' WHERE `status` = 'Finalizado (Trámite Entregado)';

-- Step 3: Remove old enum values, keeping only new ones
ALTER TABLE `applications` 
MODIFY COLUMN `status` ENUM(
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
) DEFAULT 'Formulario recibido';
