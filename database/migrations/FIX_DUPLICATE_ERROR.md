# Solución: Error "Duplicate entry for key voucher_code"

## Descripción del Error

Si está viendo este error:

```
Database Query Error: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '' for key 'voucher_code'
```

O el mensaje:
```
No se pudo generar ningún vale. Verifique que no existan duplicados.
```

Este documento explica la causa y cómo resolverlo.

## Causa del Problema

El error ocurre porque:

1. **Índice único creado antes de generar tokens**: La migración original creaba el índice UNIQUE en el campo `public_token` antes de generar tokens únicos para los formularios existentes.

2. **Generación de tokens duplicados**: El script usaba `UNIX_TIMESTAMP()` que devuelve el mismo valor para todas las filas actualizadas en la misma consulta, resultando en tokens duplicados.

3. **Tokens vacíos**: Si múltiples formularios tienen `public_token` NULL o vacío (''), y se intenta crear el índice único, MySQL rechaza la operación.

## Solución Rápida

### Paso 1: Ejecutar el Script de Reparación

Hemos creado un script específico para resolver este problema:

```bash
# Conéctese a MySQL
mysql -u recursos_visas -p

# Ejecute el script de reparación
USE recursos_visas;
source /ruta/completa/a/database/migrations/fix_duplicate_tokens.sql
```

O copie el contenido del archivo `fix_duplicate_tokens.sql` y ejecútelo en phpMyAdmin.

### Paso 2: Verificar la Solución

Después de ejecutar el script, verifique que todos los formularios tienen tokens únicos:

```sql
-- Debe devolver 0
SELECT COUNT(*) FROM forms WHERE public_token IS NULL OR public_token = '';

-- El número de tokens únicos debe igualar el total de formularios
SELECT COUNT(DISTINCT public_token) as tokens_unicos, COUNT(*) as total_formularios
FROM forms;
```

### Paso 3: Intentar Crear un Formulario

Vaya a la interfaz web y pruebe crear un nuevo formulario. Ahora debería funcionar correctamente.

## Solución Manual (Si el Script No Funciona)

Si por alguna razón el script automatizado no funciona, siga estos pasos manualmente:

### 1. Eliminar el índice único problemático

```sql
DROP INDEX IF EXISTS idx_forms_public_token ON forms;
```

### 2. Actualizar todos los tokens vacíos o NULL

```sql
UPDATE `forms` 
SET `public_token` = LOWER(CONCAT(
    MD5(CONCAT(id, name, COALESCE(created_at, NOW()), RAND())),
    MD5(CONCAT(created_by, COALESCE(updated_at, NOW()), id * 1000))
))
WHERE `public_token` IS NULL OR `public_token` = '';
```

### 3. Buscar duplicados

```sql
SELECT public_token, COUNT(*) as cantidad
FROM forms 
GROUP BY public_token 
HAVING COUNT(*) > 1;
```

Si encuentra duplicados, actualice cada uno individualmente:

```sql
-- Reemplace <ID> con el ID del formulario duplicado
UPDATE forms 
SET public_token = MD5(CONCAT(RAND(), RAND(), id, NOW())) 
WHERE id = <ID>;
```

### 4. Recrear el índice único

```sql
CREATE UNIQUE INDEX idx_forms_public_token ON forms(public_token);
```

## Prevención

El código actualizado ahora incluye:

1. **Generación de tokens mejorada**: Usa `RAND()` para garantizar unicidad
2. **Verificación antes de insertar**: El FormController verifica que el token no exista antes de usarlo
3. **Reintentos automáticos**: Si un token es duplicado, genera uno nuevo automáticamente
4. **Mejor manejo de errores**: Mensajes claros sobre qué salió mal

## Verificación Final

Ejecute estas consultas para confirmar que todo está correcto:

```sql
-- 1. Ver que el índice existe
SHOW INDEX FROM forms WHERE Key_name = 'idx_forms_public_token';

-- 2. Ver muestra de tokens (todos deben tener 64 caracteres)
SELECT id, name, public_token, LENGTH(public_token) as longitud
FROM forms
LIMIT 5;

-- 3. Confirmar que no hay valores NULL o vacíos
SELECT COUNT(*) as tokens_problematicos
FROM forms 
WHERE public_token IS NULL OR public_token = '' OR LENGTH(public_token) != 64;
```

La última consulta debe devolver `0`.

## Soporte Adicional

Si después de seguir estos pasos aún experimenta problemas:

1. **Revise el log de errores**:
   ```bash
   tail -100 /ruta/al/proyecto/error.log
   ```

2. **Verifique permisos de la base de datos**:
   ```sql
   SHOW GRANTS FOR 'recursos_visas'@'localhost';
   ```
   El usuario debe tener permisos de CREATE INDEX.

3. **Exporte la información de formularios problemáticos**:
   ```sql
   SELECT id, name, public_token, created_at 
   FROM forms 
   WHERE public_token IS NULL OR public_token = '' OR public_token IN (
       SELECT public_token FROM forms GROUP BY public_token HAVING COUNT(*) > 1
   )
   INTO OUTFILE '/tmp/forms_problematicos.csv';
   ```

## Archivos Relevantes

- **Script de reparación**: `database/migrations/fix_duplicate_tokens.sql`
- **Migración actualizada**: `database/migrations/add_enhancements_features.sql`
- **Controlador actualizado**: `app/controllers/FormController.php`
- **Guía completa**: `database/migrations/MIGRATION_GUIDE.md`

## Historial de Versiones

- **v2.0.1 (2026-02-04)**: Corrección del error de tokens duplicados
- **v2.0.0 (2026-02-04)**: Implementación inicial con error de duplicados

---

**Última actualización**: 4 de Febrero, 2026  
**Estado**: ✅ Resuelto
