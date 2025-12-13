<?php
/**
 * Configuration File - Database Connection
 * CRM Visas System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'crm_visas');
define('DB_CHARSET', 'utf8mb4');

// Timezone
date_default_timezone_set('America/Mexico_City');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Application settings
define('APP_NAME', 'CRM Visas');
define('APP_VERSION', '1.0.0');
define('UPLOAD_MAX_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Encryption key for sensitive data
define('ENCRYPTION_KEY', 'change-this-key-in-production-to-random-string');

return [
    'db' => [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'name' => DB_NAME,
        'charset' => DB_CHARSET
    ]
];
