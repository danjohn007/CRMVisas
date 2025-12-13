# CRM Visas - Sistema de Gestión de Trámites de Visas

Sistema completo de gestión interna (CRM) para el manejo de solicitudes, trámites y seguimiento de visas internacionales. Desarrollado con PHP puro, MySQL y Tailwind CSS.

## 🚀 Características Principales

### Módulos Implementados

#### 1. **Gestión de Clientes** (RF01-RF05)
- Registro completo de clientes con información personal y de contacto
- Perfil detallado con historial de trámites
- Sistema de subida y validación de documentos digitales (PDF, imágenes)
- Búsqueda avanzada por múltiples criterios
- Historial completo de interacciones y comunicaciones

#### 2. **Servicios y Formularios** (RF06-RF11)
- Configuración de tipos de servicios/visas con parámetros personalizables
- Editor visual de formularios
- Definición de campos personalizados por tipo de visa
- Configuración de validaciones y reglas por campo
- Plantillas de formularios reutilizables
- Lógica condicional en formularios

#### 3. **Gestión de Solicitudes** (RF12-RF15)
- Creación y seguimiento de solicitudes asociando cliente y servicio
- Dashboard de seguimiento por estado
- Sistema de notificaciones automáticas
- Asignación de solicitudes a agentes específicos
- Historial completo de cambios de estado

#### 4. **Proceso de Trámite** (RF16-RF23)
- Formulario unificado de captura de datos del cliente
- Generación de enlaces únicos de cuestionarios públicos
- Formularios responsive accesibles desde dispositivos móviles
- Guardado automático de progreso
- Validación en tiempo real
- Control de expiración de enlaces

#### 5. **Gestión de Pagos** (RF24-RF28)
- Generación automática de fichas de pago
- Registro de pagos con múltiples métodos (efectivo, tarjeta, transferencia, PayPal, Stripe)
- Confirmación y seguimiento de pagos
- Comprobantes digitales
- Control de pagos pendientes y vencidos

#### 6. **Revisión Documental y Checklist** (RF38-RF42)
- Dashboard para revisión de documentación
- Sistema de checklist por tipo de visa
- Marcado de documentos (aprobados/rechazados/pendientes)
- Comentarios y anotaciones en documentos
- Alertas de documentos faltantes o vencidos

#### 7. **Módulo Financiero** (RF53, RF56, RF58)
- Control de ingresos y egresos
- Sistema de comisiones por agente
- Catálogo de categorías de movimientos
- Reportes financieros

#### 8. **Sistema de Reportes** (RF47-RF49)
- Reportes predefinidos (estadísticas, productividad, financieros)
- Constructor de reportes personalizados
- Exportación a múltiples formatos

#### 9. **Usuarios y Seguridad** (RF59-RF70)
- Sistema de roles multi-nivel (Admin, Supervisor, Asesor, Cliente)
- Autenticación segura con password_hash()
- Control de acceso basado en roles
- Registro de auditoría completo
- Encriptación de datos sensibles

#### 10. **Configuración del Sistema** (RF75, RF77)
- Nombre del sitio y logotipo
- Configuración de correo electrónico
- Teléfonos de contacto y horarios de atención
- Personalización de colores del sistema
- Configuración de PayPal y pasarelas de pago
- API para QR masivos
- Configuraciones globales

## 📋 Requisitos del Sistema

- **Servidor Web:** Apache 2.4+
- **PHP:** 7.4+ (recomendado 8.0+)
- **MySQL:** 5.7+ o MariaDB 10.3+
- **Extensiones PHP requeridas:**
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - session

## 🔧 Instalación

### 1. Clonar o Descargar el Repositorio

```bash
git clone https://github.com/danjohn007/CRMVisas.git
cd CRMVisas
```

### 2. Configurar Apache

El sistema puede instalarse en cualquier directorio de Apache. La URL base se detecta automáticamente.

**Opción A: Directorio raíz**
```
/var/www/html/
```

**Opción B: Subdirectorio**
```
/var/www/html/crmvisas/
```

**Configuración .htaccess (opcional para URLs amigables):**
Crear `.htaccess` en la raíz del proyecto:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [L,QSA]
```

### 3. Crear la Base de Datos

```bash
mysql -u root -p
```

```sql
CREATE DATABASE crm_visas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 4. Importar el Schema SQL

```bash
mysql -u root -p crm_visas < assets/sql/schema.sql
```

Esto creará todas las tablas necesarias y datos de ejemplo para Querétaro, incluyendo:
- 4 usuarios de prueba (admin, supervisor, 2 asesores)
- Servicios de visa predefinidos (USA, Canadá, Schengen)
- Clientes de ejemplo
- Configuraciones del sistema
- Plantillas de email

### 5. Configurar la Conexión a la Base de Datos

Editar `config/config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'crm_visas');
```

### 6. Configurar Permisos

```bash
# Dar permisos de escritura a la carpeta de uploads
chmod -R 755 public/uploads
chown -R www-data:www-data public/uploads

# Si existe carpeta de cache
chmod -R 755 cache
chown -R www-data:www-data cache
```

### 7. Probar la Instalación

Visitar: `http://tu-servidor/test_connection.php`

Este archivo verificará:
- ✅ Detección correcta de URL base
- ✅ Conexión a base de datos
- ✅ Versión de PHP y MySQL
- ✅ Extensiones PHP necesarias
- ✅ Configuración del sistema

### 8. Acceder al Sistema

URL: `http://tu-servidor/public/index.php?page=login`

**Credenciales de Prueba:**

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin | password123 | Administrador |
| supervisor1 | password123 | Supervisor |
| asesor1 | password123 | Asesor |
| asesor2 | password123 | Asesor |

⚠️ **IMPORTANTE - SEGURIDAD:** 
- Estas son credenciales de DEMO únicamente
- **DEBE** cambiar todas las contraseñas antes de usar en producción
- Para cambiar contraseñas, acceda como admin a: Usuarios → Editar Usuario
- Considere eliminar usuarios de prueba y crear nuevos con contraseñas seguras

## 🗂️ Estructura del Proyecto

```
CRMVisas/
├── app/
│   ├── controllers/          # Controladores MVC
│   │   ├── AuthController.php
│   │   ├── BaseController.php
│   │   ├── ClientController.php
│   │   ├── DashboardController.php
│   │   ├── ServiceController.php
│   │   ├── RequestController.php
│   │   ├── PaymentController.php
│   │   ├── FinancialController.php
│   │   ├── ReportController.php
│   │   ├── SettingsController.php
│   │   ├── UserController.php
│   │   └── QuestionnaireController.php
│   ├── models/               # Modelos y lógica de negocio
│   │   ├── Database.php
│   │   └── Router.php
│   └── views/                # Vistas PHP con Tailwind CSS
│       ├── auth/
│       ├── dashboard/
│       ├── clients/
│       ├── services/
│       ├── requests/
│       ├── payments/
│       ├── financial/
│       ├── reports/
│       ├── settings/
│       ├── users/
│       ├── questionnaire/
│       ├── layouts/
│       └── errors/
├── assets/
│   ├── sql/                  # Scripts SQL
│   │   └── schema.sql
│   └── docs/                 # Documentación
├── config/
│   ├── config.php            # Configuración general
│   └── url.php               # Detección automática de URL base
├── public/
│   ├── index.php             # Punto de entrada principal
│   ├── css/                  # Estilos personalizados
│   ├── js/                   # JavaScript
│   ├── images/               # Imágenes
│   └── uploads/              # Archivos subidos
├── test_connection.php       # Test de configuración
├── .gitignore
└── README.md
```

## 🎨 Tecnologías Utilizadas

- **Backend:** PHP puro (sin frameworks)
- **Base de Datos:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Estilos:** Tailwind CSS (CDN)
- **Iconos:** Font Awesome 6
- **Gráficas:** Chart.js
- **Arquitectura:** MVC (Model-View-Controller)
- **Seguridad:** 
  - PDO con prepared statements
  - password_hash() y password_verify()
  - Sanitización de inputs
  - Auditoría completa

## 🔐 Seguridad

El sistema implementa múltiples capas de seguridad:

1. **Autenticación:** Sesiones seguras con cookies HTTPOnly
2. **Passwords:** Hashing con bcrypt (password_hash)
3. **Base de Datos:** PDO con prepared statements (prevención SQL injection)
4. **Entrada de Datos:** Sanitización y validación
5. **Control de Acceso:** Basado en roles (RBAC)
6. **Auditoría:** Log completo de todas las acciones
7. **Sesiones:** Configuradas de forma segura

### ⚠️ Checklist de Seguridad para Producción

Antes de desplegar en producción, **DEBE**:

- [ ] Cambiar todas las contraseñas de usuarios por defecto
- [ ] Generar nueva clave de encriptación en `config/config.php`
- [ ] Deshabilitar error reporting (`error_reporting(0)` y `display_errors = 0`)
- [ ] Configurar HTTPS/SSL (habilitar en config/config.php línea 15)
- [ ] Cambiar credenciales de base de datos
- [ ] Revisar permisos de archivos (755 para directorios, 644 para archivos)
- [ ] Configurar respaldos automáticos de base de datos
- [ ] Revisar configuración de `.htaccess` según su servidor

## 📱 Características Destacadas

### Responsive Design
- Diseño completamente adaptable a móviles, tablets y escritorio
- Optimizado con Tailwind CSS

### URLs Amigables
- Sistema de routing limpio
- URLs descriptivas y SEO-friendly

### Auto-configuración
- Detección automática de URL base
- Adaptable a cualquier directorio de instalación

### Auditoría Completa
- Registro de todas las acciones del sistema
- Trazabilidad completa de cambios

### Multi-idioma Ready
- Estructura preparada para internacionalización

## 🔄 Flujo de Trabajo Típico

1. **Admin/Supervisor** crea servicios de visa con sus requisitos
2. **Asesor** registra nuevo cliente en el sistema
3. **Asesor** crea solicitud de visa asociada al cliente
4. Sistema genera **enlace público** para cuestionario del cliente
5. **Cliente** completa cuestionario desde su dispositivo
6. **Asesor** revisa documentación contra checklist
7. **Asesor** registra pagos del servicio
8. Sistema actualiza estado y notifica cambios
9. **Supervisor/Admin** revisa reportes y estadísticas
10. **Admin** gestiona configuración y usuarios

## 📊 Dashboard y Reportes

El sistema incluye:
- Dashboard con estadísticas en tiempo real
- Tarjetas de resumen (clientes, solicitudes, pagos)
- Solicitudes recientes
- Tareas pendientes
- Gráficas de tendencias
- Reportes exportables

## 🛠️ Personalización

### Cambiar Colores del Sistema
1. Ir a: Admin → Configuración → Tema
2. Modificar colores primarios y secundarios

### Configurar Email
1. Ir a: Admin → Configuración → Email
2. Configurar SMTP o email del sistema

### Agregar Nuevos Servicios
1. Ir a: Servicios → Nuevo Servicio
2. Completar formulario con detalles del servicio
3. Configurar checklist de documentos requeridos

## 📞 Soporte

Para reportar problemas o sugerencias:
- **GitHub Issues:** https://github.com/danjohn007/CRMVisas/issues
- **Email:** contacto@crmvisas.com

## 📄 Licencia

Este proyecto está desarrollado como software privado. Todos los derechos reservados.

## 👥 Créditos

Desarrollado para la gestión profesional de trámites de visas en Querétaro, México.

---

## 🚀 Próximas Características

- [ ] Integración completa con PayPal/Stripe
- [ ] Generación de QR para pagos
- [ ] Calendario integrado con FullCalendar.js
- [ ] Notificaciones push
- [ ] API REST para integraciones
- [ ] App móvil
- [ ] Chat en vivo con clientes
- [ ] Firma electrónica de documentos

---

**Versión:** 1.0.0  
**Última actualización:** Diciembre 2025
