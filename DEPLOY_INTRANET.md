# Despliegue en `/intranet/`

Destino: `https://tramitevisaamericanaqueretaro.com/intranet/`

## Publicacion

1. Subir todo el repositorio a `public_html/intranet/`, incluyendo `.htaccess` y `vendor/`.
2. Conservar `app/`, `config/`, `database/`, `public/` y `vendor/` en el mismo nivel.
3. Usar PHP 8.1+ con `pdo_mysql`, `json`, `mbstring`, `openssl` y `fileinfo`.
4. Dar permiso de escritura al proceso PHP en `public/uploads/` y `error.log`.
5. Confirmar `mod_rewrite` y `AllowOverride All` para el directorio.

## Base de datos

La aplicacion usa `tramitev_crmvisas`. `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y `DB_CHARSET` pueden definirse como variables de entorno y tienen prioridad sobre `config/config.php`.

- Instalacion nueva: importar `database/schema.sql` y luego las migraciones necesarias.
- Base existente: respaldar y aplicar solo migraciones pendientes. No reimportar `schema.sql`, pues contiene `DROP TABLE`.
- No importar `idindustrial_unificada.sql`: es de `idindust_idindustrialbackup` (CRM + IoT), no de este sistema.
- Si se uso el esquema nuevo, cambiar inmediatamente la contrasena del usuario de demostracion.

Variables recomendadas: `APP_ENV=production` y `APP_URL=https://tramitevisaamericanaqueretaro.com/intranet`, ademas de las credenciales MySQL del hosting.

## Verificacion

- `/intranet/` y `/intranet/login` muestran el acceso sin `/public` en la URL.
- `/intranet/js/form-builder.js` sirve JavaScript.
- `/intranet/config/config.php` y `/intranet/database/schema.sql` responden 403.
- Como administrador, `/intranet/test-conexion` revisa PHP, MySQL y uploads.

Cron por CLI (ajustar `USUARIO`):

```text
/usr/local/bin/php /home/USUARIO/public_html/intranet/public/cron/appointment_reminders.php
```
