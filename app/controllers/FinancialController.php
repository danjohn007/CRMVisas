<?php
/**
 * Financial Controller
 * Manages financial transactions (RF53, RF56, RF58)
 */

class FinancialController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin', 'supervisor']);
    }
    
    public function index() {
        $type = $_GET['type'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        // Get transactions
        $sql = "SELECT ft.*, fc.name as category_name, u.full_name as created_by_name
                FROM financial_transactions ft
                LEFT JOIN financial_categories fc ON ft.category_id = fc.id
                LEFT JOIN users u ON ft.created_by = u.id
                WHERE ft.transaction_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        
        if (!empty($type)) {
            $sql .= " AND ft.transaction_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY ft.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        // Calculate totals
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expense
            FROM financial_transactions
            WHERE transaction_date BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $totals = $stmt->fetch();
        
        // Get categories
        $stmt = $this->db->query("SELECT * FROM financial_categories WHERE status = 'active' ORDER BY name");
        $categories = $stmt->fetchAll();
        
        $this->view('financial/index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'totals' => $totals,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
