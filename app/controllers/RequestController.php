<?php
/**
 * Request Controller
 * Manages service requests (RF12-RF15)
 */

class RequestController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function index() {
        $status = $_GET['status'] ?? '';
        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT sr.*, 
                       c.first_name, c.last_name, c.email as client_email,
                       vs.name as service_name,
                       u.full_name as assigned_name
                FROM service_requests sr
                LEFT JOIN clients c ON sr.client_id = c.id
                LEFT JOIN visa_services vs ON sr.service_id = vs.id
                LEFT JOIN users u ON sr.assigned_to = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Filter by role
        if ($role === 'asesor') {
            $sql .= " AND sr.assigned_to = ?";
            $params[] = $userId;
        }
        
        // Filter by status
        if (!empty($status)) {
            $sql .= " AND sr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY sr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();
        
        $this->view('requests/index', [
            'requests' => $requests,
            'status' => $status
        ]);
    }
    
    public function detail($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de solicitud no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=requests');
        }
        
        $stmt = $this->db->prepare("
            SELECT sr.*, 
                   c.*, c.id as client_id,
                   vs.name as service_name, vs.base_price, vs.currency,
                   u.full_name as assigned_name
            FROM service_requests sr
            LEFT JOIN clients c ON sr.client_id = c.id
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            LEFT JOIN users u ON sr.assigned_to = u.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $_SESSION['error'] = 'Solicitud no encontrada';
            $this->redirect(BASE_URL . '/public/index.php?page=requests');
        }
        
        // Get status history
        $stmt = $this->db->prepare("
            SELECT rsh.*, u.full_name as changed_by_name
            FROM request_status_history rsh
            LEFT JOIN users u ON rsh.changed_by = u.id
            WHERE rsh.request_id = ?
            ORDER BY rsh.created_at DESC
        ");
        $stmt->execute([$id]);
        $statusHistory = $stmt->fetchAll();
        
        // Get payments
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE request_id = ? ORDER BY created_at DESC");
        $stmt->execute([$id]);
        $payments = $stmt->fetchAll();
        
        // Get documents checklist
        $stmt = $this->db->prepare("
            SELECT rdc.*, dc.document_name, dc.is_required, cd.file_path
            FROM request_document_checklist rdc
            LEFT JOIN document_checklists dc ON rdc.checklist_item_id = dc.id
            LEFT JOIN client_documents cd ON rdc.document_id = cd.id
            WHERE rdc.request_id = ?
            ORDER BY dc.display_order ASC
        ");
        $stmt->execute([$id]);
        $documentChecklist = $stmt->fetchAll();
        
        $this->view('requests/view', [
            'request' => $request,
            'statusHistory' => $statusHistory,
            'payments' => $payments,
            'documentChecklist' => $documentChecklist
        ]);
    }
    
    public function create() {
        // Get clients for dropdown
        $stmt = $this->db->query("SELECT id, first_name, last_name, email FROM clients WHERE status = 'active' ORDER BY first_name");
        $clients = $stmt->fetchAll();
        
        // Get services for dropdown
        $stmt = $this->db->query("SELECT id, name, code, base_price FROM visa_services WHERE status = 'active' ORDER BY name");
        $services = $stmt->fetchAll();
        
        // Get asesores for assignment
        $stmt = $this->db->query("SELECT id, full_name FROM users WHERE role IN ('asesor', 'supervisor', 'admin') AND status = 'active' ORDER BY full_name");
        $asesores = $stmt->fetchAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId = intval($_POST['client_id'] ?? 0);
            $serviceId = intval($_POST['service_id'] ?? 0);
            
            if (!$clientId || !$serviceId) {
                $_SESSION['error'] = 'Cliente y servicio son requeridos';
            } else {
                // Generate unique request number
                $requestNumber = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                $data = [
                    'request_number' => $requestNumber,
                    'client_id' => $clientId,
                    'service_id' => $serviceId,
                    'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
                    'status' => $_POST['status'] ?? 'pending',
                    'priority' => $_POST['priority'] ?? 'medium',
                    'submission_date' => date('Y-m-d H:i:s'),
                    'estimated_completion' => !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null,
                    'notes' => $this->sanitize($_POST['notes'] ?? ''),
                    'internal_notes' => $this->sanitize($_POST['internal_notes'] ?? ''),
                    'created_by' => $_SESSION['user_id']
                ];
                
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO service_requests (
                            request_number, client_id, service_id, assigned_to, status, priority,
                            submission_date, estimated_completion, notes, internal_notes, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute(array_values($data));
                    $requestId = $this->db->lastInsertId();
                    
                    // Create status history entry
                    $stmt = $this->db->prepare("
                        INSERT INTO request_status_history (request_id, new_status, changed_by)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$requestId, $data['status'], $_SESSION['user_id']]);
                    
                    $this->logAudit('create_request', 'service_requests', $requestId, null, $data);
                    
                    $_SESSION['success'] = 'Solicitud creada exitosamente';
                    $this->redirect(BASE_URL . '/public/index.php?page=requests&action=view&id=' . $requestId);
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error al crear solicitud: ' . $e->getMessage();
                }
            }
        }
        
        $this->view('requests/create', [
            'clients' => $clients,
            'services' => $services,
            'asesores' => $asesores
        ]);
    }
    
    public function edit($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de solicitud no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=requests');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM service_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $_SESSION['error'] = 'Solicitud no encontrada';
            $this->redirect(BASE_URL . '/public/index.php?page=requests');
        }
        
        // Get asesores for assignment
        $stmt = $this->db->query("SELECT id, full_name FROM users WHERE role IN ('asesor', 'supervisor', 'admin') AND status = 'active' ORDER BY full_name");
        $asesores = $stmt->fetchAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $oldStatus = $request['status'];
            $newStatus = $_POST['status'] ?? $oldStatus;
            
            $data = [
                'assigned_to' => !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null,
                'status' => $newStatus,
                'priority' => $_POST['priority'] ?? 'medium',
                'estimated_completion' => !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null,
                'notes' => $this->sanitize($_POST['notes'] ?? ''),
                'internal_notes' => $this->sanitize($_POST['internal_notes'] ?? '')
            ];
            
            // If status changed to completed, set completion date
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $data['completion_date'] = date('Y-m-d H:i:s');
            }
            
            try {
                $stmt = $this->db->prepare("
                    UPDATE service_requests SET 
                        assigned_to = ?, status = ?, priority = ?, estimated_completion = ?,
                        notes = ?, internal_notes = ?" . 
                        (isset($data['completion_date']) ? ", completion_date = ?" : "") . "
                    WHERE id = ?
                ");
                
                $params = array_values($data);
                $params[] = $id;
                $stmt->execute($params);
                
                // Add status history if changed
                if ($oldStatus !== $newStatus) {
                    $stmt = $this->db->prepare("
                        INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, notes)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$id, $oldStatus, $newStatus, $_SESSION['user_id'], $_POST['status_notes'] ?? null]);
                }
                
                $this->logAudit('update_request', 'service_requests', $id, $request, $data);
                
                $_SESSION['success'] = 'Solicitud actualizada exitosamente';
                $this->redirect(BASE_URL . '/public/index.php?page=requests&action=view&id=' . $id);
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al actualizar solicitud: ' . $e->getMessage();
            }
        }
        
        $this->view('requests/edit', [
            'request' => $request,
            'asesores' => $asesores
        ]);
    }
}
