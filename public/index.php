<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/Router.php';

// Inicializar Router
$router = new Router();

// Obtener la URI solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = rtrim(BASE_PATH, '/');
if ($basePath !== '' && ($uri === $basePath || strpos($uri, $basePath . '/') === 0)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');

// Ejecutar la ruta
$router->dispatch($uri);
