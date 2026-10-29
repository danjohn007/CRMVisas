# CRM Visas - Bandeja WhatsApp V2

## Funciones implementadas

### Administrador
- Ve todas las conversaciones, incluso las atendidas únicamente por el bot.
- Ve todos los mensajes y quién los envió: cliente, bot, asesora o administrador.
- Ve el perfil de primer contacto y las intervenciones de las asesoras.
- Puede tomar conversaciones que están esperando atención, responder, devolver al bot y cerrar.
- Cerrar NO elimina historial.

### Asesora
- NO ve conversaciones normales que siguen con el bot.
- Ve conversaciones `WAITING_HUMAN` y las conversaciones que ella misma tomó.
- Puede tomar una conversación cuando el cliente pidió hablar con una asesora.
- Puede responder por WhatsApp y devolver la conversación al bot.
- No existe ruta ni botón para editar o borrar mensajes o conversaciones.

## Instalación

1. Importe `database/migrations/visasbot_v2_hybrid.sql` en `tramitev_crmvisas`.
2. Despliegue la versión V2 del bot y la Function `crmWhatsAppVisasBot`.
3. Copie:
   `config/whatsapp.example.php`
   a:
   `config/whatsapp.local.php`
4. Edite `config/whatsapp.local.php` y coloque exactamente la misma clave utilizada en el Secret de Firebase `WEB_ADMIN_KEY_VISASBOT`.
5. No suba `config/whatsapp.local.php` a Git. Ya está agregado a `.gitignore`.
6. Inicie sesión como Administrador o Asesor y abra `Conversaciones WhatsApp` en el menú lateral.

## Actualización de la bandeja

El historial de la conversación activa consulta mensajes nuevos automáticamente cada 6 segundos. Las acciones (tomar, responder, devolver al bot y cerrar) pasan por la Function segura de Firebase para mantener una sola fuente de verdad.
