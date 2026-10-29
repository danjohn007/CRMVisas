<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuditController extends BaseController {

    /**
     * Check if audit_trail table exists
     */
    private function checkAuditTableExists() {
        try {
            $result = $this->db->query("SHOW TABLES LIKE 'audit_trail'");
            return $result && $result->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }


    private function auditMetadataColumnExists() {
        try {
            $result = $this->db->query("SHOW COLUMNS FROM audit_trail LIKE 'metadata_json'");
            return $result && $result->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    /**
     * Show migration instructions page
     */
    private function showMigrationInstructions() {
        $title = 'Configuración Requerida - Auditoría';
        ob_start();
        ?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-3xl mr-4"></i>
                    <div>
                        <h3 class="font-bold text-lg">Tabla audit_trail no encontrada</h3>
                        <p>La tabla de auditoría no existe en la base de datos. Debe ejecutar la migración primero.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-database mr-2"></i>Configuración de Auditoría
                </h2>

                <div class="prose max-w-none">
                    <h3 class="text-xl font-semibold mb-3">Opción 1: Migración Automática (Recomendado)</h3>
                    <ol class="list-decimal list-inside space-y-2 mb-6">
                        <li>Haga clic en el botón de abajo para ejecutar la migración automática</li>
                        <li>El sistema creará la tabla y los datos de ejemplo</li>
                        <li>Una vez completado, recargue esta página</li>
                    </ol>

                    <a href="<?= BASE_URL ?>/../database/migrations/migrate_audit_trail.php"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow transition"
                       target="_blank">
                        <i class="fas fa-play-circle mr-2"></i>Ejecutar Migración Automática
                    </a>

                    <hr class="my-8">

                    <h3 class="text-xl font-semibold mb-3">Opción 2: Migración Manual (phpMyAdmin)</h3>
                    <ol class="list-decimal list-inside space-y-2 mb-4">
                        <li>Abra phpMyAdmin</li>
                        <li>Seleccione la base de datos: <code class="bg-gray-100 px-2 py-1 rounded"><?= DB_NAME ?></code></li>
                        <li>Vaya a la pestaña "SQL"</li>
                        <li>Copie y pegue el siguiente SQL:</li>
                    </ol>

                    <div class="bg-gray-50 border border-gray-300 rounded p-4 mb-4 overflow-x-auto">
                        <pre class="text-sm"><code>CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `metadata_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</code></pre>
                    </div>

                    <ol start="5" class="list-decimal list-inside space-y-2 mb-6">
                        <li>Haga clic en "Continuar" para ejecutar</li>
                        <li>Recargue esta página</li>
                    </ol>

                    <hr class="my-8">

                    <h3 class="text-xl font-semibold mb-3">Opción 3: Línea de Comandos</h3>
                    <p class="mb-2">Si tiene acceso SSH al servidor, ejecute:</p>
                    <div class="bg-gray-50 border border-gray-300 rounded p-4 mb-6">
                        <code class="text-sm">cd <?= ROOT_PATH ?><br>
php database/migrations/migrate_audit_trail.php</code>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Nota:</strong> La migración es segura y no afectará sus datos existentes.
                            Solo creará la nueva tabla de auditoría.
                        </p>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                        <p class="text-sm text-yellow-700">
                            <i class="fas fa-shield-alt mr-2"></i>
                            <strong>Seguridad:</strong> Después de ejecutar la migración, considere restringir
                            el acceso al script de migración o eliminarlo.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="<?= BASE_URL ?>/dashboard" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                </a>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/layouts/main.php';
    }

    public function index() {
        $this->requireRole([ROLE_ADMIN]);

        // Check if audit_trail table exists
        if (!$this->checkAuditTableExists()) {
            $this->showMigrationInstructions();
            return;
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;
        $hasAuditMetadata = $this->auditMetadataColumnExists();

        // Validate and sanitize dates
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }

        $userId = $_GET['user_id'] ?? '';
        $action = $_GET['action'] ?? '';
        $module = $_GET['module'] ?? '';

        // Construir consulta con filtros
        $where = ["DATE(created_at) BETWEEN ? AND ?"];
        $params = [$startDate, $endDate];

        if (!empty($userId) && $userId !== 'all') {
            $where[] = "user_id = ?";
            $params[] = $userId;
        }

        if (!empty($action)) {
            $where[] = "action LIKE ?";
            $params[] = "%$action%";
        }

        if (!empty($module)) {
            $where[] = "module = ?";
            $params[] = $module;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM audit_trail $whereClause";
        $stmt = $this->db->prepare($countQuery);
        $stmt->execute($params);
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];

        // Obtener registros de auditoría
        $offset = ($page - 1) * $perPage;
        $metadataSelect = $hasAuditMetadata ? ', metadata_json' : ', NULL AS metadata_json';
        $query = "SELECT id, user_id, user_name, user_email, action, module, description, ip_address, created_at$metadataSelect
                  FROM audit_trail
                  $whereClause
                  ORDER BY created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($query);
        $allParams = array_merge($params, [$perPage, $offset]);
        $stmt->execute($allParams);

        $auditLogs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $auditLogs[] = $row;
        }

        // Obtener usuarios para filtro
        $usersQuery = "SELECT id, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name";
        $usersStmt = $this->db->query($usersQuery);
        $users = [];
        while ($row = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }

        // Obtener módulos únicos para filtro
        $modulesQuery = "SELECT DISTINCT module FROM audit_trail ORDER BY module";
        $modulesStmt = $this->db->query($modulesQuery);
        $modules = [];
        while ($row = $modulesStmt->fetch(PDO::FETCH_ASSOC)) {
            $modules[] = $row['module'];
        }

        // Obtener estadísticas del período
        $statsQuery = "SELECT
                        COUNT(*) as total_records,
                        COUNT(DISTINCT user_id) as active_users,
                        COUNT(DISTINCT DATE(created_at)) as days_with_activity
                       FROM audit_trail
                       $whereClause";
        $stmt = $this->db->prepare($statsQuery);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalPages = ceil($total / $perPage);

        $this->view('audit/index', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'modules' => $modules,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userId' => $userId,
            'action' => $action,
            'module' => $module,
            'stats' => $stats
        ]);
    }
}
