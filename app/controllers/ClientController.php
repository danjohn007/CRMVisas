<?php
/**
 * Client Controller
 * Manages client CRUD operations (RF01-RF05)
 */

class ClientController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT c.*, u.full_name as created_by_name 
                FROM clients c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.passport_number LIKE ?)";
            $searchParam = "%$search%";
            $params = array_fill(0, 4, $searchParam);
        }
        
        if (!empty($status)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();
        
        $this->view('clients/index', [
            'clients' => $clients,
            'search' => $search,
            'status' => $status
        ]);
    }
    
    public function view($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de cliente no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=clients');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            $_SESSION['error'] = 'Cliente no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=clients');
        }
        
        // Get documents
        $stmt = $this->db->prepare("SELECT * FROM client_documents WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$id]);
        $documents = $stmt->fetchAll();
        
        // Get interactions
        $stmt = $this->db->prepare("
            SELECT ci.*, u.full_name as user_name 
            FROM client_interactions ci
            LEFT JOIN users u ON ci.user_id = u.id
            WHERE ci.client_id = ?
            ORDER BY ci.interaction_date DESC
            LIMIT 20
        ");
        $stmt->execute([$id]);
        $interactions = $stmt->fetchAll();
        
        // Get requests
        $stmt = $this->db->prepare("
            SELECT sr.*, vs.name as service_name 
            FROM service_requests sr
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            WHERE sr.client_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$id]);
        $requests = $stmt->fetchAll();
        
        $this->view('clients/view', [
            'client' => $client,
            'documents' => $documents,
            'interactions' => $interactions,
            'requests' => $requests
        ]);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validateRequired([
                'first_name', 'last_name', 'email', 'phone'
            ], $_POST);
            
            if (empty($errors)) {
                $data = [
                    'first_name' => $this->sanitize($_POST['first_name']),
                    'last_name' => $this->sanitize($_POST['last_name']),
                    'birth_date' => $_POST['birth_date'] ?: null,
                    'gender' => $_POST['gender'] ?: null,
                    'nationality' => $this->sanitize($_POST['nationality'] ?? ''),
                    'passport_number' => $this->sanitize($_POST['passport_number'] ?? ''),
                    'email' => $this->sanitize($_POST['email']),
                    'phone' => $this->sanitize($_POST['phone']),
                    'mobile' => $this->sanitize($_POST['mobile'] ?? ''),
                    'address' => $this->sanitize($_POST['address'] ?? ''),
                    'city' => $this->sanitize($_POST['city'] ?? ''),
                    'state' => $this->sanitize($_POST['state'] ?? ''),
                    'postal_code' => $this->sanitize($_POST['postal_code'] ?? ''),
                    'country' => $this->sanitize($_POST['country'] ?? 'México'),
                    'emergency_contact_name' => $this->sanitize($_POST['emergency_contact_name'] ?? ''),
                    'emergency_contact_phone' => $this->sanitize($_POST['emergency_contact_phone'] ?? ''),
                    'notes' => $this->sanitize($_POST['notes'] ?? ''),
                    'created_by' => $_SESSION['user_id']
                ];
                
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO clients (
                            first_name, last_name, birth_date, gender, nationality, passport_number,
                            email, phone, mobile, address, city, state, postal_code, country,
                            emergency_contact_name, emergency_contact_phone, notes, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute(array_values($data));
                    $clientId = $this->db->lastInsertId();
                    
                    $this->logAudit('create_client', 'clients', $clientId, null, $data);
                    
                    $_SESSION['success'] = 'Cliente registrado exitosamente';
                    $this->redirect(BASE_URL . '/public/index.php?page=clients&action=view&id=' . $clientId);
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error al registrar cliente: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Por favor complete todos los campos requeridos';
            }
        }
        
        $this->view('clients/create');
    }
    
    public function edit($id) {
        if (!$id) {
            $_SESSION['error'] = 'ID de cliente no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=clients');
        }
        
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            $_SESSION['error'] = 'Cliente no encontrado';
            $this->redirect(BASE_URL . '/public/index.php?page=clients');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'first_name' => $this->sanitize($_POST['first_name']),
                'last_name' => $this->sanitize($_POST['last_name']),
                'birth_date' => $_POST['birth_date'] ?: null,
                'gender' => $_POST['gender'] ?: null,
                'nationality' => $this->sanitize($_POST['nationality'] ?? ''),
                'passport_number' => $this->sanitize($_POST['passport_number'] ?? ''),
                'email' => $this->sanitize($_POST['email']),
                'phone' => $this->sanitize($_POST['phone']),
                'mobile' => $this->sanitize($_POST['mobile'] ?? ''),
                'address' => $this->sanitize($_POST['address'] ?? ''),
                'city' => $this->sanitize($_POST['city'] ?? ''),
                'state' => $this->sanitize($_POST['state'] ?? ''),
                'postal_code' => $this->sanitize($_POST['postal_code'] ?? ''),
                'country' => $this->sanitize($_POST['country'] ?? 'México'),
                'emergency_contact_name' => $this->sanitize($_POST['emergency_contact_name'] ?? ''),
                'emergency_contact_phone' => $this->sanitize($_POST['emergency_contact_phone'] ?? ''),
                'notes' => $this->sanitize($_POST['notes'] ?? ''),
                'status' => $_POST['status']
            ];
            
            try {
                $stmt = $this->db->prepare("
                    UPDATE clients SET 
                        first_name = ?, last_name = ?, birth_date = ?, gender = ?, nationality = ?,
                        passport_number = ?, email = ?, phone = ?, mobile = ?, address = ?,
                        city = ?, state = ?, postal_code = ?, country = ?,
                        emergency_contact_name = ?, emergency_contact_phone = ?, notes = ?, status = ?
                    WHERE id = ?
                ");
                
                $params = array_values($data);
                $params[] = $id;
                $stmt->execute($params);
                
                $this->logAudit('update_client', 'clients', $id, $client, $data);
                
                $_SESSION['success'] = 'Cliente actualizado exitosamente';
                $this->redirect(BASE_URL . '/public/index.php?page=clients&action=view&id=' . $id);
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al actualizar cliente: ' . $e->getMessage();
            }
        }
        
        $this->view('clients/edit', ['client' => $client]);
    }
    
    public function delete($id) {
        $this->requireRole(['admin', 'supervisor']);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de cliente no válido';
            $this->redirect(BASE_URL . '/public/index.php?page=clients');
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->logAudit('delete_client', 'clients', $id);
            
            $_SESSION['success'] = 'Cliente eliminado exitosamente';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error al eliminar cliente: ' . $e->getMessage();
        }
        
        $this->redirect(BASE_URL . '/public/index.php?page=clients');
    }
}
