<?php
/**
 * Main Entry Point - CRM Visas
 * Public index file
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/url.php';

// Start session
session_start();

// Simple routing based on page parameter
$page = $_GET['page'] ?? 'login';

// Check if user is logged in for protected pages
$publicPages = ['login', 'register', 'forgot-password', 'questionnaire'];
$isPublicPage = in_array($page, $publicPages);

if (!$isPublicPage && !isset($_SESSION['user_id'])) {
    $page = 'login';
}

// Include base controller
require_once __DIR__ . '/../app/controllers/BaseController.php';

// Route to appropriate controller
switch ($page) {
    case 'login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;
        
    case 'logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case 'dashboard':
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
        
    case 'clients':
        require_once __DIR__ . '/../app/controllers/ClientController.php';
        $controller = new ClientController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $controller->create();
                    break;
                case 'edit':
                    $controller->edit($_GET['id'] ?? null);
                    break;
                case 'view':
                    $controller->detail($_GET['id'] ?? null);
                    break;
                case 'delete':
                    $controller->delete($_GET['id'] ?? null);
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'services':
        require_once __DIR__ . '/../app/controllers/ServiceController.php';
        $controller = new ServiceController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $controller->create();
                    break;
                case 'edit':
                    $controller->edit($_GET['id'] ?? null);
                    break;
                case 'view':
                    $controller->detail($_GET['id'] ?? null);
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'requests':
        require_once __DIR__ . '/../app/controllers/RequestController.php';
        $controller = new RequestController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $controller->create();
                    break;
                case 'edit':
                    $controller->edit($_GET['id'] ?? null);
                    break;
                case 'view':
                    $controller->detail($_GET['id'] ?? null);
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'payments':
        require_once __DIR__ . '/../app/controllers/PaymentController.php';
        $controller = new PaymentController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $controller->create($_GET['request_id'] ?? null);
                    break;
                case 'view':
                    $controller->detail($_GET['id'] ?? null);
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'financial':
        require_once __DIR__ . '/../app/controllers/FinancialController.php';
        $controller = new FinancialController();
        $controller->index();
        break;
        
    case 'reports':
        require_once __DIR__ . '/../app/controllers/ReportController.php';
        $controller = new ReportController();
        $controller->index();
        break;
        
    case 'settings':
        require_once __DIR__ . '/../app/controllers/SettingsController.php';
        $controller = new SettingsController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'save':
                    $controller->save();
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'users':
        require_once __DIR__ . '/../app/controllers/UserController.php';
        $controller = new UserController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $controller->create();
                    break;
                case 'edit':
                    $controller->edit($_GET['id'] ?? null);
                    break;
                default:
                    $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'questionnaire':
        // Public questionnaire link
        require_once __DIR__ . '/../app/controllers/QuestionnaireController.php';
        $controller = new QuestionnaireController();
        $token = $_GET['token'] ?? null;
        if ($token) {
            $controller->show($token);
        } else {
            echo "Token inválido";
        }
        break;
        
    case 'notifications':
        require_once __DIR__ . '/../app/controllers/NotificationController.php';
        $controller = new NotificationController();
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'get':
                    $controller->getNotifications();
                    break;
                case 'count':
                    $controller->getUnreadCount();
                    break;
                case 'markRead':
                    $controller->markAsRead();
                    break;
                case 'markAllRead':
                    $controller->markAllAsRead();
                    break;
                default:
                    $controller->getNotifications();
            }
        } else {
            $controller->getNotifications();
        }
        break;
        
    default:
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/public/index.php?page=dashboard');
        } else {
            header('Location: ' . BASE_URL . '/public/index.php?page=login');
        }
        exit;
}
