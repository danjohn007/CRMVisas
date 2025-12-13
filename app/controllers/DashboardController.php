<?php
/**
 * Dashboard Controller
 * Main dashboard for the CRM system
 */

class DashboardController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function index() {
        $user = $this->getCurrentUser();
        
        // Get statistics based on role
        $stats = $this->getStatistics();
        
        // Get recent requests
        $recentRequests = $this->getRecentRequests();
        
        // Get pending tasks
        $pendingTasks = $this->getPendingTasks();
        
        $this->view('dashboard/index', [
            'user' => $user,
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'pendingTasks' => $pendingTasks
        ]);
    }
    
    private function getStatistics() {
        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        $stats = [];
        
        // Total clients
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM clients WHERE status = 'active'");
        $stats['total_clients'] = $stmt->fetch()['total'];
        
        // Total requests
        if ($role === 'asesor') {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM service_requests WHERE assigned_to = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM service_requests");
        }
        $stats['total_requests'] = $stmt->fetch()['total'];
        
        // Pending requests
        if ($role === 'asesor') {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM service_requests 
                WHERE assigned_to = ? AND status IN ('pending', 'in_process')
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query("
                SELECT COUNT(*) as total FROM service_requests 
                WHERE status IN ('pending', 'in_process')
            ");
        }
        $stats['pending_requests'] = $stmt->fetch()['total'];
        
        // Pending payments
        $stmt = $this->db->query("
            SELECT COUNT(*) as total FROM payments 
            WHERE payment_status = 'pending'
        ");
        $stats['pending_payments'] = $stmt->fetch()['total'];
        
        // Monthly revenue (current month)
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM payments 
            WHERE payment_status = 'completed' 
            AND MONTH(payment_date) = MONTH(CURRENT_DATE())
            AND YEAR(payment_date) = YEAR(CURRENT_DATE())
        ");
        $stats['monthly_revenue'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    private function getRecentRequests() {
        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        if ($role === 'asesor') {
            $stmt = $this->db->prepare("
                SELECT sr.*, 
                       c.first_name, c.last_name,
                       vs.name as service_name,
                       u.full_name as assigned_name
                FROM service_requests sr
                LEFT JOIN clients c ON sr.client_id = c.id
                LEFT JOIN visa_services vs ON sr.service_id = vs.id
                LEFT JOIN users u ON sr.assigned_to = u.id
                WHERE sr.assigned_to = ?
                ORDER BY sr.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query("
                SELECT sr.*, 
                       c.first_name, c.last_name,
                       vs.name as service_name,
                       u.full_name as assigned_name
                FROM service_requests sr
                LEFT JOIN clients c ON sr.client_id = c.id
                LEFT JOIN visa_services vs ON sr.service_id = vs.id
                LEFT JOIN users u ON sr.assigned_to = u.id
                ORDER BY sr.created_at DESC
                LIMIT 10
            ");
        }
        
        return $stmt->fetchAll();
    }
    
    private function getPendingTasks() {
        $userId = $_SESSION['user_id'];
        
        $stmt = $this->db->prepare("
            SELECT * FROM calendar_events 
            WHERE user_id = ? 
            AND status = 'scheduled'
            AND start_datetime >= NOW()
            ORDER BY start_datetime ASC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
}
