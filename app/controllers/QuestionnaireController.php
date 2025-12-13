<?php
/**
 * Questionnaire Controller
 * Handles public questionnaire links (RF19-RF23)
 */

class QuestionnaireController extends BaseController {
    
    public function __construct() {
        // Don't require auth for public questionnaires
        session_start();
        require_once APP_PATH . '/models/Database.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function show($token) {
        // Validate token format
        if (empty($token) || !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
            $this->showError('Token inválido');
            return;
        }
        
        // Validate token
        $stmt = $this->db->prepare("
            SELECT pfl.*, sr.id as request_id, sr.request_number, sr.client_id, sr.service_id,
                   c.first_name, c.last_name, c.email,
                   vs.name as service_name
            FROM public_form_links pfl
            LEFT JOIN service_requests sr ON pfl.request_id = sr.id
            LEFT JOIN clients c ON sr.client_id = c.id
            LEFT JOIN visa_services vs ON sr.service_id = vs.id
            WHERE pfl.unique_token = ? AND pfl.is_active = 1
        ");
        $stmt->execute([$token]);
        $link = $stmt->fetch();
        
        if (!$link) {
            $this->showError('Enlace inválido o expirado');
            return;
        }
        
        // Check if expired
        if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
            $this->showError('Este enlace ha expirado');
            return;
        }
        
        // Check submission count
        if ($link['submission_count'] >= $link['max_submissions']) {
            $this->showError('Este cuestionario ya ha sido completado');
            return;
        }
        
        // Update last accessed
        $stmt = $this->db->prepare("UPDATE public_form_links SET last_accessed = NOW() WHERE id = ?");
        $stmt->execute([$link['id']]);
        
        // Get form fields for this service
        $stmt = $this->db->prepare("
            SELECT ff.* FROM form_fields ff
            LEFT JOIN form_templates ft ON ff.template_id = ft.id
            WHERE ft.service_id = ? AND ft.status = 'active'
            ORDER BY ff.display_order ASC
        ");
        $stmt->execute([$link['service_id']]);
        $fields = $stmt->fetchAll();
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Save responses
                foreach ($_POST as $fieldName => $value) {
                    if (strpos($fieldName, 'field_') === 0) {
                        $fieldId = intval(str_replace('field_', '', $fieldName));
                        $stmt = $this->db->prepare("
                            INSERT INTO form_responses (request_id, field_id, response_value)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE response_value = ?
                        ");
                        $stmt->execute([$link['request_id'], $fieldId, $value, $value]);
                    }
                }
                
                // Update submission count
                $stmt = $this->db->prepare("
                    UPDATE public_form_links 
                    SET submission_count = submission_count + 1
                    WHERE id = ?
                ");
                $stmt->execute([$link['id']]);
                
                $success = true;
            } catch (PDOException $e) {
                $error = "Error al guardar el formulario";
            }
        }
        
        // Get existing responses
        $stmt = $this->db->prepare("
            SELECT field_id, response_value 
            FROM form_responses 
            WHERE request_id = ?
        ");
        $stmt->execute([$link['request_id']]);
        $responses = [];
        foreach ($stmt->fetchAll() as $row) {
            $responses[$row['field_id']] = $row['response_value'];
        }
        
        $this->view('questionnaire/form', [
            'link' => $link,
            'fields' => $fields,
            'responses' => $responses,
            'success' => $success ?? false,
            'error' => $error ?? null
        ]);
    }
    
    private function showError($message) {
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md text-center">
        <div class="text-red-600 text-6xl mb-4">⚠️</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Error</h1>
        <p class="text-gray-600">' . htmlspecialchars($message) . '</p>
    </div>
</body>
</html>';
    }
}
