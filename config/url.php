<?php
/**
 * Base URL Configuration and Detection
 * Automatically detects the base URL for the application
 */

// Detect protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Detect host
$host = $_SERVER['HTTP_HOST'];

// Detect base path
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path = str_replace('\\', '/', dirname($script_name));

// Remove index.php from path if present
if (basename($script_name) === 'index.php') {
    $base_path = dirname($base_path);
}

// Clean up the path
if ($base_path === '/' || $base_path === '\\') {
    $base_path = '';
}

// Define BASE_URL constant
define('BASE_URL', $protocol . $host . $base_path);

// Define PATH constants for easier file access
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

return BASE_URL;
