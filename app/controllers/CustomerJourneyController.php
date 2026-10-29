<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CustomerJourneyController extends BaseController {
    
    /**
     * Show customer journey for a specific application
     */
    public function show($applicationId) {
        $this->requireLogin();
        
        try {
            // Get application details
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name, f.name as form_name
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
                return;
            }
            
            // Check permissions for Asesor role
            $role = $this->getUserRole();
            if ($role === ROLE_ASESOR) {
                if ($application['status'] === STATUS_FINALIZADO || 
                    $application['status'] === STATUS_RECHAZADO) {
                    $_SESSION['error'] = 'No tienes permiso para ver esta solicitud';
                    $this->redirect('/solicitudes');
                    return;
                }
            }
            
            // Get journey touchpoints
            $stmt = $this->db->prepare("
                SELECT cj.*, u.full_name as user_name
                FROM customer_journey cj
                LEFT JOIN users u ON cj.user_id = u.id
                WHERE cj.application_id = ?
                ORDER BY cj.created_at ASC
            ");
            $stmt->execute([$applicationId]);
            $touchpoints = $stmt->fetchAll();
            
            // Get status history for timeline
            $stmt = $this->db->prepare("
                SELECT sh.*, u.full_name as changed_by_name
                FROM status_history sh
                LEFT JOIN users u ON sh.changed_by = u.id
                WHERE sh.application_id = ?
                ORDER BY sh.created_at ASC
            ");
            $stmt->execute([$applicationId]);
            $statusHistory = $stmt->fetchAll();
            
            // Get financial summary
            $stmt = $this->db->prepare("
                SELECT * FROM financial_status WHERE application_id = ?
            ");
            $stmt->execute([$applicationId]);
            $financialStatus = $stmt->fetch();
            
            $this->view('customer-journey/show', [
                'application' => $application,
                'touchpoints' => $touchpoints,
                'statusHistory' => $statusHistory,
                'financialStatus' => $financialStatus
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar customer journey: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el seguimiento';
            $this->redirect('/solicitudes');
        }
    }
    
    /**
     * Add a new touchpoint to customer journey
     */
    public function addTouchpoint($applicationId) {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/customer-journey/' . $applicationId);
            return;
        }
        
        $touchpointType = trim($_POST['touchpoint_type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contactMethod = trim($_POST['contact_method'] ?? '');
        
        if (empty($touchpointType) || empty($title)) {
            $_SESSION['error'] = 'Tipo y título son requeridos';
            $this->redirect('/customer-journey/' . $applicationId);
            return;
        }
        
        try {
            // Verify application exists and user has permission
            $stmt = $this->db->prepare("SELECT created_by, status FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch();
            
            if (!$app) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
                return;
            }
            
            $role = $this->getUserRole();
            if ($role === ROLE_ASESOR && ($app['status'] === STATUS_FINALIZADO || $app['status'] === STATUS_RECHAZADO)) {
                $_SESSION['error'] = 'No tienes permiso para modificar esta solicitud';
                $this->redirect('/solicitudes');
                return;
            }
            
            // Add touchpoint
            $success = logCustomerJourney(
                $applicationId,
                $touchpointType,
                $title,
                $description,
                !empty($contactMethod) ? $contactMethod : null
            );
            
            if ($success) {
                $_SESSION['success'] = 'Punto de contacto agregado exitosamente';
                
                // Log audit
                logAudit('create', 'customer_journey', 
                    "Punto de contacto agregado a solicitud #$applicationId: $title");
            } else {
                $_SESSION['error'] = 'Error al agregar punto de contacto';
            }
            
        } catch (PDOException $e) {
            error_log("Error al agregar touchpoint: " . $e->getMessage());
            $_SESSION['error'] = 'Error al agregar punto de contacto';
        }
        
        $this->redirect('/customer-journey/' . $applicationId);
    }
}
