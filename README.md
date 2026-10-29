# CRM de Solicitudes de Visas y Pasaportes

Sistema completo de gestión de trámites de Visas y Pasaportes con constructor de formularios dinámicos, control de roles y módulo financiero.

## 🚀 Características Principales

### ✨ Constructor de Formularios Dinámicos
- Interfaz visual tipo JotForm
- Múltiples tipos de campos (texto, fecha, archivo, select, etc.)
- Lógica condicional entre campos
- Versionado automático de formularios
- Publicar/Despublicar formularios

### 👥 Sistema de Roles y Permisos
- **Administrador**: Control total del sistema
- **Gerente**: Gestión operativa y financiera
- **Asesor**: Captura de nuevas solicitudes (sin acceso a finalizadas)

### 📋 Gestión de Solicitudes
- Folios únicos automáticos (VISA-YYYY-NNNNNN)
- Seguimiento completo por estatus
- Historial detallado de cambios
- Sistema de documentos con versionado
- **Regla crítica**: Asesores no ven solicitudes finalizadas

### 💰 Módulo Financiero
- Registro de costos por solicitud
- Control de pagos múltiples
- Estados financieros (Pendiente, Parcial, Pagado)
- Acceso exclusivo para Admin y Gerente

### 📊 Reportes y Dashboard
- Estadísticas en tiempo real
- Gráficas interactivas
- Exportación a Excel, CSV, PDF
- Dashboard personalizado por rol

### ⚙️ Configuración Global
- Personalización de sitio (nombre, logo)
- Configuración de correo electrónico
- Gestión de contactos y horarios
- Temas de color personalizables
- Integración con PayPal
- API para generación de QR

### 🔌 Integraciones
- Dispositivos HikVision
- Shelly Cloud
- Registro y visualización de logs de errores

## 📋 Requisitos del Sistema

- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Servidor Web**: Apache con mod_rewrite
- **Extensiones PHP requeridas**:
  - PDO
  - pdo_mysql
  - json
  - mbstring
  - openssl

## 🛠️ Instalación

### 1. Clonar o Descargar el Repositorio

```bash
git clone https://github.com/danjohn007/CRMIntranet.git
cd CRMIntranet
```

### 2. Configurar el Servidor Web

#### Apache (usando XAMPP, WAMP, LAMP, etc.)

Copie el proyecto a la carpeta del servidor:
- **XAMPP**: `C:\xampp\htdocs\CRMIntranet`
- **WAMP**: `C:\wamp\www\CRMIntranet`
- **Linux**: `/var/www/html/CRMIntranet`

El sistema está diseñado para funcionar en cualquier directorio gracias a la detección automática de URL base.

### 3. Configurar la Base de Datos

1. Acceda a phpMyAdmin o su gestor de MySQL
2. Ejecute el archivo `database/schema.sql` completo
3. Este archivo creará:
   - Base de datos `crm_visas`
   - Todas las tablas necesarias
   - Datos de ejemplo del estado de Querétaro
   - Usuarios de prueba

### 4. Configurar Credenciales de Base de Datos

Edite el archivo `/config/config.php` (líneas 15-18):

```php
define('DB_HOST', 'localhost');      // Host de MySQL
define('DB_NAME', 'crm_visas');      // Nombre de la base de datos
define('DB_USER', 'root');           // Usuario de MySQL
define('DB_PASS', '');               // Contraseña de MySQL
```

### 5. Configurar Permisos

Asegúrese de que el directorio `public/uploads` tenga permisos de escritura:

```bash
chmod -R 755 public/uploads
```

En Windows, generalmente no es necesario configurar permisos adicionales.

### 6. Probar la Instalación

#### Test Rápido de Conexión
Acceda al archivo de prueba rápida en la raíz:
```
http://localhost/CRMIntranet/test_connection.php
```

Este archivo verificará:
- ✅ Conexión a la base de datos
- ✅ Versión de MySQL
- ✅ Tablas existentes
- ✅ Usuarios registrados
- ✅ Configuración de PHP y PDO

#### Test Completo del Sistema
Para una verificación más completa:
```
http://localhost/CRMIntranet/test-conexion
```

Esta página verificará:
- ✅ URL base configurada correctamente
- ✅ Conexión a la base de datos
- ✅ Existencia de tablas
- ✅ Permisos de escritura
- ✅ Extensiones PHP requeridas

### 7. Acceder al Sistema

```
http://localhost/CRMIntranet/
```

## 👤 Usuarios de Prueba

El sistema incluye los siguientes usuarios de ejemplo:

| Usuario | Contraseña | Rol | Email |
|---------|-----------|-----|-------|
| admin | password123 | Administrador | admin@crmvisas.com |
| gerente01 | password123 | Gerente | gerente@crmvisas.com |
| asesor01 | password123 | Asesor | asesor1@crmvisas.com |
| asesor02 | password123 | Asesor | asesor2@crmvisas.com |

## 📁 Estructura del Proyecto

```
CRMIntranet/
├── app/
│   ├── controllers/      # Controladores MVC
│   ├── models/           # Modelos de datos
│   └── views/            # Vistas HTML/PHP
├── config/
│   ├── config.php        # Configuración general
│   └── database.php      # Conexión a BD
├── database/
│   └── schema.sql        # Estructura de BD con datos de ejemplo
├── public/
│   ├── css/              # Estilos personalizados
│   ├── js/               # JavaScript
│   ├── uploads/          # Archivos subidos
│   └── index.php         # Punto de entrada
├── .htaccess             # Configuración Apache
├── test_connection.php   # Test rápido de conexión DB
└── README.md             # Este archivo
```
│   ├── uploads/          # Archivos subidos
│   └── index.php         # Punto de entrada
├── .htaccess             # Configuración Apache
└── README.md             # Este archivo
```

## 🔒 Seguridad

### Medidas Implementadas

- ✅ Autenticación con `password_hash()` y `password_verify()`
- ✅ Sesiones seguras con cookies HTTPOnly
- ✅ Validación de permisos en cada endpoint
- ✅ Preparación de consultas SQL (PDO Prepared Statements)
- ✅ Sanitización de entradas de usuario
- ✅ Protección contra listado de directorios
- ✅ Logs de errores separados de la aplicación

### Regla Crítica de Seguridad

**Los Asesores NO pueden ver solicitudes con estatus "Finalizado"**

Esta regla está implementada a nivel de:
- Base de datos (queries filtrados)
- Controladores (validación de permisos)
- Vistas (ocultar información)

## 🎨 Tecnologías Utilizadas

- **Backend**: PHP puro (sin frameworks)
- **Base de Datos**: MySQL 5.7
- **Frontend**: 
  - HTML5 + CSS3
  - Tailwind CSS (diseño responsivo)
  - JavaScript vanilla
- **Gráficas**: Chart.js / ApexCharts
- **Iconos**: Font Awesome 6
- **Calendario**: FullCalendar.js
- **Arquitectura**: MVC (Model-View-Controller)

## 📚 Uso del Sistema

### Para Asesores

1. Iniciar sesión con credenciales de Asesor
2. Crear nueva solicitud desde el menú
3. Seleccionar tipo de formulario
4. Completar información del solicitante
5. Subir documentos requeridos
6. Visualizar solo solicitudes activas (no finalizadas)

### Para Gerentes

1. Ver todas las solicitudes (incluidas finalizadas)
2. Cambiar estatus de solicitudes
3. Acceder al módulo financiero
4. Registrar costos y pagos
5. Generar reportes
6. Finalizar solicitudes

### Para Administradores

1. Todas las funciones de Gerente
2. Crear y editar formularios dinámicos
3. Gestionar usuarios del sistema
4. Configurar parámetros globales
5. Ver logs de errores
6. Gestionar dispositivos IoT (HikVision, Shelly)

## 🔧 Configuración Avanzada

### URL Amigables

El sistema detecta automáticamente la URL base. Si necesita instalarlo en un subdirectorio:

```
http://localhost/misubdirectorio/CRMIntranet/
```

No requiere configuración adicional.

### Cambiar Colores del Sistema

1. Login como Administrador
2. Ir a Configuración > Personalización
3. Modificar colores primarios y secundarios

### Integración con PayPal

1. Ir a Configuración > Módulo Financiero
2. Ingresar Client ID y Secret de PayPal
3. Guardar configuración

## 🐛 Resolución de Problemas

### Error de conexión a la base de datos

- Verificar credenciales en `/config/config.php`
- Confirmar que MySQL esté ejecutándose
- Verificar que la base de datos `crm_visas` exista

### URLs no funcionan (404)

- Verificar que `mod_rewrite` esté habilitado en Apache
- Revisar que los archivos `.htaccess` existan
- En httpd.conf, cambiar `AllowOverride None` a `AllowOverride All`

### No se pueden subir archivos

- Verificar permisos del directorio `public/uploads`
- Aumentar `upload_max_filesize` en `php.ini` si es necesario

### Página de login muestra en blanco

- Verificar logs de error de PHP
- Revisar que todas las extensiones PHP estén instaladas
- Ver el archivo `/error.log`

## 📖 Documentación Adicional

### Flujo de Estatus de Solicitudes

1. **Creado** - Solicitud registrada por Asesor
2. **En revisión** - Gerente/Admin revisa documentación
3. **Información incompleta** - Requiere datos adicionales
4. **Documentación validada** - Documentos aprobados
5. **En proceso** - Trámite en curso
6. **Aprobado** - Trámite aprobado
7. **Rechazado** - Trámite rechazado
8. **Finalizado** - Trámite entregado (invisible para Asesores)

### Estados Financieros

- **Pendiente** - Sin pagos registrados
- **Parcial** - Pago parcial realizado
- **Pagado** - Completamente pagado

## 🤝 Contribución

Para contribuir al proyecto:

1. Fork el repositorio
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 📞 Soporte

Para soporte técnico o consultas:

- **Email**: admin@crmvisas.com
- **Teléfono**: 442-123-4567
- **Horario**: Lunes a Viernes 9:00 AM - 6:00 PM

---

Desarrollado con ❤️ siguiendo la filosofía VIBE CODING - Sistema CRM Visas y Pasaportes Querétaro
