<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class UserController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN]);
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
            $total = $stmt->fetch()['total'];
            
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            $totalPages = ceil($total / $limit);
            
            $this->view('users/index', [
                'users' => $users,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al listar usuarios: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar usuarios';
            $this->view('users/index', ['users' => []]);
        }
    }
    
    public function create() {
        $this->requireRole([ROLE_ADMIN]);
        $this->view('users/create');
    }
    
    public function store() {
        $this->requireRole([ROLE_ADMIN]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }
        
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($username) || empty($email) || empty($password) || empty($fullName) || empty($role)) {
            $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
            $this->redirect('/usuarios/crear');
        }
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            $this->redirect('/usuarios/crear');
        }
        
        // Validar contraseña
        if (strlen($password) < 6) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
            $this->redirect('/usuarios/crear');
        }
        
        try {
            // Verificar si ya existe el username o email
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()['total'] > 0) {
                $_SESSION['error'] = 'El usuario o email ya existe';
                $this->redirect('/usuarios/crear');
            }
            
            // Hash de contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, full_name, role, phone)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $email,
                $hashedPassword,
                $fullName,
                $role,
                $phone
            ]);

            $userId = (int) $this->db->lastInsertId();
            logAdminControlEvent(
                'usuarios',
                'crear',
                "Usuario creado: $fullName",
                [
                    'entity_type' => 'usuario',
                    'entity_id' => $userId,
                    'priority' => 'alta',
                    'metadata' => [
                        'usuario' => $username,
                        'email' => $email,
                        'rol' => $role
                    ]
                ]
            );
            
            $_SESSION['success'] = 'Usuario creado exitosamente';
            $this->redirect('/usuarios');
            
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear usuario';
            $this->redirect('/usuarios/crear');
        }
    }
    
    public function edit($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $_SESSION['error'] = 'Usuario no encontrado';
                $this->redirect('/usuarios');
            }
            
            $this->view('users/edit', ['user' => $user]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar usuario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar usuario';
            $this->redirect('/usuarios');
        }
    }
    
    public function update($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }
        
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username) || empty($email) || empty($fullName) || empty($role)) {
            $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
            $this->redirect('/usuarios/editar/' . $id);
        }
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no es válido';
            $this->redirect('/usuarios/editar/' . $id);
        }
        
        try {
            // Verificar si el username o email ya existe en otro usuario
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $id]);
            if ($stmt->fetch()['total'] > 0) {
                $_SESSION['error'] = 'El usuario o email ya existe';
                $this->redirect('/usuarios/editar/' . $id);
            }
            
            // Actualizar usuario
            if (!empty($password)) {
                // Si hay nueva contraseña
                if (strlen($password) < 6) {
                    $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
                    $this->redirect('/usuarios/editar/' . $id);
                }
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, password = ?, full_name = ?, role = ?, phone = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $username,
                    $email,
                    $hashedPassword,
                    $fullName,
                    $role,
                    $phone,
                    $isActive,
                    $id
                ]);
            } else {
                // Sin cambio de contraseña
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, full_name = ?, role = ?, phone = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $username,
                    $email,
                    $fullName,
                    $role,
                    $phone,
                    $isActive,
                    $id
                ]);
            }
            
            logAdminControlEvent(
                'usuarios',
                $isActive ? 'actualizar' : 'desactivar',
                ($isActive ? 'Usuario actualizado: ' : 'Usuario desactivado: ') . $fullName,
                [
                    'entity_type' => 'usuario',
                    'entity_id' => $id,
                    'priority' => $isActive ? 'normal' : 'alta',
                    'metadata' => [
                        'usuario' => $username,
                        'email' => $email,
                        'rol' => $role,
                        'activo' => (bool)$isActive,
                        'password_actualizado' => !empty($password)
                    ]
                ]
            );

            $_SESSION['success'] = 'Usuario actualizado exitosamente';
            $this->redirect('/usuarios');
            
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar usuario';
            $this->redirect('/usuarios/editar/' . $id);
        }
    }
    
    public function delete($id) {
        $this->requireRole([ROLE_ADMIN]);
        
        // No permitir eliminar el propio usuario
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'No puede eliminar su propio usuario';
            $this->redirect('/usuarios');
        }
        
        try {
            // Verificar si el usuario tiene solicitudes
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM applications WHERE created_by = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                $_SESSION['error'] = 'No se puede eliminar el usuario porque tiene solicitudes asociadas';
                $this->redirect('/usuarios');
            }
            
            $stmtUser = $this->db->prepare("SELECT username, full_name, email, role FROM users WHERE id = ?");
            $stmtUser->execute([$id]);
            $userToDelete = $stmtUser->fetch();

            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            logAdminControlEvent(
                'usuarios',
                'eliminar',
                'Usuario eliminado: ' . ($userToDelete['full_name'] ?? ('ID ' . $id)),
                [
                    'entity_type' => 'usuario',
                    'entity_id' => $id,
                    'priority' => 'critica',
                    'metadata' => [
                        'usuario' => $userToDelete['username'] ?? null,
                        'email' => $userToDelete['email'] ?? null,
                        'rol' => $userToDelete['role'] ?? null
                    ]
                ]
            );
            
            $_SESSION['success'] = 'Usuario eliminado exitosamente';
            $this->redirect('/usuarios');
            
        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar usuario';
            $this->redirect('/usuarios');
        }
    }
}
