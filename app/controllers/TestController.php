<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class TestController extends BaseController {
    
    public function connection() {
        $this->requireRole([ROLE_ADMIN]);

        $results = [];
        
        // Test 1: URL Base
        $results['url_base'] = [
            'status' => 'success',
            'value' => BASE_URL,
            'message' => 'URL Base configurada correctamente'
        ];
        
        // Test 2: Rutas del sistema
        $results['root_path'] = [
            'status' => 'success',
            'value' => ROOT_PATH,
            'message' => 'Ruta raíz configurada correctamente'
        ];
        
        // Test 3: Conexión a Base de Datos
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT VERSION() as version");
            $version = $stmt->fetch();
            
            $results['database'] = [
                'status' => 'success',
                'value' => $version['version'],
                'message' => 'Conexión a base de datos exitosa'
            ];
            
            // Test 4: Verificar tablas
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $results['tables'] = [
                'status' => 'success',
                'value' => count($tables) . ' tablas encontradas',
                'tables' => $tables,
                'message' => 'Estructura de base de datos correcta'
            ];
            
            // Test 5: Verificar usuarios
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch();
            
            $results['users'] = [
                'status' => 'success',
                'value' => $count['count'] . ' usuarios registrados',
                'message' => 'Tabla de usuarios operativa'
            ];
            
        } catch (PDOException $e) {
            $results['database'] = [
                'status' => 'error',
                'value' => $e->getMessage(),
                'message' => 'Error de conexión a base de datos'
            ];
        }
        
        // Test 6: Permisos de escritura
        $uploadDir = ROOT_PATH . '/public/uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $results['uploads_dir'] = [
            'status' => is_writable($uploadDir) ? 'success' : 'warning',
            'value' => $uploadDir,
            'message' => is_writable($uploadDir) ? 'Directorio de uploads con permisos correctos' : 'Directorio sin permisos de escritura'
        ];
        
        // Test 7: Extensiones PHP
        $extensions = ['pdo_mysql', 'json', 'mbstring', 'openssl'];
        $missing = [];
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        $results['php_extensions'] = [
            'status' => empty($missing) ? 'success' : 'error',
            'value' => 'PHP ' . phpversion(),
            'message' => empty($missing) ? 'Todas las extensiones requeridas están instaladas' : 'Extensiones faltantes: ' . implode(', ', $missing)
        ];
        
        $this->view('test/connection', ['results' => $results]);
    }
}
