<?php
/**
 * Settings Controller
 * Manages system settings (RF75, RF77)
 */

class SettingsController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['admin']);
    }
    
    public function index() {
        $tab = $_GET['tab'] ?? 'general';
        
        // Get all settings grouped by setting_group
        $stmt = $this->db->query("
            SELECT * FROM system_settings 
            ORDER BY setting_group, setting_key
        ");
        $allSettings = $stmt->fetchAll();
        
        // Group settings
        $settings = [];
        foreach ($allSettings as $setting) {
            $settings[$setting['setting_group']][] = $setting;
        }
        
        $this->view('settings/index', [
            'settings' => $settings,
            'tab' => $tab
        ]);
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/public/index.php?page=settings');
        }
        
        try {
            foreach ($_POST['settings'] as $key => $value) {
                $stmt = $this->db->prepare("
                    UPDATE system_settings 
                    SET setting_value = ?, updated_by = ?
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $_SESSION['user_id'], $key]);
            }
            
            $this->logAudit('update_settings', 'system_settings', null, null, $_POST['settings']);
            
            $_SESSION['success'] = 'Configuración guardada exitosamente';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error al guardar configuración: ' . $e->getMessage();
        }
        
        $this->redirect(BASE_URL . '/public/index.php?page=settings&tab=' . ($_POST['tab'] ?? 'general'));
    }
}
