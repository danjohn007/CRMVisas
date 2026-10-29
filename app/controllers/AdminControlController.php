<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AdminControlController extends BaseController {
    private function ensureTableExists() {
        if (!adminControlTableExists($this->db)) {
            $title = 'Centro de Control - Configuracion requerida';
            ob_start();
            ?>
            <div class="max-w-4xl mx-auto">
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-database text-3xl mr-4"></i>
                        <div>
                            <h2 class="text-xl font-bold">Falta crear la tabla del Centro de Control</h2>
                            <p class="text-sm mt-1">Ejecute la migracion <code>database/migrations/create_admin_control_events_table.sql</code> en la base de datos.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-3">SQL requerido</h3>
                    <p class="text-sm text-gray-600 mb-4">Puede copiar el contenido del archivo de migracion y ejecutarlo desde phpMyAdmin o su cliente MySQL.</p>
                    <a href="<?= BASE_URL ?>/dashboard" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al dashboard
                    </a>
                </div>
            </div>
            <?php
            $content = ob_get_clean();
            require ROOT_PATH . '/app/views/layouts/main.php';
            return false;
        }

        return true;
    }

    public function index() {
        $this->requireRole([ROLE_ADMIN]);

        if (!$this->ensureTableExists()) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'module' => trim($_GET['module'] ?? ''),
            'action' => trim($_GET['action'] ?? ''),
            'user_id' => trim($_GET['user_id'] ?? ''),
            'folio' => trim($_GET['folio'] ?? ''),
            'priority' => trim($_GET['priority'] ?? ''),
            'read_status' => trim($_GET['read_status'] ?? ''),
            'start_date' => trim($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'))),
            'end_date' => trim($_GET['end_date'] ?? date('Y-m-d')),
        ];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['start_date'])) {
            $filters['start_date'] = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['end_date'])) {
            $filters['end_date'] = date('Y-m-d');
        }

        $where = ['DATE(e.created_at) BETWEEN ? AND ?'];
        $params = [$filters['start_date'], $filters['end_date']];

        if ($filters['module'] !== '') {
            $where[] = 'e.module = ?';
            $params[] = $filters['module'];
        }
        if ($filters['action'] !== '') {
            $where[] = 'e.action = ?';
            $params[] = $filters['action'];
        }
        if ($filters['user_id'] !== '' && ctype_digit($filters['user_id'])) {
            $where[] = 'e.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if ($filters['folio'] !== '') {
            $where[] = '(e.folio LIKE ? OR e.description LIKE ?)';
            $params[] = '%' . $filters['folio'] . '%';
            $params[] = '%' . $filters['folio'] . '%';
        }
        if (in_array($filters['priority'], ['baja', 'normal', 'alta', 'critica'], true)) {
            $where[] = 'e.priority = ?';
            $params[] = $filters['priority'];
        }
        if ($filters['read_status'] === 'unread') {
            $where[] = 'e.is_read = 0';
        } elseif ($filters['read_status'] === 'read') {
            $where[] = 'e.is_read = 1';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM admin_control_events e $whereClause");
        $stmt->execute($params);
        $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $stmt = $this->db->prepare("
            SELECT e.*, u.full_name AS actor_full_name
            FROM admin_control_events e
            LEFT JOIN users u ON u.id = e.user_id
            $whereClause
            ORDER BY e.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'total' => $total,
            'unread' => (int)$this->db->query("SELECT COUNT(*) FROM admin_control_events WHERE is_read = 0")->fetchColumn(),
            'today' => (int)$this->db->query("SELECT COUNT(*) FROM admin_control_events WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'high' => (int)$this->db->query("SELECT COUNT(*) FROM admin_control_events WHERE is_read = 0 AND priority IN ('alta','critica')")->fetchColumn(),
        ];

        $modules = $this->db->query("SELECT DISTINCT module FROM admin_control_events ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
        $actions = $this->db->query("SELECT DISTINCT action FROM admin_control_events ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
        $users = $this->db->query("
            SELECT DISTINCT u.id, u.full_name
            FROM admin_control_events e
            INNER JOIN users u ON u.id = e.user_id
            ORDER BY u.full_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin-control/index', [
            'events' => $events,
            'filters' => $filters,
            'stats' => $stats,
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function markRead() {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/centro-control');
        }

        $id = (int)($_POST['event_id'] ?? 0);
        if ($id > 0 && adminControlTableExists($this->db)) {
            $stmt = $this->db->prepare("UPDATE admin_control_events SET is_read = 1, read_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
        }

        if (($this->isAjaxRequest())) {
            $this->json(['success' => true, 'unread_count' => getAdminControlUnreadCount()]);
        }

        $this->redirect('/centro-control');
    }

    public function markAllRead() {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/centro-control');
        }

        if (adminControlTableExists($this->db)) {
            $this->db->exec("UPDATE admin_control_events SET is_read = 1, read_at = NOW() WHERE is_read = 0");
        }

        $_SESSION['success'] = 'Eventos marcados como leidos';
        $this->redirect('/centro-control');
    }

    private function isAjaxRequest() {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}
