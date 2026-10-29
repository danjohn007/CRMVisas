<?php
// Configuración automática de URL Base
function getBaseUrl() {
    $configuredUrl = trim((string) getenv('APP_URL'));
    if ($configuredUrl !== '') {
        return rtrim($configuredUrl, '/');
    }

    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $protocol = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if (substr($dir, -7) === '/public') {
        $dir = substr($dir, 0, -7);
    }

    return $protocol . '://' . $host . ($dir === '' || $dir === '/' ? '' : $dir);
}

define('BASE_URL', getBaseUrl());
define('BASE_PATH', (string) (parse_url(BASE_URL, PHP_URL_PATH) ?? ''));
define('ROOT_PATH', dirname(__DIR__));

// Integración segura con VisasBot V2 (bot híbrido).
// Copie config/whatsapp.example.php como config/whatsapp.local.php y coloque ahí la clave compartida.
$whatsappLocalConfig = ROOT_PATH . '/config/whatsapp.local.php';
if (is_file($whatsappLocalConfig)) {
    require_once $whatsappLocalConfig;
}
if (!defined('WHATSAPP_BOT_API_URL')) {
    define('WHATSAPP_BOT_API_URL', getenv('WHATSAPP_BOT_API_URL') ?: 'https://us-central1-tramite-visa-vaneza-boot.cloudfunctions.net/crmWhatsAppVisasBot');
}
if (!defined('WHATSAPP_BOT_API_KEY')) {
    define('WHATSAPP_BOT_API_KEY', getenv('WHATSAPP_BOT_API_KEY') ?: '');
}

// Configuración de Base de Datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tramitev_crmvisas');
define('DB_USER', getenv('DB_USER') ?: 'tramitev_crmvisasadmin');
define('DB_PASS', getenv('DB_PASS') ?: '+svewHaWjf;u');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');


// Configuración de Timezone
date_default_timezone_set('America/Mexico_City');

// Configuración de Sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', parse_url(BASE_URL, PHP_URL_SCHEME) === 'https' ? '1' : '0');
ini_set('session.cookie_path', BASE_PATH !== '' ? rtrim(BASE_PATH, '/') . '/' : '/');
session_start();

// Error Reporting (Solo en desarrollo)
error_reporting(E_ALL);
$isDevelopment = strtolower((string) (getenv('APP_ENV') ?: 'production')) === 'development';
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/error.log');

// Configuraciones Globales del Sistema (se cargarán de BD)
define('SITE_NAME', 'Tramite Visa Americana En Queretaro By Vanesa Torres');
define('ITEMS_PER_PAGE', 20);
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Roles del Sistema
define('ROLE_ADMIN', 'Administrador');
define('ROLE_GERENTE', 'Gerente');
define('ROLE_ASESOR', 'Asesor');

// Estatus de Solicitudes (sistema de colores)
define('STATUS_NUEVO', 'Nuevo');                                    // GRIS - recién creada
define('STATUS_VALIDANDO_RESPUESTAS', 'Validando respuestas');      // NARANJA - revisión de respuestas del cuestionario
define('STATUS_LISTO_SOLICITUD', 'Listo para solicitud'); // ROJO - info+cuestionario+docs base
define('STATUS_EN_ESPERA_PAGO', 'En espera de pago consular'); // AMARILLO - solicitud oficial hecha
define('STATUS_CITA_PROGRAMADA', 'Cita programada');      // AZUL - pago+citas+docs finales
define('STATUS_EN_ESPERA_RESULTADO', 'En espera de resultado'); // MORADO - cliente en embajada
define('STATUS_TRAMITE_CERRADO', 'Trámite cerrado');      // VERDE - visa recibida/cerrado

// Estatus legacy (compatibilidad)
define('STATUS_FORMULARIO_RECIBIDO', 'Formulario recibido');
define('STATUS_PAGO_VERIFICADO', 'Pago verificado');
define('STATUS_EN_ELABORACION_HOJA', 'En elaboración de hoja de información');
define('STATUS_EN_REVISION', 'En revisión');
define('STATUS_RECHAZADO', 'Rechazado (requiere corrección)');
define('STATUS_APROBADO', 'Aprobado');
define('STATUS_CITA_SOLICITADA', 'Cita solicitada');
define('STATUS_CITA_CONFIRMADA', 'Cita confirmada');
define('STATUS_PROCESO_EMBAJADA', 'Proceso en embajada');
define('STATUS_FINALIZADO', 'Finalizado');

// Estados Financieros
define('FINANCIAL_PENDIENTE', 'Pendiente');
define('FINANCIAL_PARCIAL', 'Parcial');
define('FINANCIAL_PAGADO', 'Pagado');

// Load helper functions
require_once ROOT_PATH . '/config/helpers.php';
