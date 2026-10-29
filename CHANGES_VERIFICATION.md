# Verification Guide for Status and Download Restrictions Changes

## Changes Summary

### 1. Document Download Restrictions
**Requirement**: Only "Administrador" and "Gerente" roles can download documents. Advisors can only upload documents.

**Implementation**:
- **Backend (Already existed)**: `app/controllers/ApplicationController.php` line 529
  - The `downloadFormFile()` method already checks if user role is Admin or Gerente
  - Redirects with error if advisor tries to download
  
- **Frontend - Form Files**: `app/views/applications/show.php` lines 98-103
  - Download link for form file fields only shows for Admin and Gerente
  - Uses `<?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>`

- **Frontend - Documents Section (NEW FIX)**: `app/views/applications/show.php` lines 141-145
  - Added role check to download button in documents list
  - Now only Admin and Gerente can see download links for uploaded documents

### 2. Application Status Changes
**Requirement**: Replace old statuses with 10 new statuses

**Old Statuses**:
1. Creado
2. Recepción de información y pago
3. En revisión
4. Información incompleta
5. Documentación validada
6. En proceso
7. Aprobado
8. Rechazado
9. Finalizado (Trámite Entregado)

**New Statuses**:
1. Formulario recibido
2. Pago verificado
3. En elaboración de hoja de información
4. En revisión
5. Rechazado (requiere corrección)
6. Aprobado
7. Cita solicitada
8. Cita confirmada
9. Proceso en embajada
10. Finalizado

**Status Mapping in Migration**:
- Creado → Formulario recibido
- Recepción de información y pago → Pago verificado
- En revisión → En revisión (unchanged)
- Información incompleta → Rechazado (requiere corrección)
- Documentación validada → En revisión
- En proceso → Proceso en embajada
- Aprobado → Aprobado (unchanged)
- Rechazado → Rechazado (requiere corrección)
- Finalizado (Trámite Entregado) → Finalizado

**Files Changed**:

1. **config/config.php** (lines 47-56)
   - Updated status constants with new names
   - STATUS_FORMULARIO_RECIBIDO, STATUS_PAGO_VERIFICADO, etc.

2. **database/migrations/update_application_statuses.sql** (NEW FILE)
   - Complete migration script to update enum type
   - Updates existing records to new status values
   - Three-step process to avoid data loss

3. **app/views/applications/show.php** (lines 230-242)
   - Updated status dropdown with new statuses
   - Used new constant names

4. **app/views/applications/index.php**
   - Lines 21-33: Updated status filter dropdown
   - Lines 89-94: Updated status color coding logic

5. **app/views/reports/index.php** (lines 38-49)
   - Updated status filter dropdown with new statuses

6. **app/controllers/PublicFormController.php**
   - Lines 210, 263: Changed initial status from STATUS_CREADO to STATUS_FORMULARIO_RECIBIDO

## Migration Instructions

**IMPORTANT**: Before running the application with these changes, run the database migration:

```bash
# Using command line (use the appropriate database name: recursos_visas or crm_visas)
mysql -u recursos_visas -p recursos_visas < database/migrations/update_application_statuses.sql
```

Or through phpMyAdmin/MySQL client:
1. Connect to the database (recursos_visas or crm_visas)
2. Execute the SQL file content
3. Verify the changes with:
   ```sql
   SHOW COLUMNS FROM applications LIKE 'status';
   SELECT DISTINCT status FROM applications;
   ```

## Testing Checklist

### Document Download Restrictions
- [ ] Login as Asesor role
- [ ] Navigate to a solicitud detail page
- [ ] Verify download buttons are NOT visible for:
  - Form file fields (e.g., passport copy)
  - Documents in the "Documentos" section
- [ ] Try to access download URL directly - should redirect with error
- [ ] Login as Admin or Gerente
- [ ] Verify download buttons ARE visible
- [ ] Verify downloads work correctly

### Status Changes
- [ ] Verify database migration completed successfully
- [ ] Check that all existing applications have new status values
- [ ] Navigate to solicitudes list (/solicitudes)
- [ ] Verify status filter shows all 10 new statuses
- [ ] Filter by each status to verify it works
- [ ] Open a solicitud detail page
- [ ] Verify "Cambiar Estatus" dropdown shows all 10 new statuses
- [ ] Change status to each new value and verify it saves
- [ ] Check status history shows correctly
- [ ] Navigate to reports page (/reportes)
- [ ] Verify status filter shows all 10 new statuses
- [ ] Generate report by status

## Compatibility Notes

- The constants STATUS_FINALIZADO, STATUS_APROBADO, and STATUS_RECHAZADO are still used in controller logic for business rules (e.g., advisors cannot access finalized/rejected applications)
- These constants now point to the new status text values
- All existing logic that checks these statuses will continue to work
- New statuses added: CITA_SOLICITADA, CITA_CONFIRMADA, PROCESO_EMBAJADA, etc.

## Security Considerations

1. **Document Access Control**: 
   - Backend validation prevents advisors from downloading files even if they craft direct URLs
   - Frontend hides download buttons to improve UX
   - Audit trail logs all download attempts

2. **Status Changes**:
   - Only Admin and Gerente can change statuses (existing restriction in BaseController)
   - Status history maintains complete audit trail
   - Database enum ensures only valid statuses can be set
