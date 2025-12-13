<?php
/**
 * User Controller
 * Manages system users
 */

class UserController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin']);
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT * FROM users 
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        $this->view('users/index', ['users' => $users]);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validateRequired([
                'username', 'email', 'password', 'full_name', 'role'
            ], $_POST);
            
            if (empty($errors)) {
                $data = [
                    'username' => $this->sanitize($_POST['username']),
                    'email' => $this->sanitize($_POST['email']),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'full_name' => $this->sanitize($_POST['full_name']),
                    'role' => $_POST['role'],
                    'phone' => $this->sanitize($_POST['phone'] ?? ''),
                    'status' => $_POST['status'] ?? 'active'
                ];
                
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO users (username, email, password, full_name, role, phone, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute(array_values($data));
                    $userId = $this->db->lastInsertId();
                    
                    $this->logAudit('create_user', 'users', $userId, null, $data);
                    
                    $_SESSION['success'] = 'Usuario creado exitosamente';
                    $this->redirect(BASE_URL . '/public/index.php?page=users');
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error al crear usuario: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Por favor complete todos los campos requeridos';
            }
        }
        
        $this->view('users/create');
    }
    
    public function edit($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de usuario no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=users');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $_SESSION['error'] = 'Usuario no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $this->sanitize($_POST['username']),
                'email' => $this->sanitize($_POST['email']),
                'full_name' => $this->sanitize($_POST['full_name']),
                'role' => $_POST['role'],
                'phone' => $this->sanitize($_POST['phone'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Only update password if provided
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            try {
                if (isset($data['password'])) {
                    $stmt = $this->db->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, password = ?, full_name = ?, role = ?, phone = ?, status = ?
                        WHERE id = ?
                    ");
                    $params = array_values($data);
                    $params[] = $id;
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, full_name = ?, role = ?, phone = ?, status = ?
                        WHERE id = ?
                    ");
                    unset($data['password']);
                    $params = array_values($data);
                    $params[] = $id;
                }
                
                $stmt->execute($params);
                
                $this->logAudit('update_user', 'users', $id, $user, $data);
                
                $_SESSION['success'] = 'Usuario actualizado exitosamente';
                $this->redirect(BASE_URL . '/public/index.php?page=users');
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al actualizar usuario: ' . $e->getMessage();
            }
        }
        
        $this->view('users/edit', ['user' => $user]);
    }
}
