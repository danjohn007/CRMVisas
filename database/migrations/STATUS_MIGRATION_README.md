# Status Types Migration Guide

## Overview
This migration updates the application status types to include a new status and modify an existing one.

## Changes

### New Status Types (9 total):
1. Creado
2. **Recepción de información y pago** (NEW)
3. En revisión
4. Información incompleta
5. Documentación validada
6. En proceso
7. Aprobado
8. Rechazado
9. **Finalizado (Trámite Entregado)** (UPDATED from "Finalizado")

## Files Modified

### Database Schema
- `database/schema.sql` - Updated ENUM definition for status column
- `database/migrations/update_status_types.sql` - Migration script for existing databases

### Configuration
- `config/config.php` - Added STATUS_RECEPCION_INFO_PAGO constant and updated STATUS_FINALIZADO

### Views
- `app/views/applications/index.php` - Added new status to filter dropdown
- `app/views/applications/show.php` - Added new status to status change form
- `app/views/reports/index.php` - Added new status to reports filter

## Migration Instructions

### For New Installations
Simply use the updated `database/schema.sql` file when creating the database.

### For Existing Installations
Run the migration script to update the database:

```sql
-- Execute the migration script
source database/migrations/update_status_types.sql;
```

Or manually execute:

```sql
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

UPDATE `applications` 
SET `status` = 'Finalizado (Trámite Entregado)' 
WHERE `status` = 'Finalizado';
```

## Notes

- All controllers use constants from `config/config.php`, so no controller changes were required
- The new status "Recepción de información y pago" can be used immediately after migration
- Existing applications with "Finalizado" status will be automatically updated to "Finalizado (Trámite Entregado)"
- The status history table will continue to work with the new status values

## Verification

After running the migration, verify:
1. The status dropdown in the applications list shows all 9 statuses
2. The status change form in application details shows all 9 statuses
3. Existing applications display their status correctly
4. New applications can be created with any of the 9 statuses
