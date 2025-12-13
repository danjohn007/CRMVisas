<?php
/**
 * Authentication Controller
 * Handles login, logout, and authentication
 */

class AuthController extends BaseController {
    
    public function showLogin() {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            $this->redirect(BASE_URL . '/public/index.php?page=dashboard');
        }
        
        $this->view('auth/login');
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/public/index.php?page=login');
        }
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Usuario y contraseña son requeridos';
            $this->redirect(BASE_URL . '/public/index.php?page=login');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Update last login
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Log audit
                $this->logAudit('login', 'users', $user['id']);
                
                $_SESSION['success'] = 'Bienvenido, ' . $user['full_name'];
                $this->redirect(BASE_URL . '/public/index.php?page=dashboard');
            } else {
                $_SESSION['error'] = 'Credenciales inválidas';
                $this->redirect(BASE_URL . '/public/index.php?page=login');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error en el sistema. Intente más tarde.';
            error_log($e->getMessage());
            $this->redirect(BASE_URL . '/public/index.php?page=login');
        }
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logAudit('logout', 'users', $_SESSION['user_id']);
        }
        
        session_destroy();
        $this->redirect(BASE_URL . '/public/index.php?page=login');
    }
}
