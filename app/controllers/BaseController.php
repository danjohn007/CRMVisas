<?php
/**
 * Base Controller
 * All controllers extend this class
 */

class BaseController {
    protected $db;
    
    public function __construct() {
        session_start();
        require_once APP_PATH . '/models/Database.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View not found: $viewFile");
        }
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->redirect(BASE_URL . '/public/index.php?page=login');
        }
    }
    
    protected function requireRole($allowedRoles) {
        $this->requireAuth();
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect(BASE_URL . '/public/index.php?page=dashboard');
        }
    }
    
    protected function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
    
    protected function logAudit($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    protected function validateRequired($fields, $data) {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "El campo es requerido";
            }
        }
        return $errors;
    }
}
