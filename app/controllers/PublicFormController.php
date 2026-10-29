<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PublicFormController extends BaseController {
    private function normalizeFieldsPayload($decodedFields) {
        if (isset($decodedFields['fields']) && is_array($decodedFields['fields'])) {
            return ['fields' => $decodedFields['fields']];
        }
        
        if (is_array($decodedFields)) {
            return ['fields' => $decodedFields];
        }
        
        return ['fields' => []];
    }
    
    /**
     * Show public form by token (no authentication required)
     *
     * When ?app= parameter is present the form may only be rendered if:
     *   1. The application exists and has NOT been deleted.
     *   2. The application is linked to THIS form.
     *   3. The form has NOT already been completed for that application.
     * Any other access attempt returns a 404 / access-denied view.
     */
    public function show($token) {
        try {
            // Get form by public token
            $stmt = $this->db->prepare("
                SELECT f.*, u.full_name as creator_name, u.email as creator_email
                FROM forms f
                LEFT JOIN users u ON f.created_by = u.id
                WHERE f.public_token = ? AND f.is_published = 1 AND f.public_enabled = 1
            ");
            $stmt->execute([$token]);
            $form = $stmt->fetch();

            if (!$form) {
                http_response_code(404);
                $this->viewPublic('public/form_invalid', []);
                return;
            }

            // Check if linked to a solicitud (app parameter)
            $appId = isset($_GET['app']) ? intval($_GET['app']) : null;

            if ($appId) {
                // When ?app= is provided, the form is only valid for that specific solicitud
                $stmtApp = $this->db->prepare("
                    SELECT id, form_link_id, form_link_status
                    FROM applications WHERE id = ? AND form_link_id = ?
                ");
                $stmtApp->execute([$appId, $form['id']]);
                $application = $stmtApp->fetch();

                // Application must exist and be linked to this exact form
                if (!$application) {
                    http_response_code(404);
                    $this->viewPublic('public/form_invalid', []);
                    return;
                }

                // Form has already been completed — cannot be filled again
                if ($application['form_link_status'] === 'completado') {
                    $this->viewPublic('public/form', [
                        'form' => $form,
                        'fields' => null,
                        'pages' => null,
                        'token' => $token,
                        'alreadyCompleted' => true,
                        'appId' => $appId,
                    ]);
                    return;
                }
            }

            // Parse fields JSON
            $fields = $this->normalizeFieldsPayload(json_decode($form['fields_json'], true));

            // Parse pages if pagination enabled
            $pages = null;
            if ($form['pagination_enabled'] && !empty($form['pages_json'])) {
                $pages = json_decode($form['pages_json'], true);
            }

            $this->viewPublic('public/form', [
                'form' => $form,
                'fields' => $fields,
                'pages' => $pages,
                'token' => $token,
                'alreadyCompleted' => false,
                'appId' => $appId,
            ]);

        } catch (PDOException $e) {
            error_log("Error al cargar formulario público: " . $e->getMessage());
            http_response_code(500);
            echo "Error al cargar el formulario";
        }
    }
    
    /**
     * Submit public form data
     */
    public function submit($token) {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        try {
            // Get form
            $stmt = $this->db->prepare("
                SELECT * FROM forms 
                WHERE public_token = ? AND is_published = 1 AND public_enabled = 1
            ");
            $stmt->execute([$token]);
            $form = $stmt->fetch();
            
            if (!$form) {
                http_response_code(404);
                echo json_encode(['error' => 'Formulario no encontrado']);
                return;
            }

            // If linked to a solicitud, verify it still exists and has NOT been completed
            $linkedAppId = isset($_POST['appId']) ? intval($_POST['appId']) : null;
            if ($linkedAppId) {
                $stmtCheck = $this->db->prepare("
                    SELECT id, form_link_status FROM applications WHERE id = ? AND form_link_id = ?
                ");
                $stmtCheck->execute([$linkedAppId, $form['id']]);
                $linkedAppCheck = $stmtCheck->fetch();

                if (!$linkedAppCheck) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Solicitud no encontrada o formulario no vinculado']);
                    return;
                }

                if ($linkedAppCheck['form_link_status'] === 'completado') {
                    http_response_code(409);
                    echo json_encode(['error' => 'Este formulario ya fue completado previamente']);
                    return;
                }
            }

            // Get submission data
            $submissionData = $_POST['formData'] ?? '{}';
            $currentPage = intval($_POST['currentPage'] ?? 1);
            $isCompleted = isset($_POST['isCompleted']) && $_POST['isCompleted'] === 'true';
            
            // Validate JSON
            $data = json_decode($submissionData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                return;
            }
            
            // Calculate progress
            $fields = $this->normalizeFieldsPayload(json_decode($form['fields_json'], true));
            $totalFields = count($fields['fields'] ?? []);
            $filledFields = 0;
            
            foreach ($fields['fields'] ?? [] as $field) {
                if (isset($data[$field['id']]) && !empty($data[$field['id']])) {
                    $filledFields++;
                }
            }
            
            $progressPercentage = $totalFields > 0 ? ($filledFields / $totalFields) * 100 : 0;
            
            // Get IP and User Agent
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Check if submission exists for this session
            $submissionId = $_POST['submissionId'] ?? null;
            
            if ($submissionId) {
                // Update existing submission
                $stmt = $this->db->prepare("
                    UPDATE public_form_submissions 
                    SET submission_data = ?, progress_percentage = ?, current_page = ?, 
                        is_completed = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND form_id = ?
                ");
                $stmt->execute([
                    $submissionData,
                    $progressPercentage,
                    $currentPage,
                    $isCompleted ? 1 : 0,
                    $submissionId,
                    $form['id']
                ]);
            } else {
                // Create new submission
                $stmt = $this->db->prepare("
                    INSERT INTO public_form_submissions 
                    (form_id, submission_data, progress_percentage, current_page, is_completed, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $form['id'],
                    $submissionData,
                    $progressPercentage,
                    $currentPage,
                    $isCompleted ? 1 : 0,
                    $ipAddress,
                    $userAgent
                ]);
                
                $submissionId = $this->db->lastInsertId();
            }
            
            // If completed, optionally create an application
            if ($isCompleted) {
                // $linkedAppId was already resolved and validated above

                // Process file uploads
                $uploadedFiles = [];
                if (!empty($_FILES)) {
                    $fields = $this->normalizeFieldsPayload(json_decode($form['fields_json'], true));
                    $fileFields = array_filter($fields['fields'] ?? [], function($field) {
                        return $field['type'] === 'file';
                    });
                    
                    foreach ($fileFields as $field) {
                        $fieldId = $field['id'];
                        if (isset($_FILES[$fieldId]) && $_FILES[$fieldId]['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES[$fieldId];
                            $fileName = $file['name'];
                            $fileSize = $file['size'];
                            $fileTmpName = $file['tmp_name'];
                            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            
                            // Validate file
                            if ($fileSize <= MAX_FILE_SIZE && in_array($fileType, ALLOWED_EXTENSIONS)) {
                                // Store file info for later
                                $uploadedFiles[$fieldId] = [
                                    'name' => $fileName,
                                    'tmp_name' => $fileTmpName,
                                    'size' => $fileSize,
                                    'type' => $fileType,
                                    'label' => $field['label']
                                ];
                                
                                // Update data JSON to store only filename
                                $data[$fieldId] = $fileName;
                            }
                        }
                    }
                    
                    // Update submission data with filenames
                    if (!empty($uploadedFiles)) {
                        $submissionData = json_encode($data, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                // If linked to an existing solicitud, update it instead of creating a new application
                if ($linkedAppId) {
                    $applicationId = $linkedAppId;

                    // Save uploaded files as documents
                    if (!empty($uploadedFiles)) {
                        $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $applicationId;
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        foreach ($uploadedFiles as $fieldId => $fileInfo) {
                            $newFileName = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileInfo['name']);
                            $filePath = $uploadDir . '/' . $newFileName;
                            if (move_uploaded_file($fileInfo['tmp_name'], $filePath)) {
                                $relativePath = '/uploads/applications/' . $applicationId . '/' . $newFileName;
                                $this->db->prepare("
                                    INSERT INTO documents (application_id, name, file_path, file_type, file_size, uploaded_by)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ")->execute([
                                    $applicationId,
                                    $fileInfo['label'] . ' - ' . $fileInfo['name'],
                                    $relativePath,
                                    $fileInfo['type'],
                                    $fileInfo['size'],
                                    $form['created_by']
                                ]);
                            }
                        }
                    }

                    // Update application with submitted form data and mark as completado
                    // Preserve basic applicant fields registered at creation
                    $stmtBasicData = $this->db->prepare("SELECT data_json FROM applications WHERE id = ?");
                    $stmtBasicData->execute([$applicationId]);
                    $existingAppRow = $stmtBasicData->fetch();
                    $existingBasic  = json_decode($existingAppRow['data_json'] ?? '{}', true) ?: [];
                    $basicKeys      = ['nombre', 'apellidos', 'email', 'telefono', 'nombre_cliente', 'pago', 'fecha_cita'];
                    foreach ($basicKeys as $bk) {
                        if (!empty($existingBasic[$bk])) {
                            $data[$bk] = $existingBasic[$bk];
                        }
                    }
                    $submissionData = json_encode($data, JSON_UNESCAPED_UNICODE);

                    if ($this->columnExists('applications', 'progress_percentage')) {
                        $this->db->prepare("
                            UPDATE applications
                            SET form_link_status = 'completado', data_json = ?, progress_percentage = 100
                            WHERE id = ?
                        ")->execute([$submissionData, $applicationId]);
                    } else {
                        $this->db->prepare("
                            UPDATE applications
                            SET form_link_status = 'completado', data_json = ?
                            WHERE id = ?
                        ")->execute([$submissionData, $applicationId]);
                    }

                    // Link submission to application
                    $this->db->prepare("
                        UPDATE public_form_submissions SET application_id = ? WHERE id = ?
                    ")->execute([$applicationId, $submissionId]);

                    // Auto-advance to ROJO if client form completed, info sheet exists, AND base documents are uploaded
                    $stmtApp = $this->db->prepare("SELECT status, subtype, is_canadian_visa, canadian_modalidad, canadian_tipo FROM applications WHERE id = ?");
                    $stmtApp->execute([$applicationId]);
                    $currentApp = $stmtApp->fetch();
                    $stmtSheet = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                    $stmtSheet->execute([$applicationId]);
                    $hasInfoSheet = $stmtSheet->fetch();

                    if ($hasInfoSheet && $currentApp && $currentApp['status'] === STATUS_NUEVO) {
                        $hasPasaporte = $this->hasDocumentOfType($applicationId, 'pasaporte_vigente');

                        $isCanadianVisa = !empty($currentApp['is_canadian_visa']);

                        if ($isCanadianVisa) {
                            // Canadian visa flow: check visa_canadiense_anterior and eta_anterior
                            $isRenovacion = stripos($currentApp['canadian_modalidad'] ?? '', 'renov') !== false;
                            $isETA        = stripos($currentApp['canadian_tipo'] ?? '', 'ETA') !== false;

                            $hasVisaCanadiensPrev = true;
                            if ($isRenovacion) {
                                $hasVisaCanadiensPrev = $this->hasDocumentOfType($applicationId, 'visa_canadiense_anterior');
                            }

                            $hasEtaAnterior = true;
                            if ($isETA && $isRenovacion) {
                                $hasEtaAnterior = $this->hasDocumentOfType($applicationId, 'eta_anterior');
                            }

                            if ($hasPasaporte && $hasVisaCanadiensPrev && $hasEtaAnterior) {
                                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_LISTO_SOLICITUD, $applicationId]);
                                $this->db->prepare("
                                    INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                    VALUES (?, ?, ?, ?, ?)
                                ")->execute([$applicationId, STATUS_NUEVO, STATUS_LISTO_SOLICITUD, 'Cambio automático: cuestionario completado, hoja de información y documentos base completos (Visa Canadiense)', $form['created_by']]);
                            }
                        } else {
                            // Standard flow: check visa_anterior for renovation
                            $isRenovacion = stripos($currentApp['subtype'] ?? '', 'renov') !== false;
                            $hasVisaAnterior = true;
                            if ($isRenovacion) {
                                $hasVisaAnterior = $this->hasDocumentOfType($applicationId, 'visa_anterior');
                            }

                            if ($hasPasaporte && $hasVisaAnterior) {
                                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_VALIDANDO_RESPUESTAS, $applicationId]);
                                $this->db->prepare("
                                    INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                    VALUES (?, ?, ?, ?, ?)
                                ")->execute([$applicationId, STATUS_NUEVO, STATUS_VALIDANDO_RESPUESTAS, 'Cambio automático: cuestionario completado, hoja de información y documentos base registrados', $form['created_by']]);
                            }
                        }
                    }

                    // Log customer journey
                    $formName = htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8');
                    logCustomerJourney(
                        $applicationId,
                        'form_submission',
                        'Cuestionario del cliente completado',
                        "Formulario '$formName' completado por el cliente vía enlace",
                        'online'
                    );
                    logAdminControlEvent(
                        'formularios',
                        'publico_completado',
                        "Cuestionario del cliente completado para solicitud #$applicationId",
                        [
                            'application_id' => $applicationId,
                            'entity_type' => 'formulario',
                            'entity_id' => $form['id'],
                            'priority' => 'alta',
                            'metadata' => [
                                'formulario' => $form['name'],
                                'submission_id' => $submissionId,
                                'archivos_subidos' => array_keys($uploadedFiles)
                            ]
                        ]
                    );
                } else {
                // Generate folio
                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as max_num 
                    FROM applications WHERE folio LIKE ?
                ");
                $stmt->execute(["VISA-$year-%"]);
                $result = $stmt->fetch();
                $nextNum = ($result['max_num'] ?? 0) + 1;
                $folio = sprintf('VISA-%s-%06d', $year, $nextNum);
                
                // Create application
                $stmt = $this->db->prepare("
                    INSERT INTO applications 
                    (folio, form_id, form_version, type, subtype, status, data_json, 
                     progress_percentage, is_draft, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
                ");
                $stmt->execute([
                    $folio,
                    $form['id'],
                    $form['version'],
                    $form['type'],
                    $form['subtype'],
                    STATUS_FORMULARIO_RECIBIDO,
                    $submissionData,
                    100,
                    $form['created_by']
                ]);
                
                $applicationId = $this->db->lastInsertId();
                
                // Save uploaded files as documents
                if (!empty($uploadedFiles)) {
                    $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $applicationId;
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    foreach ($uploadedFiles as $fieldId => $fileInfo) {
                        $newFileName = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileInfo['name']);
                        $filePath = $uploadDir . '/' . $newFileName;
                        
                        if (move_uploaded_file($fileInfo['tmp_name'], $filePath)) {
                            // Save document record
                            $relativePath = '/uploads/applications/' . $applicationId . '/' . $newFileName;
                            $stmt = $this->db->prepare("
                                INSERT INTO documents (application_id, name, file_path, file_type, file_size, uploaded_by)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $applicationId,
                                $fileInfo['label'] . ' - ' . $fileInfo['name'],
                                $relativePath,
                                $fileInfo['type'],
                                $fileInfo['size'],
                                $form['created_by']
                            ]);
                        }
                    }
                }
                
                // Link submission to application
                $stmt = $this->db->prepare("
                    UPDATE public_form_submissions 
                    SET application_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$applicationId, $submissionId]);
                
                // Create initial status history
                $stmt = $this->db->prepare("
                    INSERT INTO status_history (application_id, new_status, comment, changed_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $applicationId,
                    STATUS_FORMULARIO_RECIBIDO,
                    'Solicitud creada desde formulario público',
                    $form['created_by']
                ]);
                
                // Create financial status
                $stmt = $this->db->prepare("
                    INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                    VALUES (?, 0, 0, 0, ?)
                ");
                $stmt->execute([$applicationId, FINANCIAL_PENDIENTE]);
                
                // Log customer journey
                $formName = htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8');
                logCustomerJourney(
                    $applicationId,
                    'form_submission',
                    'Formulario público completado',
                    "Formulario '$formName' completado vía enlace público",
                    'online'
                );
                logAdminControlEvent(
                    'formularios',
                    'publico_solicitud_creada',
                    "Formulario publico completado y convertido en solicitud $folio",
                    [
                        'application_id' => $applicationId,
                        'folio' => $folio,
                        'entity_type' => 'formulario',
                        'entity_id' => $form['id'],
                        'priority' => 'alta',
                        'metadata' => [
                            'formulario' => $form['name'],
                            'submission_id' => $submissionId,
                            'archivos_subidos' => array_keys($uploadedFiles)
                        ]
                    ]
                );
                } // end else (new application)
            }
            
            echo json_encode([
                'success' => true,
                'submissionId' => $submissionId,
                'progressPercentage' => round($progressPercentage, 2),
                'message' => $isCompleted ? 'Formulario enviado exitosamente' : 'Progreso guardado'
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al guardar formulario público: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el formulario']);
        } catch (Throwable $e) {
            error_log("Error inesperado al guardar formulario público: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error inesperado al guardar el formulario']);
        }
    }

    private function columnExists(string $table, string $column): bool {
        static $cache = [];
        $key = $table . '.' . $column;

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
            ");
            $stmt->execute([$table, $column]);
            $cache[$key] = (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error verificando columna $table.$column: " . $e->getMessage());
            $cache[$key] = false;
        }

        return $cache[$key];
    }

    private function hasDocumentOfType(int $applicationId, string $docType): bool {
        if (!$this->columnExists('documents', 'doc_type')) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = ?");
        $stmt->execute([$applicationId, $docType]);
        return (bool) $stmt->fetch();
    }
    
    /**
     * View for public forms (no main layout)
     */
    private function viewPublic($view, $data = []) {
        extract($data);
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Vista no encontrada: $view";
        }
    }
}
