<?php
/**
 * Payment Controller
 * Manages payments (RF24-RF28)
 */

class PaymentController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function index() {
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT p.*, 
                       sr.request_number,
                       c.first_name, c.last_name
                FROM payments p
                LEFT JOIN service_requests sr ON p.request_id = sr.id
                LEFT JOIN clients c ON sr.client_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($status)) {
            $sql .= " AND p.payment_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();
        
        $this->view('payments/index', [
            'payments' => $payments,
            'status' => $status
        ]);
    }
    
    public function detail($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de pago no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        $payment = $this->getPaymentWithDetails($id);
        
        if (!$payment) {
            $_SESSION['error'] = 'Pago no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        $this->view('payments/view', ['payment' => $payment]);
    }
    
    public function create($requestId = null) {
        $request = null;
        
        if ($requestId) {
            $stmt = $this->db->prepare("
                SELECT sr.*, c.first_name, c.last_name, vs.name as service_name, vs.base_price, vs.currency
                FROM service_requests sr
                LEFT JOIN clients c ON sr.client_id = c.id
                LEFT JOIN visa_services vs ON sr.service_id = vs.id
                WHERE sr.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestId = intval($_POST['request_id'] ?? 0);
            
            if (!$requestId) {
                $_SESSION['error'] = 'Solicitud es requerida';
            } else {
                // Generate payment reference
                $paymentReference = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                $data = [
                    'request_id' => $requestId,
                    'payment_reference' => $paymentReference,
                    'amount' => floatval($_POST['amount'] ?? 0),
                    'currency' => $_POST['currency'] ?? 'MXN',
                    'payment_method' => $_POST['payment_method'] ?? 'cash',
                    'payment_status' => $_POST['payment_status'] ?? 'pending',
                    'transaction_id' => $this->sanitize($_POST['transaction_id'] ?? ''),
                    'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : null,
                    'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                    'notes' => $this->sanitize($_POST['notes'] ?? ''),
                    'created_by' => $_SESSION['user_id']
                ];
                
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO payments (
                            request_id, payment_reference, amount, currency, payment_method,
                            payment_status, transaction_id, payment_date, due_date, notes, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute(array_values($data));
                    $paymentId = $this->db->lastInsertId();
                    
                    // If payment is completed, create financial transaction
                    if ($data['payment_status'] === 'completed') {
                        $stmt = $this->db->prepare("
                            INSERT INTO financial_transactions (
                                category_id, transaction_type, amount, currency, description,
                                reference, payment_id, request_id, transaction_date, created_by
                            ) VALUES (1, 'income', ?, ?, 'Pago de servicio', ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $data['amount'],
                            $data['currency'],
                            $paymentReference,
                            $paymentId,
                            $requestId,
                            $data['payment_date'] ?? date('Y-m-d'),
                            $_SESSION['user_id']
                        ]);
                    }
                    
                    $this->logAudit('create_payment', 'payments', $paymentId, null, $data);
                    
                    $_SESSION['success'] = 'Pago registrado exitosamente';
                    $this->redirect(BASE_URL . '/public/index.php?page=payments&action=view&id=' . $paymentId);
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error al registrar pago: ' . $e->getMessage();
                }
            }
        }
        
        // Get all active requests for dropdown
        $stmt = $this->db->query("
            SELECT sr.id, sr.request_number, c.first_name, c.last_name, vs.name as service_name, vs.base_price
            FROM service_requests sr
            LEFT JOIN clients c ON sr.client_id = c.id
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            WHERE sr.status NOT IN ('cancelled', 'completed')
            ORDER BY sr.created_at DESC
        ");
        $requests = $stmt->fetchAll();
        
        $this->view('payments/create', [
            'request' => $request,
            'requests' => $requests
        ]);
    }
    
    private function getPaymentWithDetails($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   sr.request_number,
                   c.first_name, c.last_name, c.email,
                   vs.name as service_name
            FROM payments p
            LEFT JOIN service_requests sr ON p.request_id = sr.id
            LEFT JOIN clients c ON sr.client_id = c.id
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function receipt($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de pago no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        $payment = $this->getPaymentWithDetails($id);
        
        if (!$payment) {
            $_SESSION['error'] = 'Pago no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        // Render receipt view without main layout
        require __DIR__ . '/../views/payments/receipt.php';
    }
    
    public function pdf($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de pago no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        $payment = $this->getPaymentWithDetails($id);
        
        if (!$payment) {
            $_SESSION['error'] = 'Pago no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=payments');
        }
        
        // Redirect to receipt page for browser-based PDF generation
        // User can use browser's Print to PDF functionality
        $this->redirect(BASE_URL . '/public/index.php?page=payments&action=receipt&id=' . $id);
    }
}
