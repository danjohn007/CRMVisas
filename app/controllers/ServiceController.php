<?php
/**
 * Service Controller
 * Manages visa services and form templates (RF06-RF11)
 */

class ServiceController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin', 'supervisor']);
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT vs.*, u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM service_requests WHERE service_id = vs.id) as request_count
            FROM visa_services vs
            LEFT JOIN users u ON vs.created_by = u.id
            ORDER BY vs.created_at DESC
        ");
        $services = $stmt->fetchAll();
        
        $this->view('services/index', ['services' => $services]);
    }
    
    public function view($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de servicio no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=services');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM visa_services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $_SESSION['error'] = 'Servicio no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=services');
        }
        
        // Get document checklist
        $stmt = $this->db->prepare("
            SELECT * FROM document_checklists 
            WHERE service_id = ? 
            ORDER BY display_order ASC
        ");
        $stmt->execute([$id]);
        $checklist = $stmt->fetchAll();
        
        // Get form templates
        $stmt = $this->db->prepare("SELECT * FROM form_templates WHERE service_id = ?");
        $stmt->execute([$id]);
        $templates = $stmt->fetchAll();
        
        $this->view('services/view', [
            'service' => $service,
            'checklist' => $checklist,
            'templates' => $templates
        ]);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $this->sanitize($_POST['name']),
                'code' => $this->sanitize($_POST['code']),
                'description' => $this->sanitize($_POST['description'] ?? ''),
                'country' => $this->sanitize($_POST['country']),
                'visa_type' => $this->sanitize($_POST['visa_type']),
                'processing_time' => $this->sanitize($_POST['processing_time'] ?? ''),
                'base_price' => floatval($_POST['base_price'] ?? 0),
                'currency' => $_POST['currency'] ?? 'MXN',
                'required_documents' => $this->sanitize($_POST['required_documents'] ?? ''),
                'status' => $_POST['status'] ?? 'active',
                'created_by' => $_SESSION['user_id']
            ];
            
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO visa_services (
                        name, code, description, country, visa_type, processing_time,
                        base_price, currency, required_documents, status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute(array_values($data));
                $serviceId = $this->db->lastInsertId();
                
                $this->logAudit('create_service', 'visa_services', $serviceId, null, $data);
                
                $_SESSION['success'] = 'Servicio creado exitosamente';
                $this->redirect(BASE_URL . '/public/index.php?page=services&action=view&id=' . $serviceId);
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al crear servicio: ' . $e->getMessage();
            }
        }
        
        $this->view('services/create');
    }
    
    public function edit($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de servicio no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=services');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM visa_services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $_SESSION['error'] = 'Servicio no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=services');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $this->sanitize($_POST['name']),
                'code' => $this->sanitize($_POST['code']),
                'description' => $this->sanitize($_POST['description'] ?? ''),
                'country' => $this->sanitize($_POST['country']),
                'visa_type' => $this->sanitize($_POST['visa_type']),
                'processing_time' => $this->sanitize($_POST['processing_time'] ?? ''),
                'base_price' => floatval($_POST['base_price'] ?? 0),
                'currency' => $_POST['currency'] ?? 'MXN',
                'required_documents' => $this->sanitize($_POST['required_documents'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];
            
            try {
                $stmt = $this->db->prepare("
                    UPDATE visa_services SET 
                        name = ?, code = ?, description = ?, country = ?, visa_type = ?,
                        processing_time = ?, base_price = ?, currency = ?, 
                        required_documents = ?, status = ?
                    WHERE id = ?
                ");
                
                $params = array_values($data);
                $params[] = $id;
                $stmt->execute($params);
                
                $this->logAudit('update_service', 'visa_services', $id, $service, $data);
                
                $_SESSION['success'] = 'Servicio actualizado exitosamente';
                $this->redirect(BASE_URL . '/public/index.php?page=services&action=view&id=' . $id);
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al actualizar servicio: ' . $e->getMessage();
            }
        }
        
        $this->view('services/edit', ['service' => $service]);
    }
}
