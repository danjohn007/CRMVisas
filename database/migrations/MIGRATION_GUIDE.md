# Migración de Mejoras del Sistema - Febrero 2026

Este documento describe cómo aplicar las nuevas mejoras al sistema CRM de Visas y Pasaportes.

## Nuevas Características

1. **Campo de Costo con PayPal**: Los formularios ahora pueden tener un costo asociado con enlace de pago PayPal
2. **Paginación de Formularios**: Los formularios pueden dividirse en secciones para guardar progreso
3. **Formularios Públicos**: Cada formulario puede tener un enlace público para que usuarios externos lo completen
4. **Seguimiento de Progreso**: Las solicitudes muestran el porcentaje de completado
5. **Customer Journey**: Módulo completo para rastrear todos los puntos de contacto con clientes
6. **Auditoría Mejorada**: Sistema completo de registro de eventos en el sistema
7. **Logging Mejorado**: Mejor registro de errores y eventos

## Instrucciones de Migración

### IMPORTANTE: Hacer Backup Primero

Antes de aplicar cualquier migración, haga un backup completo de su base de datos:

```bash
mysqldump -u recursos_visas -p recursos_visas > backup_recursos_visas_$(date +%Y%m%d).sql
```

### Opción 1: Migración Manual vía MySQL

1. Conecte a MySQL:
   ```bash
   mysql -u recursos_visas -p
   ```

2. Ejecute el archivo de migración:
   ```sql
   USE recursos_visas;
   source /home/runner/work/CRMIntranet/CRMIntranet/database/migrations/add_enhancements_features.sql
   ```

3. Verifique que la migración fue exitosa - debe ver el mensaje:
   ```
   Migration completed successfully!
   ```

### Opción 2: Migración vía phpMyAdmin

1. Acceda a phpMyAdmin
2. Seleccione la base de datos `recursos_visas`
3. Vaya a la pestaña "SQL"
4. Abra el archivo `database/migrations/add_enhancements_features.sql` en un editor de texto
5. Copie todo el contenido
6. Péguelo en la ventana SQL de phpMyAdmin
7. Haga clic en "Ejecutar"

## Verificación Post-Migración

Después de ejecutar la migración, verifique que todo funciona correctamente:

### 1. Verificar Nuevas Columnas en Formularios
```sql
SHOW COLUMNS FROM forms WHERE Field IN ('cost', 'paypal_enabled', 'pagination_enabled', 'public_token');
```

### 2. Verificar Nuevas Columnas en Solicitudes
```sql
SHOW COLUMNS FROM applications WHERE Field IN ('current_page', 'progress_percentage', 'is_draft');
```

### 3. Verificar Nuevas Tablas
```sql
SHOW TABLES LIKE 'customer_journey';
SHOW TABLES LIKE 'public_form_submissions';
SELECT COUNT(*) as touchpoints FROM customer_journey;
```

## Pruebas Funcionales

1. **Crear Formulario con Costo**
   - Ir a Formularios > Crear Formulario
   - Agregar un costo (ej. 2500.00) y habilitar PayPal
   - Habilitar paginación
   - Guardar y publicar

2. **Enlace Público de Formulario**
   - En la lista de formularios, hacer clic en el ícono de enlace (🔗)
   - Se copiará el enlace al portapapeles
   - Abrir el enlace en una ventana de incógnito
   - Completar el formulario y verificar auto-guardado

3. **Customer Journey**
   - Abrir una solicitud existente
   - Hacer clic en "Customer Journey"
   - Ver la línea de tiempo de eventos
   - Agregar un nuevo punto de contacto

4. **Auditoría del Sistema**
   - Ir a Auditoría en el menú
   - Verificar que se registran eventos de login/logout
   - Hacer un cambio (crear formulario, cambiar estatus)
   - Verificar que aparece en la auditoría

5. **Progreso de Solicitudes**
   - Ir a Solicitudes
   - Verificar columna "Progreso" con barras de porcentaje

## Solución de Problemas Comunes

### Error: "Duplicate entry '' for key 'voucher_code'" o "Duplicate entry for key 'public_token'"

**Síntoma**: Al ejecutar la migración o crear formularios, aparece el error:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '' for key 'public_token'
```

**Causa**: El índice único en `public_token` se creó antes de generar tokens únicos para formularios existentes, o múltiples formularios tienen el mismo token.

**Solución**:

1. Elimine el índice único si existe:
```sql
DROP INDEX IF EXISTS idx_forms_public_token ON forms;
```

2. Actualice todos los tokens vacíos o NULL:
```sql
UPDATE `forms` 
SET `public_token` = LOWER(CONCAT(
    MD5(CONCAT(id, name, COALESCE(created_at, NOW()), RAND())),
    MD5(CONCAT(created_by, COALESCE(updated_at, NOW()), id * 1000))
))
WHERE `public_token` IS NULL OR `public_token` = '';
```

3. Verifique que no haya duplicados:
```sql
SELECT public_token, COUNT(*) 
FROM forms 
GROUP BY public_token 
HAVING COUNT(*) > 1;
```

4. Si hay duplicados, actualícelos manualmente:
```sql
-- Primero, identificar los IDs con tokens duplicados
CREATE TEMPORARY TABLE temp_duplicate_ids AS
SELECT id FROM forms WHERE public_token IN 
    (SELECT public_token FROM forms GROUP BY public_token HAVING COUNT(*) > 1);

-- Luego, actualizar cada uno con un token único
UPDATE forms 
SET public_token = CONCAT(MD5(RAND()), MD5(RAND()))
WHERE id IN (SELECT id FROM temp_duplicate_ids);

-- Limpiar tabla temporal
DROP TEMPORARY TABLE temp_duplicate_ids;
```

5. Cree el índice único:
```sql
CREATE UNIQUE INDEX idx_forms_public_token ON forms(public_token);
```

### Error al crear formularios después de la migración

Si después de la migración no puede crear formularios, verifique:

1. Que el índice único existe:
```sql
SHOW INDEX FROM forms WHERE Key_name = 'idx_forms_public_token';
```

2. Que no hay tokens vacíos:
```sql
SELECT COUNT(*) FROM forms WHERE public_token IS NULL OR public_token = '';
```

3. Revise el log de errores:
```bash
tail -50 /ruta/al/proyecto/error.log
```

## Rollback (Si Hay Problemas)

Si necesita revertir la migración:

```sql
USE recursos_visas;

-- Eliminar índice único
DROP INDEX IF EXISTS idx_forms_public_token ON forms;

-- Eliminar nuevas tablas
DROP TABLE IF EXISTS public_form_submissions;
DROP TABLE IF EXISTS customer_journey;

-- Revertir cambios en forms
ALTER TABLE forms 
DROP COLUMN IF EXISTS public_enabled,
DROP COLUMN IF EXISTS public_token,
DROP COLUMN IF EXISTS pages_json,
DROP COLUMN IF EXISTS pagination_enabled,
DROP COLUMN IF EXISTS paypal_enabled,
DROP COLUMN IF EXISTS cost;

-- Revertir cambios en applications
ALTER TABLE applications
DROP COLUMN IF EXISTS last_saved_at,
DROP COLUMN IF EXISTS is_draft,
DROP COLUMN IF EXISTS progress_percentage,
DROP COLUMN IF EXISTS current_page;
```

Luego restaure su backup:
```bash
mysql -u recursos_visas -p recursos_visas < backup_recursos_visas_YYYYMMDD.sql
```

## Archivos Modificados

### Nuevos Controladores
- `app/controllers/CustomerJourneyController.php`
- `app/controllers/PublicFormController.php`

### Nuevas Vistas
- `app/views/customer-journey/show.php`
- `app/views/public/form.php`

### Helpers Actualizados
- `config/helpers.php` - Agregadas funciones `logAudit()` y `logCustomerJourney()`

### Controladores Actualizados
- `app/controllers/FormController.php`
- `app/controllers/AuthController.php`
- `app/controllers/ApplicationController.php`
- `app/controllers/Router.php`

### Vistas Actualizadas
- `app/views/forms/create.php`
- `app/views/forms/index.php`
- `app/views/applications/index.php`
- `app/views/applications/show.php`

## Configuración Post-Migración

1. **Configurar PayPal** (opcional)
   - Ir a Configuración
   - Agregar PayPal Client ID y Secret
   - Guardar configuración

2. **Publicar Formularios**
   - Los formularios existentes no se publican automáticamente
   - Revise y publique los formularios que desee hacer públicos

3. **Pruebas de Enlaces Públicos**
   - Comparta un enlace público de prueba
   - Verifique que funciona correctamente antes de compartir con clientes

## Solución de Problemas

### Error: Tabla no encontrada
Si ve errores sobre tablas que no existen, verifique que la migración se ejecutó completamente:
```sql
SHOW TABLES LIKE '%journey%';
SHOW TABLES LIKE '%public_form%';
```

### Error: Columna no existe
Si ve errores sobre columnas que no existen, verifique las columnas nuevas:
```sql
DESCRIBE forms;
DESCRIBE applications;
```

### Logs de Errores
Revise `/error.log` en la raíz del proyecto para más detalles sobre errores.

## Soporte

Para soporte adicional:
1. Revise el archivo `/error.log`
2. Revise "Logs de Errores" en el sistema
3. Revise "Auditoría" para ver eventos del sistema

---

**Fecha de Migración**: 4 de Febrero, 2026  
**Versión**: 2.0 - Mejoras del Sistema
