<?php
/**
 * Report Controller
 * Manages reports and analytics (RF47-RF49)
 */

class ReportController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin', 'supervisor']);
    }
    
    public function index() {
        $reportType = $_GET['type'] ?? 'dashboard';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        $data = [];
        
        switch ($reportType) {
            case 'requests':
                $data = $this->getRequestsReport($startDate, $endDate);
                break;
            case 'financial':
                $data = $this->getFinancialReport($startDate, $endDate);
                break;
            case 'productivity':
                $data = $this->getProductivityReport($startDate, $endDate);
                break;
            default:
                $data = $this->getDashboardStats($startDate, $endDate);
        }
        
        $this->view('reports/index', [
            'reportType' => $reportType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'data' => $data
        ]);
    }
    
    private function getDashboardStats($startDate, $endDate) {
        $stats = [];
        
        // Requests by status
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count
            FROM service_requests
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['requestsByStatus'] = $stmt->fetchAll();
        
        // Revenue trend
        $stmt = $this->db->prepare("
            SELECT DATE(transaction_date) as date, SUM(amount) as total
            FROM financial_transactions
            WHERE transaction_type = 'income' AND transaction_date BETWEEN ? AND ?
            GROUP BY DATE(transaction_date)
            ORDER BY date
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['revenueTrend'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    private function getRequestsReport($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT sr.*, c.first_name, c.last_name, vs.name as service_name, u.full_name as assigned_name
            FROM service_requests sr
            LEFT JOIN clients c ON sr.client_id = c.id
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            LEFT JOIN users u ON sr.assigned_to = u.id
            WHERE sr.created_at BETWEEN ? AND ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    private function getFinancialReport($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT ft.*, fc.name as category_name
            FROM financial_transactions ft
            LEFT JOIN financial_categories fc ON ft.category_id = fc.id
            WHERE ft.transaction_date BETWEEN ? AND ?
            ORDER BY ft.transaction_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    private function getProductivityReport($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT u.full_name, u.role,
                   COUNT(sr.id) as total_requests,
                   SUM(CASE WHEN sr.status = 'completed' THEN 1 ELSE 0 END) as completed_requests
            FROM users u
            LEFT JOIN service_requests sr ON u.id = sr.assigned_to 
                AND sr.created_at BETWEEN ? AND ?
            WHERE u.role IN ('asesor', 'supervisor')
            GROUP BY u.id, u.full_name, u.role
            ORDER BY total_requests DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
}
