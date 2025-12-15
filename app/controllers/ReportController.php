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
    
    public function export() {
        $reportType = $_GET['type'] ?? 'requests';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'csv';
        
        // Get data based on report type
        $data = [];
        $filename = '';
        $headers = [];
        
        switch ($reportType) {
            case 'requests':
                $data = $this->getRequestsReport($startDate, $endDate);
                $filename = 'reporte_solicitudes_' . date('Y-m-d');
                $headers = ['Número', 'Cliente', 'Servicio', 'Estado', 'Prioridad', 'Asignado a', 'Fecha Creación'];
                break;
            case 'financial':
                $data = $this->getFinancialReport($startDate, $endDate);
                $filename = 'reporte_financiero_' . date('Y-m-d');
                $headers = ['Fecha', 'Tipo', 'Categoría', 'Descripción', 'Monto', 'Referencia'];
                break;
            case 'productivity':
                $data = $this->getProductivityReport($startDate, $endDate);
                $filename = 'reporte_productividad_' . date('Y-m-d');
                $headers = ['Asesor', 'Rol', 'Total Solicitudes', 'Completadas', 'Tasa de Éxito (%)'];
                break;
            default:
                $_SESSION['error'] = 'Tipo de reporte no válido';
                $this->redirect(BASE_URL . '/public/index.php?page=reports');
                return;
        }
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $headers, $filename, $reportType);
        } else {
            $_SESSION['error'] = 'Formato de exportación no soportado';
            $this->redirect(BASE_URL . '/public/index.php?page=reports');
        }
    }
    
    private function exportToCSV($data, $headers, $filename, $reportType) {
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 encoding (helps with Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            $csvRow = [];
            
            switch ($reportType) {
                case 'requests':
                    $csvRow = [
                        $row['request_number'],
                        $row['first_name'] . ' ' . $row['last_name'],
                        $row['service_name'],
                        ucfirst($row['status']),
                        ucfirst($row['priority']),
                        $row['assigned_name'] ?? 'Sin asignar',
                        date('d/m/Y H:i', strtotime($row['created_at']))
                    ];
                    break;
                case 'financial':
                    $csvRow = [
                        date('d/m/Y', strtotime($row['transaction_date'])),
                        ucfirst($row['transaction_type']),
                        $row['category_name'] ?? 'Sin categoría',
                        $row['description'],
                        number_format($row['amount'], 2),
                        $row['reference'] ?? ''
                    ];
                    break;
                case 'productivity':
                    $rate = $row['total_requests'] > 0 ? 
                            ($row['completed_requests'] / $row['total_requests'] * 100) : 0;
                    $csvRow = [
                        $row['full_name'],
                        ucfirst($row['role']),
                        $row['total_requests'],
                        $row['completed_requests'],
                        number_format($rate, 2)
                    ];
                    break;
            }
            
            fputcsv($output, $csvRow);
        }
        
        fclose($output);
        exit;
    }
}
