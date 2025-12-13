<?php
/**
 * Router Class
 * Handles URL routing and controller dispatching
 */

class Router {
    private $routes = [];
    private $baseUrl;
    
    public function __construct() {
        $this->baseUrl = BASE_URL;
    }
    
    public function add($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function get($path, $controller, $action) {
        $this->add('GET', $path, $controller, $action);
    }
    
    public function post($path, $controller, $action) {
        $this->add('POST', $path, $controller, $action);
    }
    
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $requestUri = strtok($requestUri, '?');
        
        // Remove base path from URI
        $basePath = parse_url($this->baseUrl, PHP_URL_PATH);
        if ($basePath && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        
        // Remove /public from URI if present
        $requestUri = preg_replace('#^/public#', '', $requestUri);
        
        // Normalize URI
        $requestUri = '/' . trim($requestUri, '/');
        if ($requestUri !== '/') {
            $requestUri = rtrim($requestUri, '/');
        }
        
        // Try to match route
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                $pattern = $this->convertPathToRegex($route['path']);
                if (preg_match($pattern, $requestUri, $matches)) {
                    array_shift($matches); // Remove full match
                    $this->executeController($route['controller'], $route['action'], $matches);
                    return;
                }
            }
        }
        
        // No route found - 404
        $this->show404();
    }
    
    private function convertPathToRegex($path) {
        // Convert :param to regex capture group
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    private function executeController($controllerName, $action, $params = []) {
        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                
                if (method_exists($controller, $action)) {
                    call_user_func_array([$controller, $action], $params);
                } else {
                    die("Action '$action' not found in controller '$controllerName'");
                }
            } else {
                die("Controller class '$controllerName' not found");
            }
        } else {
            die("Controller file not found: $controllerFile");
        }
    }
    
    private function show404() {
        header("HTTP/1.0 404 Not Found");
        require_once APP_PATH . '/views/errors/404.php';
        exit;
    }
    
    public static function url($path = '') {
        return BASE_URL . '/public' . $path;
    }
}
