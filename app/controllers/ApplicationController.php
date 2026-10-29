<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ApplicationController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $status = $_GET['status'] ?? '';
        $flow = $_GET['flow'] ?? '';  // 'normal', 'canadiense', or '' (todos)
        
        try {
            // Construir query según rol
            $where = [];
            $params = [];
            
            if ($role === ROLE_ASESOR) {
                // REGLA CRÍTICA: Asesor solo puede ver SUS PROPIAS solicitudes y no las cerradas
                $where[] = "a.created_by = ?";
                $params[] = $userId;
                $where[] = "a.status != ?";
                $params[] = STATUS_TRAMITE_CERRADO;
            }
            
            if (!empty($status)) {
                $where[] = "a.status = ?";
                $params[] = $status;
            }
            
            if ($flow === 'canadiense') {
                $where[] = "a.is_canadian_visa = 1";
            } elseif ($flow === 'normal') {
                $where[] = "(a.is_canadian_visa = 0 OR a.is_canadian_visa IS NULL)";
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Contar total
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM applications a $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Obtener solicitudes
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       f.name as form_name,
                       fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $applications = $stmt->fetchAll();
            
            $totalPages = ceil($total / $limit);
            
            $this->view('applications/index', [
                'applications' => $applications,
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'status' => $status,
                'flow' => $flow
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en listado de solicitudes: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitudes';
            $this->view('applications/index', ['applications' => []]);
        }
    }
    
    public function create() {
        $this->requireLogin();
        
        try {
            // Obtener formularios publicados
            $stmt = $this->db->query("
                SELECT * FROM forms 
                WHERE is_published = 1 
                ORDER BY type, name
            ");
            $forms = $stmt->fetchAll();
            
            $this->view('applications/create', ['forms' => $forms]);
            
        } catch (PDOException $e) {
            error_log("Error al cargar formularios: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar formularios';
            $this->redirect('/solicitudes');
        }
    }
    
    public function store() {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/crear');
        }

        $isCanadianVisa = (($_POST['is_canadian_visa'] ?? '0') === '1');
        $formId = intval($_POST['form_id'] ?? 0);
        $formData = $_POST['form_data'] ?? [];

        // Keep basic creation fields; sanitise values
        $basicKeys    = ['nombre', 'apellidos', 'email', 'telefono', 'nombre_cliente', 'pago', 'fecha_cita'];
        $filteredData = [];
        foreach ($basicKeys as $key) {
            $filteredData[$key] = trim($formData[$key] ?? '');
        }

        $isUniqueUsPassportForm = false;
        if (!$isCanadianVisa && $formId > 0) {
            $stmtFormType = $this->db->prepare("
                SELECT name, type, subtype
                FROM forms
                WHERE id = ? AND is_published = 1
            ");
            $stmtFormType->execute([$formId]);
            $selectedForm = $stmtFormType->fetch();
            if ($selectedForm) {
                $isUniqueUsPassportForm =
                    trim($selectedForm['name']) === 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO' &&
                    trim($selectedForm['type']) === 'Pasaporte' &&
                    trim($selectedForm['subtype'] ?? '') === 'Única Vez';
            }
        }

        if ($isUniqueUsPassportForm) {
            if (empty($filteredData['nombre_cliente'])) {
                $_SESSION['error'] = 'El nombre del cliente es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            if (empty($filteredData['pago'])) {
                $_SESSION['error'] = 'El pago es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            if (empty($filteredData['fecha_cita'])) {
                $_SESSION['error'] = 'La fecha de la cita es obligatoria';
                $this->redirect('/solicitudes/crear');
            }
            $clientName = trim($filteredData['nombre_cliente']);
        } else {
            if (empty($filteredData['nombre'])) {
                $_SESSION['error'] = 'El nombre del solicitante es obligatorio';
                $this->redirect('/solicitudes/crear');
            }
            $clientName = trim($filteredData['nombre'] . ' ' . $filteredData['apellidos']);
        }

        // ── Canadian Visa flow ────────────────────────────────────
        if ($isCanadianVisa) {
            $canadianTipo      = trim($_POST['canadian_tipo'] ?? '');
            $canadianModalidad = trim($_POST['canadian_modalidad'] ?? '');

            if (empty($canadianTipo) || empty($canadianModalidad)) {
                $_SESSION['error'] = 'Debe seleccionar el Tipo y la Modalidad para Visa Canadiense';
                $this->redirect('/solicitudes/crear');
            }

            if ($formId <= 0) {
                $_SESSION['error'] = 'Debe seleccionar el formulario de cliente para Visa Canadiense';
                $this->redirect('/solicitudes/crear');
            }

            // Obtener versión del formulario seleccionado
            $stmtForm = $this->db->prepare("SELECT id, version FROM forms WHERE id = ? AND is_published = 1");
            $stmtForm->execute([$formId]);
            $form = $stmtForm->fetch();

            if (!$form) {
                $_SESSION['error'] = 'El formulario seleccionado no es válido';
                $this->redirect('/solicitudes/crear');
            }

            $formVersion = intval($form['version'] ?? 1);

            try {
                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as last_number
                    FROM applications WHERE folio LIKE ?
                ");
                $stmt->execute(["VISA-$year-%"]);
                $result     = $stmt->fetch();
                $nextNumber = ($result['last_number'] ?? 0) + 1;
                $folio      = sprintf("VISA-%s-%06d", $year, $nextNumber);

                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO applications
                            (folio, form_id, form_version, type, subtype,
                             is_canadian_visa, canadian_tipo, canadian_modalidad,
                             data_json, client_name, created_by)
                        VALUES (?, ?, ?, 'Visa', ?, 1, ?, ?, ?, ?, ?)
                    ");
                    // subtype = canadian_modalidad for backward-compat with $isRenovacion check
                    $stmt->execute([
                        $folio,
                        $formId,             // form_id
                        $formVersion,        // form_version
                        $canadianModalidad,  // subtype (backward-compat)
                        $canadianTipo,       // canadian_tipo
                        $canadianModalidad,  // canadian_modalidad
                        json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                        $clientName,
                        $_SESSION['user_id']
                    ]);
                } catch (PDOException $e) {
                    // Fallback if new columns don't exist yet
                    $stmt = $this->db->prepare("
                        INSERT INTO applications
                            (folio, form_id, form_version, type, subtype, data_json, created_by)
                        VALUES (?, ?, ?, 'Visa', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $folio,
                        $formId,
                        $formVersion,
                        $canadianModalidad,
                        json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                        $_SESSION['user_id']
                    ]);
                }

                $applicationId = $this->db->lastInsertId();

                $this->db->prepare("
                    INSERT INTO status_history (application_id, new_status, comment, changed_by)
                    VALUES (?, ?, ?, ?)
                ")->execute([$applicationId, STATUS_NUEVO, 'Solicitud Visa Canadiense creada', $_SESSION['user_id']]);

                $this->db->prepare("
                    INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                    VALUES (?, 0, 0, 0, ?)
                ")->execute([$applicationId, FINANCIAL_PENDIENTE]);

                logAdminControlEvent(
                    'solicitudes',
                    'crear',
                    "Solicitud Visa Canadiense creada: $folio",
                    [
                        'application_id' => $applicationId,
                        'folio' => $folio,
                        'entity_type' => 'solicitud',
                        'entity_id' => $applicationId,
                        'priority' => 'alta',
                        'metadata' => [
                            'cliente' => $clientName,
                            'tipo' => $canadianTipo,
                            'modalidad' => $canadianModalidad
                        ]
                    ]
                );

                $_SESSION['success'] = "Solicitud Visa Canadiense creada: $folio";
                $this->redirect('/solicitudes/ver/' . $applicationId);

            } catch (PDOException $e) {
                error_log("Error al crear solicitud canadiense: " . $e->getMessage());
                $_SESSION['error'] = 'Error al crear solicitud';
                $this->redirect('/solicitudes/crear');
            }
            return;
        }

        // ── Standard flow ─────────────────────────────────────────
        if ($formId <= 0) {
            $_SESSION['error'] = 'Debe seleccionar un tipo de trámite';
            $this->redirect('/solicitudes/crear');
        }

        try {
            // Obtener información del formulario
            $stmt = $this->db->prepare("SELECT * FROM forms WHERE id = ? AND is_published = 1");
            $stmt->execute([$formId]);
            $form = $stmt->fetch();

            if (!$form) {
                $_SESSION['error'] = 'Formulario no encontrado';
                $this->redirect('/solicitudes/crear');
            }

            // Generar folio único
            $year = date('Y');
            $stmt = $this->db->prepare("
                SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) as last_number
                FROM applications
                WHERE folio LIKE ?
            ");
            $stmt->execute(["VISA-$year-%"]);
            $result     = $stmt->fetch();
            $nextNumber = ($result['last_number'] ?? 0) + 1;
            $folio      = sprintf("VISA-%s-%06d", $year, $nextNumber);

            // Crear solicitud con datos básicos
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO applications (folio, form_id, form_version, type, subtype, data_json, client_name, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $folio,
                    $formId,
                    $form['version'],
                    $form['type'],
                    $form['subtype'],
                    json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                    $clientName,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $e) {
                // Fallback if client_name column doesn't exist yet
                $stmt = $this->db->prepare("
                    INSERT INTO applications (folio, form_id, form_version, type, subtype, data_json, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $folio,
                    $formId,
                    $form['version'],
                    $form['type'],
                    $form['subtype'],
                    json_encode($filteredData, JSON_UNESCAPED_UNICODE),
                    $_SESSION['user_id']
                ]);
            }

            $applicationId = $this->db->lastInsertId();

            // Crear registro de historial
            $stmt = $this->db->prepare("
                INSERT INTO status_history (application_id, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $applicationId,
                STATUS_NUEVO,
                'Solicitud creada',
                $_SESSION['user_id']
            ]);

            // Crear estado financiero inicial
            $stmt = $this->db->prepare("
                INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
                VALUES (?, 0, 0, 0, ?)
            ");
            $stmt->execute([$applicationId, FINANCIAL_PENDIENTE]);

            logAdminControlEvent(
                'solicitudes',
                'crear',
                "Solicitud creada: $folio",
                [
                    'application_id' => $applicationId,
                    'folio' => $folio,
                    'entity_type' => 'solicitud',
                    'entity_id' => $applicationId,
                    'priority' => 'alta',
                    'metadata' => [
                        'cliente' => $clientName,
                        'tipo' => $form['type'] ?? null,
                        'subtipo' => $form['subtype'] ?? null,
                        'formulario' => $form['name'] ?? null
                    ]
                ]
            );

            $_SESSION['success'] = "Solicitud creada exitosamente: $folio";
            $this->redirect('/solicitudes/ver/' . $applicationId);

        } catch (PDOException $e) {
            error_log("Error al crear solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear solicitud';
            $this->redirect('/solicitudes/crear');
        }
    }
    
    public function show($id) {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        
        try {
            // Obtener solicitud
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name as creator_name,
                       f.name as form_name, f.fields_json,
                       fs.total_costs, fs.total_paid, fs.balance, fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // REGLA CRÍTICA: Asesor solo puede ver SUS PROPIAS solicitudes y no las cerradas
            if ($role === ROLE_ASESOR) {
                if ($application['status'] === STATUS_TRAMITE_CERRADO || $application['status'] === STATUS_FINALIZADO) {
                    $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (intval($application['created_by']) !== intval($userId)) {
                    $_SESSION['error'] = 'No tiene permisos para ver esta solicitud';
                    $this->redirect('/solicitudes');
                }
            }
            
            // Obtener historial de estatus
            $stmt = $this->db->prepare("
                SELECT sh.*, u.full_name as changed_by_name
                FROM status_history sh
                LEFT JOIN users u ON sh.changed_by = u.id
                WHERE sh.application_id = ?
                ORDER BY sh.created_at DESC
            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll();
            
            // Obtener documentos
            $stmt = $this->db->prepare("
                SELECT d.*, u.full_name as uploaded_by_name
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE d.application_id = ?
                ORDER BY d.created_at DESC
            ");
            $stmt->execute([$id]);
            $documents = $stmt->fetchAll();
            
            // Obtener indicaciones/notas
            $stmt = $this->db->prepare("
                SELECT n.*, u.full_name as created_by_name, u.role as created_by_role
                FROM application_notes n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.application_id = ?
                ORDER BY n.is_important DESC, n.created_at DESC
            ");
            $stmt->execute([$id]);
            $notes = $stmt->fetchAll();
            
            // Obtener costos (solo Admin y Gerente)
            $costs = [];
            $payments = [];
            if ($this->canAccessFinancial()) {
                $stmt = $this->db->prepare("
                    SELECT fc.*, u.full_name as created_by_name
                    FROM financial_costs fc
                    LEFT JOIN users u ON fc.created_by = u.id
                    WHERE fc.application_id = ?
                    ORDER BY fc.created_at DESC
                ");
                $stmt->execute([$id]);
                $costs = $stmt->fetchAll();
                
                $stmt = $this->db->prepare("
                    SELECT p.*, u.full_name as registered_by_name
                    FROM payments p
                    LEFT JOIN users u ON p.registered_by = u.id
                    WHERE p.application_id = ?
                    ORDER BY p.payment_date DESC
                ");
                $stmt->execute([$id]);
                $payments = $stmt->fetchAll();
            }

            // Obtener hoja de información si existe
            $infoSheet = null;
            $familiarMembers = [];
            try {
                $stmt = $this->db->prepare("SELECT * FROM information_sheets WHERE application_id = ?");
                $stmt->execute([$id]);
                $infoSheet = $stmt->fetch() ?: null;

                if ($infoSheet) {
                    try {
                        $stmtFam = $this->db->prepare("SELECT * FROM information_sheet_familiar WHERE information_sheet_id = ? ORDER BY id");
                        $stmtFam->execute([$infoSheet['id']]);
                        $familiarMembers = $stmtFam->fetchAll();
                    } catch (PDOException $e) {
                        // Table may not exist yet
                    }
                }
            } catch (PDOException $e) {
                // Tabla puede no existir aún
            }

            // Obtener formularios publicados (para dropdown de envío a cliente)
            $publishedForms = [];
            try {
                $stmt = $this->db->query("SELECT id, name, type, subtype FROM forms WHERE is_published = 1 ORDER BY type, name");
                $publishedForms = $stmt->fetchAll();
            } catch (PDOException $e) {}

            // Obtener token público del formulario vinculado (para generar enlace de cliente)
            $formLinkToken = null;
            $linkedFormId = null;
            if (!empty($application['form_link_id'])) {
                $candidateFormId = intval($application['form_link_id']);
                if ($candidateFormId > 0) {
                    $linkedFormId = $candidateFormId;
                }
            } elseif (!empty($application['form_id'])) {
                $candidateFormId = intval($application['form_id']);
                if ($candidateFormId > 0) {
                    $linkedFormId = $candidateFormId;
                }
            }
            if ($linkedFormId !== null) {
                try {
                    $stmt = $this->db->prepare("SELECT public_token FROM forms WHERE id = ?");
                    $stmt->execute([$linkedFormId]);
                    $linkedFormRow = $stmt->fetch();
                    $formLinkToken = $linkedFormRow['public_token'] ?? null;
                } catch (PDOException $e) {}
            }
            
            $cashDestinations = financialGetActiveCashDestinations($this->db);

            // V5.1: el trámite y precio de Hoja de Información se resuelven
            // automáticamente desde la Solicitud/Formulario. La asesora ya no
            // selecciona el servicio manualmente.
            $autoFinancialService = null;
            $infoSheetSale = null;
            try {
                $autoFinancialService = $this->resolveApplicationFinancialService($application);

                if ($infoSheet && !empty($infoSheet['financial_sale_id'])) {
                    $stmtInfoSale = $this->db->prepare("
                        SELECT * FROM income_sales WHERE id=? LIMIT 1
                    ");
                    $stmtInfoSale->execute([(int)$infoSheet['financial_sale_id']]);
                    $infoSheetSale = $stmtInfoSale->fetch() ?: null;
                }
            } catch (Throwable $e) {
                error_log('No se pudo resolver servicio automático de Hoja de Información: '.$e->getMessage());
                $autoFinancialService = null;
                $infoSheetSale = null;
            }

            // Control de Ingresos V3: expediente financiero comercial.
            $incomeSales = [];
            $incomePaymentHistory = [];
            try {
                $stmt = $this->db->prepare("
                    SELECT s.*, c.code AS catalog_master_code,
                           COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) AS validated_paid,
                           COALESCE(SUM(CASE WHEN p.validation_status='PENDIENTE' THEN p.amount ELSE 0 END),0) AS pending_validation
                    FROM income_sales s
                    LEFT JOIN income_catalog c ON c.id=s.catalog_id
                    LEFT JOIN income_payments p ON p.sale_id=s.id
                    WHERE s.application_id=? AND s.financial_status<>'CANCELADO'
                    GROUP BY s.id
                    ORDER BY s.contract_date DESC,s.id DESC
                ");
                $stmt->execute([$id]);
                $incomeSales = $stmt->fetchAll();

                $stmt = $this->db->prepare("
                    SELECT p.*, pc.concept AS payment_concept,
                           r.full_name AS registered_by_name,
                           v.full_name AS validated_by_name
                    FROM income_payments p
                    LEFT JOIN income_payment_concepts pc ON pc.code=p.payment_concept_code
                    LEFT JOIN users r ON r.id=p.registered_by
                    LEFT JOIN users v ON v.id=p.validated_by
                    WHERE p.application_id=?
                    ORDER BY p.payment_date DESC,p.id DESC
                ");
                $stmt->execute([$id]);
                $incomePaymentHistory = $stmt->fetchAll();
            } catch (Throwable $e) {
                $incomeSales = [];
                $incomePaymentHistory = [];
            }

            // V4: vínculo con WhatsApp/Bot. El snapshot se conserva aunque
            // posteriormente el cliente complete o actualice el formulario.
            $botApplicationLink = null;
            $botPrefill = null;
            try {
                $stmt = $this->db->prepare("
                    SELECT bal.*, bc.phone AS whatsapp_phone, bc.profile_name AS whatsapp_profile_name,
                           bsc.name AS bot_service_name
                    FROM bot_application_links bal
                    LEFT JOIN bot_contacts bc ON bc.id = bal.contact_id
                    LEFT JOIN bot_service_catalog bsc ON bsc.code = bal.service_code
                    WHERE bal.application_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$id]);
                $botApplicationLink = $stmt->fetch() ?: null;
                if ($botApplicationLink && !empty($botApplicationLink['prefill_json'])) {
                    $decodedPrefill = json_decode($botApplicationLink['prefill_json'], true);
                    if (is_array($decodedPrefill)) {
                        $botPrefill = $decodedPrefill;
                    }
                }
            } catch (Throwable $e) {
                $botApplicationLink = null;
                $botPrefill = null;
            }

            $infoSheetCsrfToken = $this->infoSheetCsrfToken();

            $this->view('applications/show', [
                'application' => $application,
                'history' => $history,
                'documents' => $documents,
                'notes' => $notes,
                'costs' => $costs,
                'payments' => $payments,
                'infoSheet' => $infoSheet,
                'familiarMembers' => $familiarMembers,
                'publishedForms' => $publishedForms,
                'formLinkToken' => $formLinkToken,
                'cashDestinations' => $cashDestinations,
                'autoFinancialService' => $autoFinancialService,
                'infoSheetSale' => $infoSheetSale,
                'incomeSales' => $incomeSales,
                'incomePaymentHistory' => $incomePaymentHistory,
                'botApplicationLink' => $botApplicationLink,
                'botPrefill' => $botPrefill,
                'infoSheetCsrfToken' => $infoSheetCsrfToken,
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al ver solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitud';
            $this->redirect('/solicitudes');
        }
    }
    

    /**
     * Editar la información capturada de una solicitud.
     * Asesor: sólo sus propias solicitudes activas.
     * Gerente/Admin: cualquier solicitud.
     */
    public function edit($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        $role = $this->getUserRole();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name AS creator_name,
                       f.name AS form_name, f.fields_json, f.type AS form_type, f.subtype AS form_subtype
                FROM applications a
                LEFT JOIN users u ON u.id = a.created_by
                LEFT JOIN forms f ON f.id = a.form_id
                WHERE a.id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR) {
                if ((int)$application['created_by'] !== $userId) {
                    $_SESSION['error'] = 'No tiene permisos para editar esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (in_array($application['status'], [STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO], true)) {
                    $_SESSION['error'] = 'Una solicitud cerrada no puede ser editada por una asesora';
                    $this->redirect('/solicitudes/ver/' . (int)$id);
                }
            }

            $botApplicationLink = null;
            try {
                $stmt = $this->db->prepare("
                    SELECT bal.id, bal.service_code, bal.conversation_id
                    FROM bot_application_links bal
                    WHERE bal.application_id = ?
                    LIMIT 1
                ");
                $stmt->execute([(int)$id]);
                $botApplicationLink = $stmt->fetch() ?: null;
            } catch (Throwable $e) {
                $botApplicationLink = null;
            }

            $this->view('applications/edit', [
                'application' => $application,
                'botApplicationLink' => $botApplicationLink,
                'editCsrfToken' => $this->applicationEditCsrfToken(),
            ]);

        } catch (PDOException $e) {
            error_log('Error al cargar edición de solicitud: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo cargar la solicitud para edición';
            $this->redirect('/solicitudes');
        }
    }

    /**
     * Guardar información editable de una solicitud sin alterar su folio,
     * responsable, historial, documentos o vínculo original con WhatsApp.
     */
    public function update($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . (int)$id);
        }

        $this->verifyApplicationEditCsrf();

        $role = $this->getUserRole();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $stmt = $this->db->prepare("
                SELECT a.*, f.name AS form_name, f.fields_json
                FROM applications a
                LEFT JOIN forms f ON f.id = a.form_id
                WHERE a.id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR) {
                if ((int)$application['created_by'] !== $userId) {
                    $_SESSION['error'] = 'No tiene permisos para editar esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if (in_array($application['status'], [STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO], true)) {
                    $_SESSION['error'] = 'Una solicitud cerrada no puede ser editada por una asesora';
                    $this->redirect('/solicitudes/ver/' . (int)$id);
                }
            }

            $existingData = json_decode((string)($application['data_json'] ?? ''), true);
            if (!is_array($existingData)) $existingData = [];

            $posted = $_POST['form_data'] ?? [];
            if (!is_array($posted)) $posted = [];

            $changedFields = [];
            $setValue = function($key, $value) use (&$existingData, &$changedFields) {
                $old = $existingData[$key] ?? null;
                if ($old !== $value) {
                    $changedFields[] = $key;
                    $existingData[$key] = $value;
                }
            };

            $isUniqueUsPassportForm =
                trim((string)($application['form_name'] ?? '')) === 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO'
                && trim((string)($application['type'] ?? '')) === 'Pasaporte'
                && trim((string)($application['subtype'] ?? '')) === 'Única Vez';

            if ($isUniqueUsPassportForm) {
                $nombreCliente = trim(strip_tags((string)($posted['nombre_cliente'] ?? '')));
                $pago = trim(strip_tags((string)($posted['pago'] ?? '')));
                $fechaCita = trim(strip_tags((string)($posted['fecha_cita'] ?? '')));

                if ($nombreCliente === '') {
                    throw new RuntimeException('El nombre del cliente es obligatorio.');
                }

                $setValue('nombre_cliente', $nombreCliente);
                $setValue('pago', $pago);
                $setValue('fecha_cita', $fechaCita);
                $clientName = $nombreCliente;
            } else {
                $nombre = trim(strip_tags((string)($posted['nombre'] ?? '')));
                $apellidos = trim(strip_tags((string)($posted['apellidos'] ?? '')));
                $email = trim(strip_tags((string)($posted['email'] ?? '')));
                $telefono = trim(strip_tags((string)($posted['telefono'] ?? '')));

                if ($nombre === '') {
                    throw new RuntimeException('El nombre del solicitante es obligatorio.');
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('El correo electrónico no es válido.');
                }

                $setValue('nombre', $nombre);
                $setValue('apellidos', $apellidos);
                $setValue('email', $email);
                $setValue('telefono', $telefono);
                $clientName = trim($nombre . ' ' . $apellidos);
            }

            // Editar únicamente campos definidos por el formulario actual.
            $fieldsJson = json_decode((string)($application['fields_json'] ?? ''), true);
            $fields = is_array($fieldsJson) ? ($fieldsJson['fields'] ?? []) : [];

            $basicKeys = ['nombre','apellidos','email','telefono','nombre_cliente','pago','fecha_cita'];
            foreach ($fields as $field) {
                $fieldId = trim((string)($field['id'] ?? ''));
                $fieldType = strtolower(trim((string)($field['type'] ?? 'text')));

                if ($fieldId === '' || in_array($fieldId, $basicKeys, true)) continue;
                if (in_array($fieldType, ['file','label','heading','section','paragraph','html'], true)) continue;

                if ($fieldType === 'checkbox') {
                    $value = $posted[$fieldId] ?? [];
                    if (!is_array($value)) $value = [$value];
                    $value = array_values(array_filter(array_map(function($v) {
                        return trim(strip_tags((string)$v));
                    }, $value), function($v) {
                        return $v !== '';
                    }));
                    $setValue($fieldId, $value);
                } else {
                    $value = trim(strip_tags((string)($posted[$fieldId] ?? '')));
                    $setValue($fieldId, $value);
                }
            }

            $canadianTipo = $application['canadian_tipo'] ?? null;
            $canadianModalidad = $application['canadian_modalidad'] ?? null;
            $subtype = $application['subtype'] ?? null;

            if (!empty($application['is_canadian_visa'])) {
                $canadianTipo = trim((string)($_POST['canadian_tipo'] ?? ''));
                $canadianModalidad = trim((string)($_POST['canadian_modalidad'] ?? ''));

                $allowedTipos = ['Visa Canadiense', 'ETA Canadiense'];
                $allowedModalidades = ['Primera vez', 'Renovación'];

                if (!in_array($canadianTipo, $allowedTipos, true)
                    || !in_array($canadianModalidad, $allowedModalidades, true)) {
                    throw new RuntimeException('Tipo o modalidad canadiense no válidos.');
                }
                $subtype = $canadianModalidad;
            }

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($newJson === false) {
                throw new RuntimeException('No se pudo preparar la información para guardar.');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE applications
                SET data_json = ?,
                    client_name = ?,
                    subtype = ?,
                    canadian_tipo = ?,
                    canadian_modalidad = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $newJson,
                $clientName,
                $subtype,
                $canadianTipo,
                $canadianModalidad,
                (int)$id,
            ]);

            // Mantener consistente el nombre visible en Control de Ingresos
            // si ya existe una venta financiera para este expediente.
            try {
                $stmt = $this->db->prepare("
                    UPDATE income_sales
                    SET client_name_snapshot = ?, updated_at = NOW()
                    WHERE application_id = ? AND financial_status <> 'CANCELADO'
                ");
                $stmt->execute([$clientName, (int)$id]);
            } catch (Throwable $e) {
                // La migración de ingresos puede no estar instalada todavía.
            }

            $this->db->commit();

            $changedFields = array_values(array_unique($changedFields));

            logAudit(
                'update',
                'solicitudes',
                'Información de solicitud #' . (int)$id . ' editada desde el detalle'
            );

            logAdminControlEvent(
                'solicitudes',
                'informacion_editada',
                'Información de solicitud #' . (int)$id . ' actualizada',
                [
                    'application_id' => (int)$id,
                    'entity_type' => 'solicitud',
                    'entity_id' => (int)$id,
                    'priority' => 'normal',
                    'metadata' => [
                        'campos_modificados' => $changedFields,
                        'usuario_id' => $userId,
                    ],
                ]
            );

            $_SESSION['success'] = $changedFields
                ? 'Información actualizada correctamente.'
                : 'No se detectaron cambios en la información.';
            $this->redirect('/solicitudes/ver/' . (int)$id);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Error al editar solicitud: ' . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/solicitudes/editar/' . (int)$id);
        }
    }

    public function changeStatus($id) {
        $this->requireLogin();

        $role      = $this->getUserRole();
        $newStatus = $_POST['status'] ?? '';

        // Asesor may only close a trámite (from morado) or confirm biometrics attendance (Canadian: azul → morado)
        if ($role === ROLE_ASESOR) {
            if ($newStatus !== STATUS_TRAMITE_CERRADO && $newStatus !== STATUS_EN_ESPERA_RESULTADO) {
                http_response_code(403);
                die("Acceso denegado. No tiene permisos para esta acción.");
            }
        } elseif (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE])) {
            http_response_code(403);
            die("Acceso denegado. No tiene permisos para acceder a esta sección.");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $comment = trim($_POST['comment'] ?? '');

        if (empty($newStatus)) {
            $_SESSION['error'] = 'Debe seleccionar un estatus';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        // Validar comentario obligatorio en rechazo
        if ($newStatus === STATUS_RECHAZADO && empty($comment)) {
            $_SESSION['error'] = 'El comentario es obligatorio para rechazar una solicitud';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            // Obtener solicitud completa para validaciones
            $stmt = $this->db->prepare("
                SELECT a.*, a.subtype
                FROM applications a WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            $previousStatus = $application['status'];

            // Asesor: validate specific allowed transitions
            if ($role === ROLE_ASESOR) {
                if (intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                    $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                    $this->redirect('/solicitudes');
                }
                if ($newStatus === STATUS_TRAMITE_CERRADO) {
                    if ($previousStatus !== STATUS_EN_ESPERA_RESULTADO) {
                        $_SESSION['error'] = 'No puede cerrar un trámite que no está en estado En espera de resultado';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }
                } elseif ($newStatus === STATUS_EN_ESPERA_RESULTADO) {
                    // Only allowed for Canadian flow: asesor confirms biometrics attendance (AZUL → MORADO)
                    if (empty($application['is_canadian_visa']) || $previousStatus !== STATUS_CITA_PROGRAMADA) {
                        $_SESSION['error'] = 'No tiene permisos para esta acción';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }
                }
            }

            // ── Detect Canadian Visa flag ───────────────────────────────────
            $isCanadianVisa = !empty($application['is_canadian_visa']);

            // ── Validaciones antes de pasar de NUEVO → ROJO ────────────────────
            if ($previousStatus === STATUS_NUEVO && $newStatus === STATUS_LISTO_SOLICITUD) {
                // 1. Pasaporte subido
                $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                $stmtDoc->execute([$id]);
                if (!$stmtDoc->fetch()) {
                    $_SESSION['error'] = 'No se puede cambiar a este estado: no se ha cargado el pasaporte vigente.';
                    $this->redirect('/solicitudes/ver/' . $id);
                }

                if ($isCanadianVisa) {
                    // Visa canadiense anterior (si Renovación)
                    $isRenovacion = stripos($application['canadian_modalidad'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_canadiense_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere la visa canadiense anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                    // ETA anterior (si ETA Canadiense + Renovación)
                    $isETA = stripos($application['canadian_tipo'] ?? '', 'ETA') !== false;
                    if ($isETA && $isRenovacion) {
                        $stmtEta = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'eta_anterior'");
                        $stmtEta->execute([$id]);
                        if (!$stmtEta->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: se requiere el ETA anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                } else {
                    // 2. Si es renovación (estándar), visa anterior subida
                    $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere cargar la visa anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                }
            }

            // ── Validaciones antes de pasar de ROJO → AMARILLO ─────────────────
            if ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO) {
                if ($isCanadianVisa) {
                    // Para Canadian visa: verificar que los documentos estén cargados en portal
                    $docsUploaded = isset($_POST['canadian_docs_uploaded_portal']) ? 1 : intval($application['canadian_docs_uploaded_portal'] ?? 0);
                    if (!$docsUploaded) {
                        $_SESSION['error'] = 'No se puede avanzar: marque "Documentos cargados en portal Canadá" primero.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }
                } else {
                    // 1. Formulario del cliente completado
                    if ($application['form_link_status'] !== 'completado') {
                        $_SESSION['error'] = 'No se puede cambiar a este estado: el cliente aún no ha completado el cuestionario.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }

                    // 2. Pasaporte subido
                    $stmtDoc = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                    $stmtDoc->execute([$id]);
                    if (!$stmtDoc->fetch()) {
                        $_SESSION['error'] = 'No se puede cambiar a este estado: no se ha cargado el pasaporte vigente.';
                        $this->redirect('/solicitudes/ver/' . $id);
                    }

                    // 3. Si es renovación, visa anterior subida
                    $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
                    if ($isRenovacion) {
                        $stmtVisa = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                        $stmtVisa->execute([$id]);
                        if (!$stmtVisa->fetch()) {
                            $_SESSION['error'] = 'No se puede cambiar a este estado: para renovación se requiere cargar la visa anterior.';
                            $this->redirect('/solicitudes/ver/' . $id);
                        }
                    }
                }
            }

            // ── Campos adicionales por estado ───────────────────────────────────
            $extraSql    = '';
            $extraParams = [];

            // Email notification tracking: set when an appointment date is being saved
            $notifyAppointmentType = null;
            $notifyAppointmentDate = null;

            if ($isCanadianVisa) {
                // ── Canadian visa extra fields ──────────────────────────────
                if ($newStatus === STATUS_LISTO_SOLICITUD || ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO)) {
                    $docsUploaded    = isset($_POST['canadian_docs_uploaded_portal']) ? 1 : 0;
                    $applicationNum  = trim($_POST['canadian_application_number'] ?? '');
                    $extraSql    = ', canadian_docs_uploaded_portal = ?, canadian_application_number = ?';
                    $extraParams = [$docsUploaded, $applicationNum ?: null];
                } elseif ($newStatus === STATUS_EN_ESPERA_PAGO) {
                    // AMARILLO canadiense: biometric appointment fields
                    $biometricGenerated = isset($_POST['canadian_biometric_appointment_generated']) ? 1 : 0;
                    $biometricDate      = !empty($_POST['canadian_biometric_date']) ? $_POST['canadian_biometric_date'] : null;
                    $biometricLocation  = trim($_POST['canadian_biometric_location'] ?? '');
                    $extraSql    = ', canadian_biometric_appointment_generated = ?, canadian_biometric_date = ?, canadian_biometric_location = ?';
                    $extraParams = [$biometricGenerated, $biometricDate, $biometricLocation ?: null];
                    // Trigger email if biometric date is new or changed
                    if (!empty($biometricDate) && $biometricDate !== ($application['canadian_biometric_date'] ?? null)) {
                        $notifyAppointmentType = 'biometric';
                        $notifyAppointmentDate = $biometricDate;
                    }
                } elseif ($newStatus === STATUS_CITA_PROGRAMADA) {
                    // AZUL canadiense: biometrics attendance
                    $attended     = isset($_POST['canadian_client_attended_biometrics']) ? 1 : 0;
                    $attendedDate = !empty($_POST['canadian_biometric_attended_date']) ? $_POST['canadian_biometric_attended_date'] : null;
                    $extraSql    = ', canadian_client_attended_biometrics = ?, canadian_biometric_attended_date = ?';
                    $extraParams = [$attended, $attendedDate];
                } elseif ($newStatus === STATUS_EN_ESPERA_RESULTADO) {
                    // AZUL → MORADO canadiense: biometrics attendance
                    $attended     = isset($_POST['canadian_client_attended_biometrics']) ? 1 : 0;
                    $attendedDate = !empty($_POST['canadian_biometric_attended_date']) ? $_POST['canadian_biometric_attended_date'] : null;
                    $extraSql    = ', canadian_client_attended_biometrics = ?, canadian_biometric_attended_date = ?';
                    $extraParams = [$attended, $attendedDate];
                } elseif ($newStatus === STATUS_TRAMITE_CERRADO) {
                    // MORADO → VERDE canadiense: visa result
                    $visaResult         = trim($_POST['canadian_visa_result'] ?? '');
                    $resolutionDate     = !empty($_POST['canadian_resolution_date']) ? $_POST['canadian_resolution_date'] : null;
                    $guideNumber        = trim($_POST['canadian_guide_number'] ?? '');
                    $finalObservations  = trim($_POST['canadian_final_observations'] ?? '');
                    $extraSql    = ', canadian_visa_result = ?, canadian_resolution_date = ?, canadian_guide_number = ?, canadian_final_observations = ?';
                    $extraParams = [$visaResult ?: null, $resolutionDate, $guideNumber ?: null, $finalObservations ?: null];
                }
            } else {
                // ── Standard flow extra fields ──────────────────────────────
                if ($previousStatus === STATUS_LISTO_SOLICITUD && $newStatus === STATUS_EN_ESPERA_PAGO) {
                    // Checkboxes vienen del estado ROJO (ya deben estar marcados)
                    $officialDone = isset($_POST['official_application_done']) ? 1 : 0;
                    $feeSent      = isset($_POST['consular_fee_sent']) ? 1 : 0;
                    $ds160Num     = trim($_POST['ds160_confirmation_number'] ?? '');
                    $extraSql    = ', official_application_done = ?, consular_fee_sent = ?, ds160_confirmation_number = ?';
                    $extraParams = [$officialDone, $feeSent, $ds160Num ?: null];
                } elseif ($newStatus === STATUS_LISTO_SOLICITUD) {
                    // Saving checkboxes while still in ROJO (no status transition)
                    $officialDone = isset($_POST['official_application_done']) ? 1 : 0;
                    $feeSent      = isset($_POST['consular_fee_sent']) ? 1 : 0;
                    $ds160Num     = trim($_POST['ds160_confirmation_number'] ?? '');
                    $extraSql    = ', official_application_done = ?, consular_fee_sent = ?, ds160_confirmation_number = ?';
                    $extraParams = [$officialDone, $feeSent, $ds160Num ?: null];
                } elseif ($newStatus === STATUS_EN_ESPERA_PAGO) {
                    // Updating AMARILLO fields
                    $consularPaymentConfirmed = isset($_POST['consular_payment_confirmed']) ? 1 : 0;
                    $appointmentDate = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
                    if ($appointmentDate !== null) {
                        $extraSql    = ', consular_payment_confirmed = ?, appointment_date = ?';
                        $extraParams = [$consularPaymentConfirmed, $appointmentDate];
                        // Trigger email if appointment date is new or changed
                        if ($appointmentDate !== ($application['appointment_date'] ?? null)) {
                            $notifyAppointmentType = 'consular';
                            $notifyAppointmentDate = $appointmentDate;
                        }
                    } else {
                        $extraSql    = ', consular_payment_confirmed = ?';
                        $extraParams = [$consularPaymentConfirmed];
                    }
                } elseif ($newStatus === STATUS_TRAMITE_CERRADO) {
                    $dhlTracking  = trim($_POST['dhl_tracking'] ?? '');
                    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
                    $extraSql    = ', dhl_tracking = ?, delivery_date = ?';
                    $extraParams = [$dhlTracking ?: null, $deliveryDate];
                }
            }

            $stmt = $this->db->prepare("UPDATE applications SET status = ? $extraSql WHERE id = ?");
            try {
                $stmt->execute(array_merge([$newStatus], $extraParams, [$id]));
            } catch (PDOException $colErr) {
                // New columns may not exist yet; fall back to status-only update
                error_log("changeStatus column fallback: " . $colErr->getMessage());
                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            }

            // Handle DS-160 file upload (ROJO state, standard flow)
            if (!$isCanadianVisa && $newStatus === STATUS_LISTO_SOLICITUD && isset($_FILES['ds160_file']) && $_FILES['ds160_file']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['ds160_file'], 'ds160');
            }

            // Handle Canadian portal capture (ROJO state, Canadian flow)
            if ($isCanadianVisa && in_array($newStatus, [STATUS_LISTO_SOLICITUD, STATUS_EN_ESPERA_PAGO])
                && isset($_FILES['canadian_portal_capture']) && $_FILES['canadian_portal_capture']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['canadian_portal_capture'], 'canadian_portal_capture');
            }

            // Handle VAC confirmation (AMARILLO state, Canadian flow)
            if ($isCanadianVisa && in_array($newStatus, [STATUS_EN_ESPERA_PAGO, STATUS_CITA_PROGRAMADA])
                && isset($_FILES['canadian_vac_confirmation']) && $_FILES['canadian_vac_confirmation']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['canadian_vac_confirmation'], 'canadian_vac_confirmation');
            }

            // Handle consular payment evidence (AMARILLO state, standard flow)
            if (!$isCanadianVisa && $newStatus === STATUS_EN_ESPERA_PAGO && isset($_FILES['consular_payment_file']) && $_FILES['consular_payment_file']['error'] === UPLOAD_ERR_OK) {
                $this->saveApplicationFile($id, $_FILES['consular_payment_file'], 'consular_payment_evidence');
            }

            // Handle appointment confirmation and official application (AMARILLO → AZUL, standard flow)
            if (!$isCanadianVisa && ($newStatus === STATUS_CITA_PROGRAMADA || $newStatus === STATUS_EN_ESPERA_PAGO)) {
                if (isset($_FILES['appointment_confirmation_doc']) && $_FILES['appointment_confirmation_doc']['error'] === UPLOAD_ERR_OK) {
                    $this->saveApplicationFile($id, $_FILES['appointment_confirmation_doc'], 'appointment_confirmation');
                }
                if (isset($_FILES['official_application_final']) && $_FILES['official_application_final']['error'] === UPLOAD_ERR_OK) {
                    $this->saveApplicationFile($id, $_FILES['official_application_final'], 'official_application_final');
                }
            }

            // Registrar en historial
            $stmt = $this->db->prepare("
                INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $previousStatus,
                $newStatus,
                $comment,
                $_SESSION['user_id']
            ]);

            // Log audit trail
            logAudit('update', 'solicitudes',
                "Cambio de estatus de solicitud #$id: $previousStatus → $newStatus");

            // Log customer journey
            logCustomerJourney(
                $id,
                'status_change',
                "Cambio de estatus: $newStatus",
                $comment,
                'online'
            );

            logAdminControlEvent(
                'solicitudes',
                'cambio_estatus',
                "Solicitud {$application['folio']} cambio de estatus: $previousStatus -> $newStatus",
                [
                    'application_id' => $id,
                    'folio' => $application['folio'] ?? null,
                    'entity_type' => 'solicitud',
                    'entity_id' => $id,
                    'priority' => in_array($newStatus, [STATUS_CITA_PROGRAMADA, STATUS_TRAMITE_CERRADO, STATUS_RECHAZADO], true) ? 'alta' : 'normal',
                    'metadata' => [
                        'estatus_anterior' => $previousStatus,
                        'estatus_nuevo' => $newStatus,
                        'comentario' => $comment,
                        'cita_tipo' => $notifyAppointmentType,
                        'cita_fecha' => $notifyAppointmentDate
                    ]
                ]
            );

            // Send appointment notification email when a new/changed appointment date is saved
            if ($notifyAppointmentType !== null && $notifyAppointmentDate !== null) {
                try {
                    sendAppointmentNotificationEmail($id, $notifyAppointmentType, $notifyAppointmentDate, false, $this->db);
                } catch (\Exception $e) {
                    error_log("Error sending appointment notification email for application #$id: " . $e->getMessage());
                }
            }
            
            $_SESSION['success'] = 'Estatus actualizado correctamente';
            // Asesor cannot view a closed trámite, redirect them to the list
            if ($role === ROLE_ASESOR && $newStatus === STATUS_TRAMITE_CERRADO) {
                $this->redirect('/solicitudes');
            }
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al cambiar estatus: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar estatus';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function uploadDocument($id) {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        // Verificar que la solicitud existe y el usuario tiene acceso
        $role = $this->getUserRole();

        // REGLA: Asesor tiene acceso de solo lectura a documentos, no puede subir archivos
        if ($role === ROLE_ASESOR) {
            $_SESSION['error'] = 'No tiene permisos para subir documentos';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            // Procesar archivo
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Error al subir el archivo';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $file = $_FILES['document'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmpName = $file['tmp_name'];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Tipo de documento
            $docType = trim($_POST['doc_type'] ?? 'adicional');
            $allowedDocTypes = [
                'pasaporte_vigente', 'visa_anterior', 'ficha_pago_consular',
                'consular_payment_evidence', 'adicional',
                // Canadian visa doc types
                'visa_canadiense_anterior', 'eta_anterior',
                'canadian_vac_confirmation', 'canadian_portal_capture',
            ];
            if (!in_array($docType, $allowedDocTypes)) {
                $docType = 'adicional';
            }
            
            // Validaciones
            if ($fileSize > MAX_FILE_SIZE) {
                $_SESSION['error'] = 'El archivo excede el tamaño máximo permitido (2MB)';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
                $_SESSION['error'] = 'Tipo de archivo no permitido';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Solo puede haber una ficha de pago consular
            if ($docType === 'ficha_pago_consular') {
                $stmtCheck = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'ficha_pago_consular'");
                $stmtCheck->execute([$id]);
                if ($stmtCheck->fetch()) {
                    $_SESSION['error'] = 'Ya existe una ficha de pago consular para esta solicitud';
                    $this->redirect('/solicitudes/ver/' . $id);
                }
            }
            
            // Crear directorio si no existe
            $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $id;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generar nombre único
            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
            $filePath = $uploadDir . '/' . $newFileName;
            
            if (!move_uploaded_file($fileTmpName, $filePath)) {
                $_SESSION['error'] = 'Error al guardar el archivo';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Guardar en base de datos (con doc_type si la columna existe)
            $relativePath = '/uploads/applications/' . $id . '/' . $newFileName;
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $fileName,
                    $docType,
                    $relativePath,
                    $fileType,
                    $fileSize,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $e) {
                // Fallback si la columna doc_type aún no existe
                error_log('doc_type column missing, using fallback: ' . $e->getMessage());
                $stmt = $this->db->prepare("
                    INSERT INTO documents (application_id, name, file_path, file_type, file_size, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $fileName,
                    $relativePath,
                    $fileType,
                    $fileSize,
                    $_SESSION['user_id']
                ]);
            }

            logAdminControlEvent(
                'documentos',
                'subir',
                "Documento subido a solicitud {$application['folio']}: $fileName",
                [
                    'application_id' => $id,
                    'folio' => $application['folio'] ?? null,
                    'entity_type' => 'documento',
                    'priority' => in_array($docType, ['pasaporte_vigente', 'visa_anterior', 'visa_canadiense_anterior', 'eta_anterior'], true) ? 'alta' : 'normal',
                    'metadata' => [
                        'documento' => $fileName,
                        'tipo_documento' => $docType,
                        'extension' => $fileType,
                        'tamano' => $fileSize
                    ]
                ]
            );
            
            // Auto-advance to ROJO when a base doc is uploaded and all conditions are met
            $canadianBaseDocTypes = ['pasaporte_vigente', 'visa_canadiense_anterior', 'eta_anterior'];
            $standardBaseDocTypes = ['pasaporte_vigente', 'visa_anterior'];
            if (in_array($docType, array_merge($standardBaseDocTypes, $canadianBaseDocTypes))) {
                $stmtApp2 = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
                $stmtApp2->execute([$id]);
                $currentApp2 = $stmtApp2->fetch();
                if ($currentApp2 && $currentApp2['status'] === STATUS_NUEVO) {
                    $isCanadianVisa2 = !empty($currentApp2['is_canadian_visa']);

                    if ($isCanadianVisa2) {
                        // Canadian visa auto-advance conditions
                        $stmtSheet2 = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                        $stmtSheet2->execute([$id]);
                        $hasInfoSheet2 = $stmtSheet2->fetch();

                        // form_link_status: required 'completado' if a form is assigned; optional only if no form assigned at all
                        $formOk = ($currentApp2['form_link_status'] === 'completado' || (empty($currentApp2['form_link_id']) && empty($currentApp2['form_id'])));

                        if ($hasInfoSheet2 && $formOk) {
                            $stmtDoc2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                            $stmtDoc2->execute([$id]);
                            $hasPasaporte2 = (bool) $stmtDoc2->fetch();

                            $isRenovacion2 = stripos($currentApp2['canadian_modalidad'] ?? '', 'renov') !== false;
                            $isETA2        = stripos($currentApp2['canadian_tipo'] ?? '', 'ETA') !== false;

                            $hasVisaCanadiensPrev2 = true;
                            if ($isRenovacion2) {
                                $stmtVC = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_canadiense_anterior'");
                                $stmtVC->execute([$id]);
                                $hasVisaCanadiensPrev2 = (bool) $stmtVC->fetch();
                            }

                            $hasEtaAnterior2 = true;
                            if ($isETA2 && $isRenovacion2) {
                                $stmtEta2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'eta_anterior'");
                                $stmtEta2->execute([$id]);
                                $hasEtaAnterior2 = (bool) $stmtEta2->fetch();
                            }

                            if ($hasPasaporte2 && $hasVisaCanadiensPrev2 && $hasEtaAnterior2) {
                                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_LISTO_SOLICITUD, $id]);
                                $this->db->prepare("
                                    INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                    VALUES (?, ?, ?, ?, ?)
                                ")->execute([$id, STATUS_NUEVO, STATUS_LISTO_SOLICITUD, 'Cambio automático: documentos base e hoja de información completos (Visa Canadiense)', $_SESSION['user_id']]);
                            }
                        }
                    } else {
                        // Standard flow auto-advance
                        if ($currentApp2['form_link_status'] === 'completado') {
                            $stmtSheet2 = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
                            $stmtSheet2->execute([$id]);
                            $hasInfoSheet2 = $stmtSheet2->fetch();
                            if ($hasInfoSheet2) {
                                $stmtDoc2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'pasaporte_vigente'");
                                $stmtDoc2->execute([$id]);
                                $hasPasaporte2 = (bool) $stmtDoc2->fetch();

                                $isRenovacion2 = stripos($currentApp2['subtype'] ?? '', 'renov') !== false;
                                $hasVisaAnterior2 = true;
                                if ($isRenovacion2) {
                                    $stmtVisa2 = $this->db->prepare("SELECT id FROM documents WHERE application_id = ? AND doc_type = 'visa_anterior'");
                                    $stmtVisa2->execute([$id]);
                                    $hasVisaAnterior2 = (bool) $stmtVisa2->fetch();
                                }

                                if ($hasPasaporte2 && $hasVisaAnterior2) {
                                    $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_VALIDANDO_RESPUESTAS, $id]);
                                    $this->db->prepare("
                                        INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                                        VALUES (?, ?, ?, ?, ?)
                                    ")->execute([$id, STATUS_NUEVO, STATUS_VALIDANDO_RESPUESTAS, 'Cambio automático: documentos base, cuestionario y hoja de información completos', $_SESSION['user_id']]);
                                }
                            }
                        }
                    }
                }
            }

            $_SESSION['success'] = 'Documento subido correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al subir documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al subir documento';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function addNote($id) {
        // Solo Admin y Gerente pueden agregar indicaciones
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        $noteText = trim($_POST['note_text'] ?? '');
        $isImportant = isset($_POST['is_important']) ? 1 : 0;
        
        if (empty($noteText)) {
            $_SESSION['error'] = 'La indicación no puede estar vacía';
            $this->redirect('/solicitudes/ver/' . $id);
        }
        
        try {
            // Verificar que la solicitud existe
            $stmt = $this->db->prepare("SELECT id FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // Insertar indicación
            $stmt = $this->db->prepare("
                INSERT INTO application_notes (application_id, note_text, is_important, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $noteText,
                $isImportant,
                $_SESSION['user_id']
            ]);
            
            // Log audit trail
            logAudit('create', 'solicitudes', 
                "Indicación agregada a solicitud #$id" . ($isImportant ? ' (Importante)' : ''));
            
            logAdminControlEvent(
                'solicitudes',
                'indicacion',
                "Indicacion agregada a solicitud #$id" . ($isImportant ? ' (importante)' : ''),
                [
                    'application_id' => $id,
                    'entity_type' => 'indicacion',
                    'priority' => $isImportant ? 'alta' : 'normal',
                    'metadata' => [
                        'importante' => (bool)$isImportant,
                        'texto' => function_exists('mb_substr') ? mb_substr($noteText, 0, 500) : substr($noteText, 0, 500)
                    ]
                ]
            );

            $_SESSION['success'] = 'Indicación agregada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);
            
        } catch (PDOException $e) {
            error_log("Error al agregar indicación: " . $e->getMessage());
            $_SESSION['error'] = 'Error al agregar indicación';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }
    
    public function downloadFormFile($id, $fieldId) {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        
        try {
            // Obtener solicitud
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }
            
            // REGLA: Asesor no puede acceder a solicitudes finalizadas ni rechazadas
            if ($role === ROLE_ASESOR && ($application['status'] === STATUS_FINALIZADO || $application['status'] === STATUS_RECHAZADO)) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }
            
            // Solo Admin y Gerente pueden descargar archivos
            if (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE])) {
                $_SESSION['error'] = 'No tiene permisos para descargar archivos';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Obtener datos del formulario
            $formData = json_decode($application['data_json'], true);
            $formFields = json_decode($application['fields_json'], true);
            
            // Verificar que el campo existe y es de tipo file
            $isFileField = false;
            if ($formFields && isset($formFields['fields'])) {
                foreach ($formFields['fields'] as $field) {
                    if ($field['id'] === $fieldId && $field['type'] === 'file') {
                        $isFileField = true;
                        break;
                    }
                }
            }
            
            if (!$isFileField) {
                $_SESSION['error'] = 'Campo no válido';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            if (!isset($formData[$fieldId]) || empty($formData[$fieldId])) {
                $_SESSION['error'] = 'Archivo no encontrado en los datos';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $fileName = $formData[$fieldId];
            
            // Buscar el archivo en la tabla de documents
            $stmt = $this->db->prepare("
                SELECT * FROM documents 
                WHERE application_id = ? AND (name LIKE ? OR file_path LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$id, "%$fileName%", "%$fileName%"]);
            $document = $stmt->fetch();
            
            if (!$document) {
                $_SESSION['error'] = 'El documento no se encuentra registrado';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            $filePath = ROOT_PATH . '/public' . $document['file_path'];
            
            if (!file_exists($filePath)) {
                $_SESSION['error'] = 'El archivo no existe en el servidor';
                $this->redirect('/solicitudes/ver/' . $id);
            }
            
            // Log audit trail
            logAudit('download', 'solicitudes', 
                "Descarga de archivo '$fileName' de solicitud #$id (campo: $fieldId)");
            
            // Descargar archivo
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
            
        } catch (PDOException $e) {
            error_log("Error al descargar archivo: " . $e->getMessage());
            $_SESSION['error'] = 'Error al descargar archivo';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function saveInfoSheet($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $this->verifyInfoSheetCsrf($id);

        $role = $this->getUserRole();
        $saleLockName = 'crmvisas_income_sale_folio_' . date('Y');
        $saleLock = false;
        $newProofPaths = [];

        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.full_name AS advisor_name
                FROM applications a
                LEFT JOIN users u ON u.id=a.created_by
                WHERE a.id=? LIMIT 1
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && (int)$application['created_by'] !== (int)$_SESSION['user_id']) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $entryDate      = trim((string)($_POST['entry_date'] ?? date('Y-m-d')));
            $residencePlace = trim((string)($_POST['residence_place'] ?? ''));
            $address        = trim((string)($_POST['address'] ?? ''));
            $clientEmail    = trim((string)($_POST['client_email'] ?? ''));
            $embassyEmail   = trim((string)($_POST['embassy_email'] ?? ''));
            $observations   = trim((string)($_POST['observations'] ?? ''));

            $quantity       = max(1, (int)($_POST['service_quantity'] ?? 1));
            $cashAmount     = max(0, parseCurrencyInput($_POST['payment_cash'] ?? 0));
            $terminalAmount = max(0, parseCurrencyInput($_POST['payment_terminal'] ?? 0));
            $transferAmount = max(0, parseCurrencyInput($_POST['payment_transfer'] ?? 0));
            $paymentReference = '';

            if (!$this->validSimpleDate($entryDate) || $entryDate > date('Y-m-d')) {
                throw new RuntimeException('La fecha de ingreso no es válida.');
            }
            if ($clientEmail !== '' && !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El correo del solicitante no es válido.');
            }

            // El servicio y su precio se obtienen del formulario/solicitud.
            $service = $this->resolveApplicationFinancialService($application);
            $catalogId = !empty($service['catalog_id']) ? (int)$service['catalog_id'] : null;
            $catalogCode = (string)$service['catalog_code'];
            $serviceName = (string)$service['service_name'];
            $unitPrice = (float)$service['unit_price'];

            if ($unitPrice <= 0) {
                throw new RuntimeException(
                    'El trámite de esta solicitud no tiene un precio configurado. '
                    .'Actualice el costo del formulario o el catálogo antes de guardar la Hoja de Información.'
                );
            }

            $totalAmount = round($unitPrice * $quantity, 2);

            $initialPaid = $cashAmount + $terminalAmount + $transferAmount;
            if ($initialPaid - $totalAmount > 0.009) {
                throw new RuntimeException('La suma de Efectivo, Terminal y Transferencia no puede superar el total automático del trámite.');
            }
            if ($initialPaid > 0 && !financialCanWriteDate($entryDate, $this->db)) {
                throw new RuntimeException('La semana de este pago ya está cerrada.');
            }

            // Recuperar Hoja/Venta y comprobantes anteriores antes de reescribir
            // una venta que todavía está pendiente.
            $stmt = $this->db->prepare("SELECT * FROM information_sheets WHERE application_id=? LIMIT 1");
            $stmt->execute([$id]);
            $existingSheet = $stmt->fetch() ?: null;

            $existingSale = null;
            $existingProofs = [];
            if ($existingSheet && !empty($existingSheet['financial_sale_id'])) {
                $stmt = $this->db->prepare("SELECT * FROM income_sales WHERE id=? LIMIT 1");
                $stmt->execute([(int)$existingSheet['financial_sale_id']]);
                $existingSale = $stmt->fetch() ?: null;

                if ($existingSale) {
                    $stmt = $this->db->prepare("
                        SELECT payment_method,proof_path
                        FROM income_payments
                        WHERE sale_id=? AND source_type='INFO_SHEET'
                          AND validation_status IN ('PENDIENTE','RECHAZADO')
                        ORDER BY id DESC
                    ");
                    $stmt->execute([(int)$existingSale['id']]);
                    foreach ($stmt->fetchAll() as $row) {
                        $method = financialNormalizePaymentMethod($row['payment_method']);
                        if (!isset($existingProofs[$method]) && !empty($row['proof_path'])) {
                            $existingProofs[$method] = $row['proof_path'];
                        }
                    }
                }
            }

            if ($existingSale && $existingSale['approval_status'] === 'VALIDADA') {
                $postedChanged =
                    (int)$existingSheet['service_quantity'] !== $quantity ||
                    abs((float)$existingSheet['service_unit_price'] - $unitPrice) > 0.009 ||
                    abs((float)$existingSheet['service_total_amount'] - $totalAmount) > 0.009 ||
                    abs((float)$existingSheet['payment_cash'] - $cashAmount) > 0.009 ||
                    abs((float)$existingSheet['payment_terminal'] - $terminalAmount) > 0.009 ||
                    abs((float)$existingSheet['payment_transfer'] - $transferAmount) > 0.009;

                if ($postedChanged) {
                    throw new RuntimeException(
                        'La venta de esta Hoja de Información ya fue aprobada. '
                        .'Para conservar la auditoría no se pueden cambiar cantidad, total ni formas de pago validadas.'
                    );
                }
            }

            $proofByMethod = [
                'EFECTIVO' => $existingProofs['EFECTIVO'] ?? null,
                'TERMINAL_TARJETA' => $existingProofs['TERMINAL_TARJETA'] ?? null,
                'TRANSFERENCIA' => $existingProofs['TRANSFERENCIA'] ?? null,
            ];

            if (!$existingSale || $existingSale['approval_status'] !== 'VALIDADA') {
                $proofFields = [
                    'EFECTIVO' => ['payment_cash_proof','efectivo'],
                    'TERMINAL_TARJETA' => ['payment_terminal_proof','terminal'],
                    'TRANSFERENCIA' => ['payment_transfer_proof','transferencia'],
                ];
                foreach ($proofFields as $method => [$field,$tag]) {
                    if (!empty($_FILES[$field]['name'])) {
                        $path = $this->storeInfoSheetPaymentProof($_FILES[$field], $tag);
                        $proofByMethod[$method] = $path;
                        $newProofPaths[] = $path;
                    }
                }
            }

            $this->db->beginTransaction();

            $paymentMethodSummary = null;
            $usedMethods = 0;
            if ($cashAmount > 0) $usedMethods++;
            if ($terminalAmount > 0) $usedMethods++;
            if ($transferAmount > 0) $usedMethods++;
            if ($usedMethods === 1) {
                if ($cashAmount > 0) $paymentMethodSummary = 'EFECTIVO';
                elseif ($terminalAmount > 0) $paymentMethodSummary = 'TERMINAL_TARJETA';
                else $paymentMethodSummary = 'TRANSFERENCIA';
            } elseif ($usedMethods > 1) {
                $paymentMethodSummary = 'MIXTO';
            }

            // DHL ya existía históricamente en la tabla. V5.1 no lo muestra
            // en la captura principal y, si ya tenía valor, lo conserva.
            $stmt = $this->db->prepare("
                INSERT INTO information_sheets
                  (application_id,entry_date,residence_place,address,client_email,embassy_email,
                   service_catalog_id,service_name,service_quantity,service_unit_price,service_total_amount,
                   amount_paid,payment_cash,payment_terminal,payment_transfer,payment_method,payment_reference,
                   cash_envelope_id,observations,created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULL,?,?)
                ON DUPLICATE KEY UPDATE
                  entry_date=VALUES(entry_date),
                  residence_place=VALUES(residence_place),
                  address=VALUES(address),
                  client_email=VALUES(client_email),
                  embassy_email=VALUES(embassy_email),
                  service_catalog_id=VALUES(service_catalog_id),
                  service_name=VALUES(service_name),
                  service_quantity=VALUES(service_quantity),
                  service_unit_price=VALUES(service_unit_price),
                  service_total_amount=VALUES(service_total_amount),
                  amount_paid=VALUES(amount_paid),
                  payment_cash=VALUES(payment_cash),
                  payment_terminal=VALUES(payment_terminal),
                  payment_transfer=VALUES(payment_transfer),
                  payment_method=VALUES(payment_method),
                  payment_reference=VALUES(payment_reference),
                  cash_envelope_id=NULL,
                  observations=VALUES(observations),
                  updated_at=NOW()
            ");
            $stmt->execute([
                $id,$entryDate,$residencePlace,$address,$clientEmail,$embassyEmail,
                $catalogId,$serviceName,$quantity,$unitPrice,$totalAmount,
                $initialPaid,$cashAmount,$terminalAmount,$transferAmount,$paymentMethodSummary,
                $paymentReference !== '' ? $paymentReference : null,
                $observations,$_SESSION['user_id']
            ]);

            $stmt = $this->db->prepare("SELECT * FROM information_sheets WHERE application_id=? LIMIT 1");
            $stmt->execute([$id]);
            $sheet = $stmt->fetch();
            $sheetId = (int)$sheet['id'];

            $saleId = (int)($sheet['financial_sale_id'] ?? 0);
            $sale = null;
            if ($saleId > 0) {
                $stmt = $this->db->prepare("SELECT * FROM income_sales WHERE id=? LIMIT 1 FOR UPDATE");
                $stmt->execute([$saleId]);
                $sale = $stmt->fetch() ?: null;
            }

            if (!$sale) {
                $stmt = $this->db->prepare("SELECT GET_LOCK(?,8)");
                $stmt->execute([$saleLockName]);
                $saleLock = (int)$stmt->fetchColumn() === 1;
                if (!$saleLock) throw new RuntimeException('No se pudo reservar el folio interno de la venta.');

                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(sale_folio,-6) AS UNSIGNED))
                    FROM income_sales WHERE sale_folio LIKE ?
                ");
                $stmt->execute(["VTA-$year-%"]);
                $last = (int)($stmt->fetchColumn() ?: 0);
                $saleFolio = sprintf('VTA-%s-%06d',$year,$last+1);

                $stmt = $this->db->prepare("
                    INSERT INTO income_sales
                      (sale_folio,application_id,quick_client_id,catalog_id,catalog_code,sale_origin,information_sheet_id,
                       client_name_snapshot,client_folio_snapshot,service_name_snapshot,advisor_id,quantity,unit_price,
                       total_amount,contract_date,financial_status,approval_status,notes,created_by)
                    VALUES (?, ?, NULL, ?, ?, 'HOJA_INFORMACION', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE','PENDIENTE',?,?)
                ");
                $stmt->execute([
                    $saleFolio,$id,$catalogId,$catalogCode,$sheetId,
                    $application['client_name'],$application['folio'],$serviceName,
                    (int)$application['created_by'],$quantity,$unitPrice,$totalAmount,$entryDate,
                    $observations !== '' ? $observations : null,$_SESSION['user_id']
                ]);
                $saleId = (int)$this->db->lastInsertId();

                $this->db->prepare("UPDATE information_sheets SET financial_sale_id=? WHERE id=?")
                    ->execute([$saleId,$sheetId]);
            } elseif ($sale['approval_status'] !== 'VALIDADA') {
                $this->db->prepare("
                    UPDATE income_sales SET
                      catalog_id=?,catalog_code=?,client_name_snapshot=?,client_folio_snapshot=?,
                      service_name_snapshot=?,advisor_id=?,quantity=?,unit_price=?,total_amount=?,
                      contract_date=?,financial_status='PENDIENTE',approval_status='PENDIENTE',
                      rejection_reason=NULL,approved_by=NULL,approved_at=NULL,notes=?,updated_at=NOW()
                    WHERE id=?
                ")->execute([
                    $catalogId,$catalogCode,$application['client_name'],$application['folio'],
                    $serviceName,(int)$application['created_by'],$quantity,$unitPrice,$totalAmount,
                    $entryDate,$observations !== '' ? $observations : null,$saleId
                ]);

                $this->db->prepare("
                    DELETE FROM income_payments
                    WHERE sale_id=? AND source_type='INFO_SHEET'
                      AND validation_status IN ('PENDIENTE','RECHAZADO')
                ")->execute([$saleId]);
            }

            if (!$sale || $sale['approval_status'] !== 'VALIDADA') {
                $parts = [
                    ['method'=>'EFECTIVO','amount'=>$cashAmount],
                    ['method'=>'TERMINAL_TARJETA','amount'=>$terminalAmount],
                    ['method'=>'TRANSFERENCIA','amount'=>$transferAmount],
                ];
                $conceptCode = abs($initialPaid-$totalAmount)<=0.009 ? 'COB-004' : 'COB-001';

                $stmtPay = $this->db->prepare("
                    INSERT INTO income_payments
                      (sale_id,application_id,quick_client_id,payment_concept_code,amount,payment_method,
                       receiver_account,reference,proof_path,notes,payment_date,validation_status,registered_by,source_type,source_id)
                    VALUES (?, ?, NULL, ?, ?, ?, NULL, ?, ?, ?, ?, 'PENDIENTE', ?, 'INFO_SHEET', ?)
                ");
                foreach ($parts as $part) {
                    if ($part['amount'] <= 0) continue;
                    $stmtPay->execute([
                        $saleId,$id,$conceptCode,$part['amount'],$part['method'],
                        $paymentReference !== '' ? $paymentReference : null,
                        $proofByMethod[$part['method']] ?? null,
                        $observations !== '' ? $observations : null,
                        $entryDate,$_SESSION['user_id'],$sheetId
                    ]);
                }
            }

            incomeRefreshSaleStatus($this->db,$saleId);
            $this->db->commit();

            if ($saleLock) {
                try {
                    $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
                    $stmt->execute([$saleLockName]);
                } catch (Throwable $ignore) {}
                $saleLock = false;
            }

            $this->advanceAfterInfoSheet($id);

            logAudit('create','solicitudes',"Hoja de información guardada para solicitud #$id");
            logAdminControlEvent('solicitudes','hoja_info',"Hoja de información y venta registradas para solicitud #$id",[
                'application_id'=>$id,'entity_type'=>'hoja_informacion','entity_id'=>$sheetId,'priority'=>'alta',
                'metadata'=>[
                    'folio'=>$application['folio'],'servicio'=>$serviceName,'cantidad'=>$quantity,
                    'precio_unitario'=>$unitPrice,'total'=>$totalAmount,
                    'efectivo'=>$cashAmount,'terminal'=>$terminalAmount,'transferencia'=>$transferAmount,
                    'venta_id'=>$saleId,'estado_venta'=>'PENDIENTE'
                ]
            ]);

            $_SESSION['success']='Hoja de Información guardada. La venta se calculó automáticamente, quedó pendiente de aprobación y se generó la Nota de Remisión.';
            $this->redirect('/solicitudes/nota-remision/' . $id . '?sale=' . $saleId . '&print=1');

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($saleLock) {
                try {
                    $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
                    $stmt->execute([$saleLockName]);
                } catch (Throwable $ignore) {}
            }
            foreach ($newProofPaths as $path) {
                if ($path && is_file(ROOT_PATH.'/'.ltrim($path,'/'))) {
                    @unlink(ROOT_PATH.'/'.ltrim($path,'/'));
                }
            }
            error_log('Error al guardar Hoja de Información V5.1: '.$e->getMessage());
            $_SESSION['error']=$e->getMessage();
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function remission($id) {
        $this->requireRole([ROLE_ASESOR,ROLE_ADMIN,ROLE_GERENTE]);
        $role=$this->getUserRole();

        try {
            $stmt=$this->db->prepare("
                SELECT a.*,u.full_name advisor_name
                FROM applications a
                LEFT JOIN users u ON u.id=a.created_by
                WHERE a.id=? LIMIT 1
            ");
            $stmt->execute([(int)$id]);
            $application=$stmt->fetch();
            if(!$application)throw new RuntimeException('Solicitud no encontrada.');

            if($role===ROLE_ASESOR && (int)$application['created_by']!==(int)$_SESSION['user_id']){
                throw new RuntimeException('No tiene permisos para esta solicitud.');
            }

            $stmt=$this->db->prepare("SELECT * FROM information_sheets WHERE application_id=? LIMIT 1");
            $stmt->execute([(int)$id]);
            $infoSheet=$stmt->fetch();
            if(!$infoSheet)throw new RuntimeException('La solicitud todavía no tiene Hoja de Información.');

            $saleId=(int)($_GET['sale']??$infoSheet['financial_sale_id']??0);
            $stmt=$this->db->prepare("
                SELECT * FROM income_sales
                WHERE id=? AND application_id=? LIMIT 1
            ");
            $stmt->execute([$saleId,(int)$id]);
            $sale=$stmt->fetch();
            if(!$sale)throw new RuntimeException('No se encontró la venta asociada a la Hoja de Información.');

            // La Nota de Remisión emitida desde la Hoja de Información refleja
            // el pago inicial reportado en esa hoja. Los abonos posteriores
            // permanecen en el historial financiero y no reescriben esta nota.
            $stmt=$this->db->prepare("
                SELECT * FROM income_payments
                WHERE sale_id=? AND source_type='INFO_SHEET'
                  AND validation_status IN ('PENDIENTE','VALIDADO')
                ORDER BY id
            ");
            $stmt->execute([$saleId]);
            $payments=$stmt->fetchAll();

            $paymentBreakdown=[
                'EFECTIVO'=>(float)($infoSheet['payment_cash']??0),
                'TERMINAL_TARJETA'=>(float)($infoSheet['payment_terminal']??0),
                'TRANSFERENCIA'=>(float)($infoSheet['payment_transfer']??0)
            ];
            $received=array_sum($paymentBreakdown);
            $pending=max(0,(float)$sale['total_amount']-$received);
            $clientData=json_decode((string)$application['data_json'],true);
            if(!is_array($clientData))$clientData=[];

            $autoPrint=(string)($_GET['print']??'')==='1';

            $this->view('applications/remission',compact(
                'application','infoSheet','sale','payments','paymentBreakdown','received','pending',
                'clientData','autoPrint'
            ));
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
            $this->redirect('/solicitudes/ver/'.(int)$id);
        }
    }

    private function resolveApplicationFinancialService(array $application): array {
        $form = null;
        if (!empty($application['form_id'])) {
            $stmt = $this->db->prepare("
                SELECT id,name,type,subtype,cost
                FROM forms
                WHERE id=?
                LIMIT 1
            ");
            $stmt->execute([(int)$application['form_id']]);
            $form = $stmt->fetch() ?: null;
        }

        $serviceName = trim((string)($form['name'] ?? ''));
        if ($serviceName === '') {
            $serviceName = trim(
                (string)($application['type'] ?? '')
                .' '
                .(string)($application['subtype'] ?? '')
            );
        }

        $unitPrice = $form ? (float)($form['cost'] ?? 0) : 0.0;

        $raw = implode(' ', [
            (string)($application['type'] ?? ''),
            (string)($application['subtype'] ?? ''),
            (string)($application['canadian_tipo'] ?? ''),
            (string)($application['canadian_modalidad'] ?? ''),
            $serviceName,
        ]);
        $normalized = mb_strtoupper($raw, 'UTF-8');
        $normalized = strtr($normalized, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'
        ]);

        $catalogCode = null;

        if (strpos($normalized, 'PASAPORTE') !== false) {
            if (strpos($normalized, 'MEXIC') !== false) {
                $catalogCode = strpos($normalized, 'RENOV') !== false ? 'ING-026' : 'ING-025';
            } elseif (strpos($normalized, 'AMERICAN') !== false || strpos($normalized, 'ESTADOUNID') !== false) {
                $catalogCode = strpos($normalized, 'RENOV') !== false ? 'ING-028' : 'ING-027';
            }
        } elseif (strpos($normalized, 'ETA') !== false && strpos($normalized, 'CANAD') !== false) {
            $catalogCode = 'ING-018';
        } elseif (strpos($normalized, 'CANAD') !== false) {
            $catalogCode = 'ING-017';
        } elseif (strpos($normalized, 'CHINA') !== false) {
            $catalogCode = 'ING-019';
        } elseif (strpos($normalized, 'AUSTRALIA') !== false) {
            $catalogCode = 'ING-020';
        } elseif (strpos($normalized, 'VISA') !== false || strpos($normalized, 'AMERICANA') !== false) {
            $isRenewal = strpos($normalized, 'RENOV') !== false;
            $withTransport = strpos($normalized, 'CON TRASLADO') !== false;
            $withoutTransport = strpos($normalized, 'SIN TRASLADO') !== false;

            if ($isRenewal && $withTransport) $catalogCode = 'ING-004';
            elseif ($isRenewal && $withoutTransport) $catalogCode = 'ING-002';
            elseif (!$isRenewal && $withTransport) $catalogCode = 'ING-003';
            elseif (!$isRenewal && $withoutTransport) $catalogCode = 'ING-001';
        }

        $catalog = null;
        if ($catalogCode !== null) {
            $stmt = $this->db->prepare("
                SELECT id,code,concept,authorized_price,price_mode
                FROM income_catalog
                WHERE code=?
                LIMIT 1
            ");
            $stmt->execute([$catalogCode]);
            $catalog = $stmt->fetch() ?: null;
        }

        // El costo del formulario es la primera fuente porque es el servicio
        // seleccionado en la Solicitud. Para formularios con costo 0 (ej. Canadá),
        // se usa el precio autorizado del catálogo como respaldo.
        if ($unitPrice <= 0 && $catalog && (float)($catalog['authorized_price'] ?? 0) > 0) {
            $unitPrice = (float)$catalog['authorized_price'];
        }

        if ($serviceName === '' && $catalog) {
            $serviceName = (string)$catalog['concept'];
        }

        $fallbackCode = !empty($application['form_id'])
            ? 'FORM-'.(int)$application['form_id']
            : 'SOL-'.(int)($application['id'] ?? 0);

        return [
            'catalog_id' => $catalog ? (int)$catalog['id'] : null,
            'catalog_code' => $catalog ? (string)$catalog['code'] : $fallbackCode,
            'service_name' => $serviceName,
            'unit_price' => round($unitPrice, 2),
            'form_id' => $form ? (int)$form['id'] : null,
        ];
    }

    private function storeInfoSheetPaymentProof(array $file, string $tag): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo adjuntar el comprobante de '.$tag.'.');
        }
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException('El comprobante de '.$tag.' no puede superar 10 MB.');
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'], true)) {
            throw new RuntimeException('El comprobante de '.$tag.' debe ser JPG, PNG, WEBP o PDF.');
        }

        $dirRel = 'storage/finance/payment-proofs/'.date('Y/m');
        $dir = ROOT_PATH.'/'.$dirRel;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No fue posible preparar la carpeta de comprobantes.');
        }

        $name = 'hoja-'.$tag.'-'.date('YmdHis').'-'.bin2hex(random_bytes(5)).'.'.$ext;
        if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) {
            throw new RuntimeException('No fue posible guardar el comprobante de '.$tag.'.');
        }
        return $dirRel.'/'.$name;
    }

    private function advanceAfterInfoSheet($id): void {
        try {
            $stmtApp=$this->db->prepare("SELECT * FROM applications WHERE id=?");
            $stmtApp->execute([$id]);
            $currentApp=$stmtApp->fetch();
            if(!$currentApp || $currentApp['status']!==STATUS_NUEVO)return;

            $isCanadianVisa=!empty($currentApp['is_canadian_visa']);
            if($isCanadianVisa){
                $formOk=($currentApp['form_link_status']==='completado' || (empty($currentApp['form_link_id']) && empty($currentApp['form_id'])));
                if(!$formOk)return;

                $stmtDoc=$this->db->prepare("SELECT COUNT(*) FROM documents WHERE application_id=? AND doc_type='pasaporte_vigente'");
                $stmtDoc->execute([$id]);
                $hasPassport=(int)$stmtDoc->fetchColumn()>0;
                if(!$hasPassport)return;

                $isRenovacion=stripos($currentApp['canadian_modalidad']??'','renov')!==false;
                if($isRenovacion){
                    $stmt=$this->db->prepare("SELECT COUNT(*) FROM documents WHERE application_id=? AND doc_type='visa_canadiense_anterior'");
                    $stmt->execute([$id]);
                    if((int)$stmt->fetchColumn()===0)return;
                }

                $isETA=stripos($currentApp['canadian_tipo']??'','ETA')!==false;
                if($isETA && $isRenovacion){
                    $stmt=$this->db->prepare("SELECT COUNT(*) FROM documents WHERE application_id=? AND doc_type='eta_anterior'");
                    $stmt->execute([$id]);
                    if((int)$stmt->fetchColumn()===0)return;
                }

                $this->db->prepare("UPDATE applications SET status=? WHERE id=?")->execute([STATUS_LISTO_SOLICITUD,$id]);
                $this->db->prepare("
                    INSERT INTO status_history(application_id,previous_status,new_status,comment,changed_by)
                    VALUES(?,?,?,?,?)
                ")->execute([$id,STATUS_NUEVO,STATUS_LISTO_SOLICITUD,'Cambio automático: Hoja de Información y documentos base completos',$_SESSION['user_id']]);
            } elseif($currentApp['form_link_status']==='completado'){
                $stmt=$this->db->prepare("SELECT COUNT(*) FROM documents WHERE application_id=? AND doc_type='pasaporte_vigente'");
                $stmt->execute([$id]);
                $hasPassport=(int)$stmt->fetchColumn()>0;
                if(!$hasPassport)return;

                $isRenovacion=stripos($currentApp['subtype']??'','renov')!==false;
                if($isRenovacion){
                    $stmt=$this->db->prepare("SELECT COUNT(*) FROM documents WHERE application_id=? AND doc_type='visa_anterior'");
                    $stmt->execute([$id]);
                    if((int)$stmt->fetchColumn()===0)return;
                }

                $this->db->prepare("UPDATE applications SET status=? WHERE id=?")->execute([STATUS_VALIDANDO_RESPUESTAS,$id]);
                $this->db->prepare("
                    INSERT INTO status_history(application_id,previous_status,new_status,comment,changed_by)
                    VALUES(?,?,?,?,?)
                ")->execute([$id,STATUS_NUEVO,STATUS_VALIDANDO_RESPUESTAS,'Cambio automático: Hoja de Información, cuestionario y documentos base completos',$_SESSION['user_id']]);
            }
        } catch(Throwable $e) {
            error_log('No se pudo avanzar estatus tras Hoja de Información V5: '.$e->getMessage());
        }
    }

    private function infoSheetCsrfToken(): string {
        if (empty($_SESSION['info_sheet_csrf'])) {
            $_SESSION['info_sheet_csrf'] = bin2hex(random_bytes(24));
        }
        return (string)$_SESSION['info_sheet_csrf'];
    }

    private function verifyInfoSheetCsrf($applicationId): void {
        $posted = (string)($_POST['csrf_token'] ?? '');
        $known = (string)($_SESSION['info_sheet_csrf'] ?? '');
        if ($known === '' || $posted === '' || !hash_equals($known, $posted)) {
            $_SESSION['error'] = 'La sesión de la Hoja de Información expiró. Vuelva a abrirla.';
            $this->redirect('/solicitudes/ver/' . (int)$applicationId);
        }
    }

    private function validSimpleDate(string $date): bool {
        $d=DateTime::createFromFormat('Y-m-d',$date);
        return $d && $d->format('Y-m-d')===$date;
    }

    public function saveFamiliar($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            // Ensure the parent information_sheet exists
            $stmtSheet = $this->db->prepare("SELECT id FROM information_sheets WHERE application_id = ?");
            $stmtSheet->execute([$id]);
            $sheet = $stmtSheet->fetch();

            if (!$sheet) {
                $_SESSION['error'] = 'Primero debe guardar la hoja de información Individual';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $sheetId           = $sheet['id'];
            $familiarId        = intval($_POST['familiar_id'] ?? 0);
            $entryDate         = trim($_POST['fam_entry_date'] ?? '') ?: null;
            $nombreCompleto    = trim($_POST['fam_nombre_completo'] ?? '');
            $parentesco        = trim($_POST['fam_parentesco'] ?? '');
            $fechaNacimiento   = trim($_POST['fam_fecha_nacimiento'] ?? '') ?: null;
            $pasaporte         = trim($_POST['fam_pasaporte'] ?? '');
            $residencePlace    = trim($_POST['fam_residence_place'] ?? '');
            $address           = trim($_POST['fam_address'] ?? '');
            $clientEmail       = trim($_POST['fam_client_email'] ?? '');
            $embassyEmail      = trim($_POST['fam_embassy_email'] ?? '');
            $amountPaid        = !empty($_POST['fam_amount_paid']) ? parseCurrencyInput($_POST['fam_amount_paid']) : null;
            $dhl               = trim($_POST['fam_dhl'] ?? '');
            $observations      = trim($_POST['fam_observations'] ?? '');

            if ($familiarId > 0) {
                // Update existing
                $stmtUpd = $this->db->prepare("
                    UPDATE information_sheet_familiar SET
                        entry_date = ?, nombre_completo = ?, parentesco = ?, fecha_nacimiento = ?,
                        pasaporte = ?, residence_place = ?, address = ?, client_email = ?,
                        embassy_email = ?, amount_paid = ?, dhl = ?, observations = ?
                    WHERE id = ? AND information_sheet_id = ?
                ");
                $stmtUpd->execute([
                    $entryDate, $nombreCompleto, $parentesco, $fechaNacimiento,
                    $pasaporte, $residencePlace, $address, $clientEmail,
                    $embassyEmail, $amountPaid, $dhl ?: null, $observations,
                    $familiarId, $sheetId
                ]);
            } else {
                // Insert new
                $stmtIns = $this->db->prepare("
                    INSERT INTO information_sheet_familiar
                        (information_sheet_id, entry_date, nombre_completo, parentesco, fecha_nacimiento,
                         pasaporte, residence_place, address, client_email, embassy_email,
                         amount_paid, dhl, observations, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $sheetId, $entryDate, $nombreCompleto, $parentesco, $fechaNacimiento,
                    $pasaporte, $residencePlace, $address, $clientEmail, $embassyEmail,
                    $amountPaid, $dhl ?: null, $observations, $_SESSION['user_id']
                ]);
            }

            logAudit($familiarId > 0 ? 'update' : 'create', 'solicitudes', "Familiar guardado para solicitud #$id");

            logAdminControlEvent(
                'solicitudes',
                $familiarId > 0 ? 'familiar_actualizar' : 'familiar_crear',
                "Familiar guardado para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'familiar',
                    'entity_id' => $familiarId > 0 ? $familiarId : null,
                    'priority' => 'normal',
                    'metadata' => [
                        'nombre' => $nombreCompleto,
                        'parentesco' => $parentesco,
                        'email_cliente' => $clientEmail,
                        'monto_pagado' => $amountPaid
                    ]
                ]
            );

            $_SESSION['success'] = 'Familiar guardado correctamente';
            $this->redirect('/solicitudes/ver/' . $id . '#familiar-tab');

        } catch (PDOException $e) {
            error_log("Error al guardar familiar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar familiar: ' . $e->getMessage();
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function deleteFamiliar($id) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $familiarId = intval($_POST['familiar_id'] ?? 0);
            if ($familiarId > 0) {
                $this->db->prepare("
                    DELETE isf FROM information_sheet_familiar isf
                    INNER JOIN information_sheets ish ON ish.id = isf.information_sheet_id
                    WHERE isf.id = ? AND ish.application_id = ?
                ")->execute([$familiarId, $id]);
            }

            logAudit('delete', 'solicitudes', "Familiar #$familiarId eliminado para solicitud #$id");

            logAdminControlEvent(
                'solicitudes',
                'familiar_eliminar',
                "Familiar #$familiarId eliminado para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'familiar',
                    'entity_id' => $familiarId,
                    'priority' => 'alta'
                ]
            );

            $_SESSION['success'] = 'Familiar eliminado';
            $this->redirect('/solicitudes/ver/' . $id . '#familiar-tab');

        } catch (PDOException $e) {
            error_log("Error al eliminar familiar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar familiar';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function markClientAttended($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $attended     = isset($_POST['client_attended']) ? 1 : 0;
            $attendedDate = !empty($_POST['client_attended_date']) ? $_POST['client_attended_date'] : null;

            $this->db->prepare("UPDATE applications SET client_attended = ?, client_attended_date = ? WHERE id = ?")
                ->execute([$attended, $attendedDate, $id]);

            // Advance to STATUS_EN_ESPERA_RESULTADO if attended
            if ($attended && $application['status'] === STATUS_CITA_PROGRAMADA) {
                $prevStatus = $application['status'];
                $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([STATUS_EN_ESPERA_RESULTADO, $id]);
                $this->db->prepare("INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$id, $prevStatus, STATUS_EN_ESPERA_RESULTADO, 'Cliente marcó asistencia a cita', $_SESSION['user_id']]);
            }

            logAdminControlEvent(
                'solicitudes',
                'asistencia_cita',
                "Asistencia registrada para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'cita',
                    'priority' => 'alta',
                    'metadata' => [
                        'asistio' => (bool)$attended,
                        'fecha_asistencia' => $attendedDate
                    ]
                ]
            );

            $_SESSION['success'] = 'Asistencia registrada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al registrar asistencia: " . $e->getMessage());
            $_SESSION['error'] = 'Error al registrar asistencia';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    public function linkForm($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();
        $formLinkId = intval($_POST['form_link_id'] ?? 0);

        if ($formLinkId <= 0) {
            $_SESSION['error'] = 'Formulario no válido';
            $this->redirect('/solicitudes/ver/' . $id);
        }

        try {
            $stmt = $this->db->prepare("SELECT created_by FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            // Verify the form exists and is published before generating a link
            $stmtForm = $this->db->prepare("SELECT id FROM forms WHERE id = ? AND is_published = 1");
            $stmtForm->execute([$formLinkId]);
            if (!$stmtForm->fetch()) {
                $_SESSION['error'] = 'El formulario seleccionado no está publicado o no existe';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $this->db->prepare("UPDATE applications SET form_link_id = ?, form_link_status = 'enviado', form_link_sent_at = NOW() WHERE id = ?")
                ->execute([$formLinkId, $id]);

            // Ensure the linked form is publicly accessible when a link is generated
            $this->db->prepare("UPDATE forms SET public_enabled = 1 WHERE id = ?")
                ->execute([$formLinkId]);

            logAdminControlEvent(
                'solicitudes',
                'formulario_vinculado',
                "Formulario vinculado a solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'formulario',
                    'entity_id' => $formLinkId,
                    'priority' => 'normal'
                ]
            );

            $_SESSION['success'] = 'Formulario vinculado. Copia el enlace y compártelo con el cliente.';
            $this->redirect('/solicitudes/ver/' . $id . '?copiar_enlace=1');

        } catch (PDOException $e) {
            error_log("Error al vincular formulario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al vincular formulario';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Guardar cita a oficinas (fecha/hora y modalidad) para estado AZUL.
     */
    public function saveOfficeAppointment($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            $officeDate     = !empty($_POST['office_appointment_date']) ? $_POST['office_appointment_date'] : null;
            $officeModality = in_array($_POST['office_appointment_modality'] ?? '', ['Zoom', 'Presencial'])
                ? $_POST['office_appointment_modality'] : null;

            $this->db->prepare("UPDATE applications SET office_appointment_date = ?, office_appointment_modality = ? WHERE id = ?")
                ->execute([$officeDate, $officeModality, $id]);

            logAdminControlEvent(
                'solicitudes',
                'cita_oficina',
                "Cita a oficinas guardada para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'cita',
                    'priority' => 'alta',
                    'metadata' => [
                        'fecha' => $officeDate,
                        'modalidad' => $officeModality
                    ]
                ]
            );

            $_SESSION['success'] = 'Cita a oficinas guardada correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar cita a oficinas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar cita a oficinas';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Guardar respuestas editadas del formulario (estado Validando respuestas).
     * Accesible para Asesor y Admin/Gerente.
     */
    public function saveFormResponses($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status, data_json FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            if ($application['status'] !== STATUS_VALIDANDO_RESPUESTAS) {
                $_SESSION['error'] = 'Solo se pueden editar respuestas cuando el estatus es "Validando respuestas"';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $existingData = json_decode($application['data_json'], true) ?: [];
            $postedAnswers = $_POST['answers'] ?? [];

            // Merge posted answers into existing data (only overwrite non-file keys)
            foreach ($postedAnswers as $key => $value) {
                $key = strip_tags($key);
                if (is_array($value)) {
                    $existingData[$key] = array_map('strip_tags', $value);
                } else {
                    $existingData[$key] = strip_tags($value);
                }
            }

            $newJson = json_encode($existingData, JSON_UNESCAPED_UNICODE);
            $this->db->prepare("UPDATE applications SET data_json = ? WHERE id = ?")
                ->execute([$newJson, $id]);

            logAudit(
                'update',
                'solicitudes',
                'Respuestas del cuestionario editadas (estatus Validando respuestas) para solicitud #' . $id
            );

            logAdminControlEvent(
                'solicitudes',
                'respuestas_guardadas',
                "Respuestas del cuestionario editadas para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'respuestas',
                    'priority' => 'normal'
                ]
            );

            $_SESSION['success'] = 'Respuestas guardadas correctamente';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al guardar respuestas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar respuestas';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Confirmar respuestas del formulario y avanzar a "Listo para comenzar" (STATUS_LISTO_SOLICITUD).
     * Accesible para Asesor y Admin/Gerente.
     */
    public function confirmFormResponses($id) {
        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes/ver/' . $id);
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by, status FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/solicitudes');
            }

            if ($application['status'] !== STATUS_VALIDANDO_RESPUESTAS) {
                $_SESSION['error'] = 'La solicitud no está en estado "Validando respuestas"';
                $this->redirect('/solicitudes/ver/' . $id);
            }

            $this->db->prepare("UPDATE applications SET status = ? WHERE id = ?")
                ->execute([STATUS_LISTO_SOLICITUD, $id]);

            $this->db->prepare("
                INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$id, STATUS_VALIDANDO_RESPUESTAS, STATUS_LISTO_SOLICITUD, 'Respuestas confirmadas', $_SESSION['user_id']]);

            logAudit(
                'status_change',
                'solicitudes',
                'Respuestas confirmadas: ' . STATUS_VALIDANDO_RESPUESTAS . ' → ' . STATUS_LISTO_SOLICITUD . ' para solicitud #' . $id
            );

            logAdminControlEvent(
                'solicitudes',
                'respuestas_confirmadas',
                "Respuestas confirmadas para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'respuestas',
                    'priority' => 'alta',
                    'metadata' => [
                        'estatus_anterior' => STATUS_VALIDANDO_RESPUESTAS,
                        'estatus_nuevo' => STATUS_LISTO_SOLICITUD
                    ]
                ]
            );

            $_SESSION['success'] = 'Respuestas confirmadas. Estatus actualizado a "Listo para comenzar"';
            $this->redirect('/solicitudes/ver/' . $id);

        } catch (PDOException $e) {
            error_log("Error al confirmar respuestas: " . $e->getMessage());
            $_SESSION['error'] = 'Error al confirmar respuestas';
            $this->redirect('/solicitudes/ver/' . $id);
        }
    }

    /**
     * Vista pública para que asesoras confirmen citas del día siguiente.
     */
    public function publicSolicitudes() {
        $this->requireLogin();

        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];

        try {
            // Solicitudes en estado "Cita programada" con cita MAÑANA
            $tomorrow = date('Y-m-d', strtotime('+1 day'));

            $sql = "
                SELECT a.*, u.full_name as creator_name, f.name as form_name,
                       COALESCE(a.appointment_confirmed_day_before, 0) as appointment_confirmed_day_before
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN forms f ON a.form_id = f.id
                WHERE a.status = ?
                  AND DATE(a.appointment_date) = ?
            ";
            $params = [STATUS_CITA_PROGRAMADA, $tomorrow];

            if ($role === ROLE_ASESOR) {
                $sql .= " AND a.created_by = ?";
                $params[] = $userId;
            }
            $sql .= " ORDER BY a.appointment_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $appointmentApplications = $stmt->fetchAll();

            $this->view('public/solicitudes', [
                'appointmentApplications' => $appointmentApplications,
            ]);

        } catch (PDOException $e) {
            error_log("Error en publicSolicitudes: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar solicitudes';
            $this->view('public/solicitudes', ['appointmentApplications' => []]);
        }
    }

    /**
     * Asesor confirma que la cita sigue vigente un día antes.
     */
    public function confirmAppointment($id) {        $this->requireRole([ROLE_ASESOR, ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/public/solicitudes');
        }

        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("SELECT created_by FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch();

            if (!$application) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/public/solicitudes');
            }

            if ($role === ROLE_ASESOR && intval($application['created_by']) !== intval($_SESSION['user_id'])) {
                $_SESSION['error'] = 'No tiene permisos para esta solicitud';
                $this->redirect('/public/solicitudes');
            }

            $this->db->prepare("UPDATE applications SET appointment_confirmed_day_before = 1 WHERE id = ?")
                ->execute([$id]);

            logAdminControlEvent(
                'solicitudes',
                'cita_confirmada',
                "Cita confirmada un dia antes para solicitud #$id",
                [
                    'application_id' => $id,
                    'entity_type' => 'cita',
                    'priority' => 'alta'
                ]
            );

            $_SESSION['success'] = 'Cita confirmada correctamente';
            $this->redirect('/public/solicitudes');

        } catch (PDOException $e) {
            error_log("Error al confirmar cita: " . $e->getMessage());
            $_SESSION['error'] = 'Error al confirmar cita';
            $this->redirect('/public/solicitudes');
        }
    }

    /**
     * Eliminar solicitud (solo Admin).
     */
    public function delete($id) {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/solicitudes');
        }

        try {
            // Obtener solicitud y sus documentos para borrar archivos físicos
            $stmt = $this->db->prepare("SELECT id, folio FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $applicationToDelete = $stmt->fetch();
            if (!$applicationToDelete) {
                $_SESSION['error'] = 'Solicitud no encontrada';
                $this->redirect('/solicitudes');
            }

            // Borrar archivos físicos de documentos
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE application_id = ?");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $doc) {
                $filePath = ROOT_PATH . '/public' . $doc['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Borrar registros relacionados (tablas explícitas para evitar inyección)
            $relatedTables = [
                "DELETE FROM documents WHERE application_id = ?",
                "DELETE FROM status_history WHERE application_id = ?",
                "DELETE FROM application_notes WHERE application_id = ?",
                "DELETE FROM financial_costs WHERE application_id = ?",
                "DELETE FROM payments WHERE application_id = ?",
                "DELETE FROM financial_status WHERE application_id = ?",
                "DELETE FROM information_sheets WHERE application_id = ?",
                "DELETE FROM public_form_submissions WHERE application_id = ?",
            ];
            foreach ($relatedTables as $sql) {
                try {
                    $this->db->prepare($sql)->execute([$id]);
                } catch (PDOException $e) {
                    // Tabla puede no existir; continuar
                }
            }

            // Borrar solicitud
            $this->db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);

            logAudit('delete', 'solicitudes', "Solicitud #$id eliminada por administrador");

            logAdminControlEvent(
                'solicitudes',
                'eliminar',
                "Solicitud {$applicationToDelete['folio']} eliminada por administrador",
                [
                    'folio' => $applicationToDelete['folio'] ?? null,
                    'entity_type' => 'solicitud',
                    'entity_id' => $id,
                    'priority' => 'critica'
                ]
            );

            $_SESSION['success'] = 'Solicitud eliminada correctamente';
            $this->redirect('/solicitudes');

        } catch (PDOException $e) {
            error_log("Error al eliminar solicitud: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar la solicitud';
            $this->redirect('/solicitudes');
        }
    }

    /**
     * Visualizar un documento por su ID.
     */
    // Esta ruta se usa como src de <img>/<embed> para previsualizar documentos inline;
    // en caso de error debe responder con texto plano y no redirigir a una página HTML
    // completa, que quedaría anidada (y navegable) dentro del visor.
    private function denyDocumentView($message, $status = 403) {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }

    public function viewDocument($docId) {
        $this->requireLogin();
        $role = $this->getUserRole();

        try {
            $stmt = $this->db->prepare("
                SELECT d.*, a.id as app_id, a.folio, a.created_by as app_created_by, a.status
                FROM documents d
                LEFT JOIN applications a ON d.application_id = a.id
                WHERE d.id = ?
            ");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                $this->denyDocumentView('Documento no encontrado', 404);
            }

            $docStatus = $doc['status'] ?? '';
            $docCreatorId = intval($doc['app_created_by'] ?? 0);

            if ($role === ROLE_ASESOR) {
                if ($docStatus === STATUS_TRAMITE_CERRADO || $docStatus === STATUS_FINALIZADO) {
                    $this->denyDocumentView('No tiene permisos para esta solicitud');
                }
                if ($docCreatorId !== intval($_SESSION['user_id'])) {
                    $this->denyDocumentView('No tiene permisos para esta solicitud');
                }
            } elseif (!in_array($role, [ROLE_ADMIN, ROLE_GERENTE])) {
                $this->denyDocumentView('No tiene permisos para visualizar documentos');
            }

            $filePath = ROOT_PATH . '/public' . $doc['file_path'];
            if (!file_exists($filePath)) {
                $this->denyDocumentView('El archivo no existe en el servidor', 404);
            }

            logAudit('view', 'documentos', "Visualización de documento #$docId ({$doc['name']})");

            logAdminControlEvent(
                'documentos',
                'visualizar',
                "Documento visualizado: {$doc['name']}",
                [
                    'application_id' => $doc['app_id'] ?? null,
                    'folio' => $doc['folio'] ?? null,
                    'entity_type' => 'documento',
                    'entity_id' => $docId,
                    'priority' => 'normal'
                ]
            );

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $declared = strtolower(trim((string)($doc['file_type'] ?? '')));
            $mimeMap = [
                'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
                'gif'=>'image/gif','pdf'=>'application/pdf'
            ];
            $mimeType = $mimeMap[$ext] ?? $mimeMap[$declared] ?? (mime_content_type($filePath) ?: 'application/octet-stream');

            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . basename($doc['name']) . '"');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, no-store, max-age=0');
            readfile($filePath);
            exit;

        } catch (PDOException $e) {
            error_log("Error al visualizar documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al visualizar documento';
            $this->redirect('/solicitudes');
        }
    }

    public function downloadDocument($docId) {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        try {
            $stmt = $this->db->prepare("
                SELECT d.*, a.id as app_id, a.folio
                FROM documents d
                LEFT JOIN applications a ON d.application_id = a.id
                WHERE d.id = ?
            ");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                $_SESSION['error'] = 'Documento no encontrado';
                $this->redirect('/solicitudes');
            }

            $filePath = ROOT_PATH . '/public' . $doc['file_path'];
            if (!file_exists($filePath)) {
                $_SESSION['error'] = 'El archivo no existe en el servidor';
                $this->redirect('/solicitudes/ver/' . $doc['app_id']);
            }

            logAudit('download', 'documentos', "Descarga de documento #$docId ({$doc['name']})");

            logAdminControlEvent(
                'documentos',
                'descargar',
                "Documento descargado: {$doc['name']}",
                [
                    'application_id' => $doc['app_id'] ?? null,
                    'folio' => $doc['folio'] ?? null,
                    'entity_type' => 'documento',
                    'entity_id' => $docId,
                    'priority' => 'alta'
                ]
            );

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        } catch (PDOException $e) {
            error_log("Error al descargar documento: " . $e->getMessage());
            $_SESSION['error'] = 'Error al descargar documento';
            $this->redirect('/solicitudes');
        }
    }

    private function applicationEditCsrfToken(): string {
        if (empty($_SESSION['application_edit_csrf'])) {
            $_SESSION['application_edit_csrf'] = bin2hex(random_bytes(24));
        }
        return (string)$_SESSION['application_edit_csrf'];
    }

    private function verifyApplicationEditCsrf(): void {
        $posted = (string)($_POST['csrf_token'] ?? '');
        $known = (string)($_SESSION['application_edit_csrf'] ?? '');
        if ($known === '' || $posted === '' || !hash_equals($known, $posted)) {
            throw new RuntimeException('La sesión de edición expiró. Vuelva a abrir la solicitud e intente nuevamente.');
        }
    }

    /**
     * Helper: save an uploaded file as a document of the given doc_type.
     * Returns true on success, false on failure.
     */
    private function saveApplicationFile($appId, array $fileInfo, string $docType): bool {
        $fileName    = $fileInfo['name'];
        $fileSize    = $fileInfo['size'];
        $fileTmpName = $fileInfo['tmp_name'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileSize > MAX_FILE_SIZE || !in_array($fileExt, ALLOWED_EXTENSIONS)) {
            return false;
        }

        $uploadDir = ROOT_PATH . '/public/uploads/applications/' . $appId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName  = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $filePath     = $uploadDir . '/' . $newFileName;

        if (!move_uploaded_file($fileTmpName, $filePath)) {
            return false;
        }

        $relativePath = '/uploads/applications/' . $appId . '/' . $newFileName;
        try {
            $this->db->prepare("
                INSERT INTO documents (application_id, name, doc_type, file_path, file_type, file_size, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $appId,
                $fileName,
                $docType,
                $relativePath,
                $fileExt,
                $fileSize,
                $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("saveApplicationFile PDO error: " . $e->getMessage());
            return false;
        }

        logAdminControlEvent(
            'documentos',
            'subir',
            "Documento interno subido a solicitud #$appId: $fileName",
            [
                'application_id' => $appId,
                'entity_type' => 'documento',
                'priority' => 'normal',
                'metadata' => [
                    'documento' => $fileName,
                    'tipo_documento' => $docType,
                    'extension' => $fileExt,
                    'tamano' => $fileSize
                ]
            ]
        );

        return true;
    }
}
