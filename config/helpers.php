<?php
/**
 * Helper function to get global configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if not found
 * @return mixed Configuration value or default
 */
function getConfig($key, $default = null) {
    static $configCache = null;
    
    // Load all configs once
    if ($configCache === null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT config_key, config_value FROM global_config");
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $configCache = $configs;
        } catch (PDOException $e) {
            error_log("Error loading config: " . $e->getMessage());
            $configCache = [];
        }
    }
    
    return $configCache[$key] ?? $default;
}

/**
 * Get site logo path
 * @return string|null Logo path or null if not configured
 */
function getSiteLogo() {
    $logo = trim((string) getConfig('site_logo', ''));
    // Validate logo path is relative and doesn't contain protocols
    if ($logo === '') {
        return null;
    }
    if (strpos($logo, '://') !== false || strpos($logo, 'javascript:') === 0) {
        error_log("Invalid logo path detected: $logo");
        return null;
    }
    $logo = '/' . ltrim(str_replace('\\', '/', $logo), '/');
    if (strpos($logo, '/uploads/') !== 0 || strpos($logo, '..') !== false) {
        return null;
    }

    $absolutePath = ROOT_PATH . '/public' . $logo;
    if (!is_file($absolutePath) || !is_readable($absolutePath)) {
        return null;
    }

    return $logo;
}

/**
 * Get site name
 * @return string Site name
 */
function getSiteName() {
    return getConfig('site_name', SITE_NAME);
}

/**
 * Check whether the admin control table is available.
 * This helper is intentionally defensive so operational actions never fail
 * just because the intranet control migration has not been applied yet.
 *
 * @param PDO|null $db
 * @return bool
 */
function adminControlTableExists($db = null) {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $stmt = $db->query("SHOW TABLES LIKE 'admin_control_events'");
        $exists = $stmt && $stmt->fetch() !== false;
    } catch (Exception $e) {
        $exists = false;
    }

    return $exists;
}

/**
 * Register an internal intranet event for the administrator control center.
 *
 * @param string $module
 * @param string $action
 * @param string $description
 * @param array $options
 * @return bool
 */
function logAdminControlEvent($module, $action, $description, $options = []) {
    try {
        $db = Database::getInstance()->getConnection();

        if (!adminControlTableExists($db)) {
            return false;
        }

        $priority = $options['priority'] ?? 'normal';
        if (!in_array($priority, ['baja', 'normal', 'alta', 'critica'], true)) {
            $priority = 'normal';
        }

        $applicationId = $options['application_id'] ?? null;
        $folio = $options['folio'] ?? null;

        if ($applicationId && empty($folio)) {
            try {
                $stmtApp = $db->prepare("SELECT folio FROM applications WHERE id = ?");
                $stmtApp->execute([$applicationId]);
                $app = $stmtApp->fetch(PDO::FETCH_ASSOC);
                if ($app && !empty($app['folio'])) {
                    $folio = $app['folio'];
                }
            } catch (PDOException $e) {
                // Non-fatal; the event can still be stored without a folio.
            }
        }

        $metadata = $options['metadata'] ?? [];
        $metadataJson = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $userId = array_key_exists('user_id', $options) ? $options['user_id'] : ($_SESSION['user_id'] ?? null);
        $userName = $options['user_name'] ?? ($_SESSION['user_name'] ?? null);

        $stmt = $db->prepare("
            INSERT INTO admin_control_events
                (module, action, entity_type, entity_id, application_id, folio,
                 user_id, user_name, description, metadata_json, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $module,
            $action,
            $options['entity_type'] ?? null,
            $options['entity_id'] ?? null,
            $applicationId,
            $folio,
            $userId,
            $userName,
            $description,
            $metadataJson,
            $priority
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Error logging admin control event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread events count for the administrator menu badge.
 *
 * @return int
 */
function getAdminControlUnreadCount() {
    try {
        $db = Database::getInstance()->getConnection();
        if (!adminControlTableExists($db)) {
            return 0;
        }
        return (int) $db->query("SELECT COUNT(*) FROM admin_control_events WHERE is_read = 0")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check whether audit_trail has the metadata_json column.
 *
 * @param PDO|null $db
 * @return bool
 */
function auditTrailMetadataColumnExists($db = null) {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $stmt = $db->query("SHOW COLUMNS FROM audit_trail LIKE 'metadata_json'");
        $exists = $stmt && $stmt->fetch() !== false;
    } catch (Exception $e) {
        $exists = false;
    }

    return $exists;
}

/**
 * Log audit trail event
 * @param string $action Action performed (login, logout, create, update, delete, etc)
 * @param string $module Module name (usuarios, solicitudes, formularios, etc)
 * @param string $description Detailed description of the action
 * @param array $metadata Additional metadata (optional)
 * @return bool Success status
 */
function logAudit($action, $module, $description, $metadata = []) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get current user info
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? null;
        
        // Get IP and User Agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metadataJson = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $columns = "user_id, user_name, user_email, action, module, description, ip_address, user_agent";
        $placeholders = "?, ?, ?, ?, ?, ?, ?, ?";
        $values = [
            $userId,
            $userName,
            $userEmail,
            $action,
            $module,
            $description,
            $ipAddress,
            $userAgent
        ];

        if (auditTrailMetadataColumnExists($db)) {
            $columns .= ", metadata_json";
            $placeholders .= ", ?";
            $values[] = $metadataJson;
        }

        $stmt = $db->prepare("
            INSERT INTO audit_trail
            ($columns)
            VALUES ($placeholders)
        ");

        $stmt->execute($values);
        
        return true;
    } catch (PDOException $e) {
        // Don't throw exception, just log error
        error_log("Error logging audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Log customer journey touchpoint
 * @param int $applicationId Application ID
 * @param string $touchpointType Type of touchpoint (email, call, meeting, status_change, etc)
 * @param string $title Short title of the touchpoint
 * @param string $description Detailed description
 * @param string|null $contactMethod How contact was made (email, phone, in-person, online)
 * @param array $metadata Additional metadata in array format
 * @return bool Success status
 */
function logCustomerJourney($applicationId, $touchpointType, $title, $description = '', $contactMethod = null, $metadata = []) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $userId = $_SESSION['user_id'] ?? null;
        $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
        
        $stmt = $db->prepare("
            INSERT INTO customer_journey 
            (application_id, touchpoint_type, touchpoint_title, touchpoint_description, contact_method, user_id, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $applicationId,
            $touchpointType,
            $title,
            $description,
            $contactMethod,
            $userId,
            $metadataJson
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging customer journey: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all unique email recipients for an application appointment notification.
 *
 * Collects emails from:
 *   1. Basic application data (data_json.email)
 *   2. Information sheet (information_sheets.client_email)
 *   3. Any 'email' type fields in the client form response (data_json)
 *   4. The asesor who created the application (users.email)
 *   5. All active Admins and Gerentes (users.email)
 *
 * Returned array is deduplicated and all values are validated email addresses.
 *
 * @param int    $applicationId
 * @param PDO    $db
 * @return array List of unique email strings
 */
function getApplicationEmailRecipients($applicationId, $db) {
    $emails = [];

    try {
        // 1 & 3: Application basic data + form fields
        $stmt = $db->prepare("
            SELECT a.data_json, a.form_id, a.created_by
            FROM applications a
            WHERE a.id = ?
        ");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return [];
        }

        $basicData = json_decode($app['data_json'], true) ?: [];

        // Email from basic registration fields
        if (!empty($basicData['email']) && filter_var(trim($basicData['email']), FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower(trim($basicData['email']));
        }

        // Any email-type fields in the linked form schema
        if (!empty($app['form_id'])) {
            try {
                $stmtF = $db->prepare("SELECT fields_json FROM forms WHERE id = ?");
                $stmtF->execute([$app['form_id']]);
                $form = $stmtF->fetch(PDO::FETCH_ASSOC);
                if ($form) {
                    $fieldsData = json_decode($form['fields_json'], true) ?: [];
                    foreach ($fieldsData['fields'] ?? [] as $field) {
                        if (($field['type'] ?? '') === 'email' && !empty($basicData[$field['id'] ?? ''])) {
                            $val = trim($basicData[$field['id']]);
                            if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                                $emails[] = strtolower($val);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                // Ignore if form not found
            }
        }

        // 2: Information sheet client email
        try {
            $stmtS = $db->prepare("SELECT client_email FROM information_sheets WHERE application_id = ?");
            $stmtS->execute([$applicationId]);
            $sheet = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($sheet && !empty($sheet['client_email']) && filter_var(trim($sheet['client_email']), FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($sheet['client_email']));
            }
        } catch (PDOException $e) {
            // Table may not exist yet
        }

        // 4: Asesor email
        $stmtU = $db->prepare("SELECT email FROM users WHERE id = ? AND is_active = 1");
        $stmtU->execute([$app['created_by']]);
        $asesor = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($asesor && !empty($asesor['email']) && filter_var(trim($asesor['email']), FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower(trim($asesor['email']));
        }

        // 5: All active Admins and Gerentes
        $stmtM = $db->prepare("SELECT email FROM users WHERE role IN ('Administrador', 'Gerente') AND is_active = 1");
        $stmtM->execute();
        foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $manager) {
            if (!empty($manager['email']) && filter_var(trim($manager['email']), FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($manager['email']));
            }
        }

    } catch (PDOException $e) {
        error_log("Error collecting email recipients for application #$applicationId: " . $e->getMessage());
    }

    return array_values(array_unique(array_filter($emails)));
}

/**
 * Send an appointment notification email (creation or 2-day reminder).
 *
 * @param int    $applicationId  Application ID
 * @param string $type           'consular' or 'biometric'
 * @param string $appointmentDate Date/datetime string of the appointment
 * @param bool   $isReminder     true = reminder email, false = creation email
 * @param PDO    $db
 * @return bool  true if all emails sent without error, false otherwise
 */
function sendAppointmentNotificationEmail($applicationId, $type, $appointmentDate, $isReminder, $db) {
    // Load PHPMailer
    require_once ROOT_PATH . '/vendor/autoload.php';

    // Read SMTP configuration
    try {
        $stmt = $db->query("SELECT config_key, config_value FROM global_config WHERE config_key IN ('smtp_user','smtp_password','smtp_host','smtp_port','site_name')");
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error reading SMTP config for appointment email: " . $e->getMessage());
        return false;
    }

    $smtpHost     = $config['smtp_host']     ?? '';
    $smtpUser     = $config['smtp_user']     ?? '';
    $smtpPassword = $config['smtp_password'] ?? '';
    $smtpPort     = (int)($config['smtp_port'] ?? 465);
    $siteName     = $config['site_name']     ?? (defined('SITE_NAME') ? SITE_NAME : 'CRM Visas');

    if (empty($smtpHost) || empty($smtpUser) || empty($smtpPassword)) {
        error_log("Incomplete SMTP config — skipping appointment email for application #$applicationId.");
        return false;
    }

    // Collect recipients
    $recipients = getApplicationEmailRecipients($applicationId, $db);
    if (empty($recipients)) {
        error_log("No email recipients found for application #$applicationId.");
        return false;
    }

    // Fetch application details
    try {
        $stmtA = $db->prepare("
            SELECT a.*, u.full_name AS creator_name, f.name AS form_name
            FROM applications a
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN forms f ON a.form_id = f.id
            WHERE a.id = ?
        ");
        $stmtA->execute([$applicationId]);
        $application = $stmtA->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching application #$applicationId for email: " . $e->getMessage());
        return false;
    }

    if (!$application) {
        return false;
    }

    // Fetch PDF documents to attach
    $pdfDocuments = [];
    try {
        $stmtD = $db->prepare("SELECT name, file_path FROM documents WHERE application_id = ?");
        $stmtD->execute([$applicationId]);
        foreach ($stmtD->fetchAll(PDO::FETCH_ASSOC) as $doc) {
            $ext      = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            $fullPath = ROOT_PATH . '/public' . $doc['file_path'];
            if ($ext === 'pdf' && file_exists($fullPath)) {
                $pdfDocuments[] = ['path' => $fullPath, 'name' => basename($doc['name']) . '.pdf'];
            }
        }
    } catch (PDOException $e) {
        // Non-fatal: proceed without attachments
    }

    // Build email content
    $data       = json_decode($application['data_json'], true) ?: [];
    $clientName = trim(($application['client_name'] ?? '') ?: ($data['nombre_cliente'] ?? '') ?: (($data['nombre'] ?? '') . ' ' . ($data['apellidos'] ?? '')));
    if (empty($clientName)) {
        $clientName = 'Cliente';
    }
    $folio       = $application['folio'];
    $isCanadian  = !empty($application['is_canadian_visa']);
    $advisorName = htmlspecialchars($application['creator_name'] ?? 'Asesor', ENT_QUOTES, 'UTF-8');

    $dateFormatted = date('d/m/Y H:i', strtotime($appointmentDate));

    if ($type === 'biometric') {
        $appointmentLabel = 'Cita Biométrica';
        $location         = $application['canadian_biometric_location'] ?? '';
    } else {
        $appointmentLabel = 'Cita Consular';
        $location         = '';
    }

    if ($isCanadian) {
        $tramiteLabel = htmlspecialchars(
            ($application['canadian_tipo'] ?? 'Visa Canadiense') .
            ($application['canadian_modalidad'] ? ' — ' . $application['canadian_modalidad'] : ''),
            ENT_QUOTES, 'UTF-8'
        );
    } else {
        $tramiteLabel = htmlspecialchars(
            $application['type'] . ($application['subtype'] ? ' — ' . $application['subtype'] : ''),
            ENT_QUOTES, 'UTF-8'
        );
    }

    if ($isReminder) {
        $subjectPrefix = '⏰ Recordatorio de cita';
        $headerText    = 'Recordatorio: ' . $appointmentLabel;
        $introText     = 'Le recordamos que la siguiente cita está programada próximamente (en los próximos 2 días).';
    } else {
        $subjectPrefix = '📅 Cita agendada';
        $headerText    = $appointmentLabel . ' Confirmada';
        $introText     = 'Se ha agendado exitosamente una cita para el siguiente trámite.';
    }

    $subject = "$subjectPrefix — $folio — $siteName";

    $locationRow = !empty($location)
        ? '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;width:40%;">Lugar</th>'
          . '<td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        : '';

    $body = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
        . '<div style="background-color:#3b82f6;padding:24px;text-align:center;">'
        . '<h1 style="color:#ffffff;margin:0;font-size:22px;">📅 ' . htmlspecialchars($headerText, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p style="color:#bfdbfe;margin:8px 0 0;">' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>'
        . '<div style="padding:24px;">'
        . '<p style="color:#374151;font-size:15px;">' . htmlspecialchars($introText, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;width:40%;">Folio</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($folio, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Cliente</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Trámite</th><td style="padding:8px 12px;color:#111827;">' . $tramiteLabel . '</td></tr>'
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Tipo de Cita</th><td style="padding:8px 12px;color:#111827;">' . htmlspecialchars($appointmentLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr style="background:#f3f4f6;"><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Fecha y Hora</th><td style="padding:8px 12px;color:#1d4ed8;font-weight:bold;">' . htmlspecialchars($dateFormatted, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . $locationRow
        . '<tr><th style="text-align:left;padding:8px 12px;color:#6b7280;font-weight:600;">Asesor</th><td style="padding:8px 12px;color:#111827;">' . $advisorName . '</td></tr>'
        . '</table>'
        . '<div style="background-color:#fef9c3;border:2px solid #f59e0b;border-radius:8px;padding:16px;margin:20px 0;">'
        . '<p style="margin:0;font-size:15px;color:#92400e;font-weight:bold;">⚠️ Recordatorio importante</p>'
        . '<p style="margin:8px 0 0;font-size:14px;color:#78350f;">Recuerda verificar que tus citas sigan vigentes y listas para presentación enviando un WhatsApp al <strong>4424495675</strong>.</p>'
        . '</div>'
        . '<p style="color:#6b7280;font-size:12px;margin-top:24px;border-top:1px solid #e5e7eb;padding-top:16px;">'
        . 'Este correo fue generado automáticamente por ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '. Por favor no responda a este mensaje.'
        . '</p>'
        . '</div>'
        . '</div>';

    $altBody = "Folio: $folio | Cliente: $clientName | $appointmentLabel: $dateFormatted"
        . (!empty($location) ? " | Lugar: $location" : '')
        . " | Asesor: {$application['creator_name']}"
        . " | IMPORTANTE: Recuerda verificar que tus citas sigan vigentes enviando un WhatsApp al 4424495675.";

    // Send one email per unique recipient
    $sendErrors = [];
    foreach ($recipients as $recipient) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPassword;
            $mail->Port       = $smtpPort;
            $mail->SMTPSecure = ($smtpPort == 465)
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($smtpUser, $siteName);
            $mail->addAddress($recipient);

            // Attach PDF documents
            foreach ($pdfDocuments as $pdf) {
                $mail->addAttachment($pdf['path'], $pdf['name']);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;
            $mail->send();

        } catch (Exception $e) {
            $sendErrors[] = $recipient . ': ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage());
        }
    }

    if (!empty($sendErrors)) {
        error_log("Appointment email errors for application #$applicationId: " . implode(' | ', $sendErrors));
    }

    return empty($sendErrors);
}

/**
 * Get upcoming appointment notifications for the current user.
 * Returns appointments within the next 2 days that have not yet been attended.
 * Advisors only see their own clients; Admin/Gerente see all.
 *
 * @return array List of notification items with keys:
 *               application_id, folio, client_name, notification_type,
 *               appointment_date, location, is_read
 */
function getUpcomingNotifications() {
    $userId   = $_SESSION['user_id']   ?? null;
    $userRole = $_SESSION['user_role'] ?? null;

    if (!$userId || !$userRole) {
        return [];
    }

    try {
        $db = Database::getInstance()->getConnection();

        $today    = date('Y-m-d');
        $deadline = date('Y-m-d', strtotime('+2 days'));

        // Generic (non-Canadian) upcoming appointments
        $sqlGeneric = "
            SELECT
                a.id            AS application_id,
                a.folio,
                a.created_by,
                JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')) AS client_name,
                'appointment'   AS notification_type,
                a.appointment_date AS appointment_date,
                NULL            AS location,
                CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read
            FROM applications a
            LEFT JOIN notification_reads nr
                   ON nr.application_id = a.id
                  AND nr.notification_type = 'appointment'
                  AND nr.user_id = ?
            WHERE a.appointment_date IS NOT NULL
              AND DATE(a.appointment_date) >= ?
              AND DATE(a.appointment_date) <= ?
              AND (a.client_attended IS NULL OR a.client_attended = 0)
        ";

        // Canadian visa biometric upcoming appointments
        $sqlBiometric = "
            SELECT
                a.id            AS application_id,
                a.folio,
                a.created_by,
                JSON_UNQUOTE(JSON_EXTRACT(a.data_json, '$.nombre')) AS client_name,
                'biometric'     AS notification_type,
                a.canadian_biometric_date AS appointment_date,
                a.canadian_biometric_location AS location,
                CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read
            FROM applications a
            LEFT JOIN notification_reads nr
                   ON nr.application_id = a.id
                  AND nr.notification_type = 'biometric'
                  AND nr.user_id = ?
            WHERE a.canadian_biometric_date IS NOT NULL
              AND DATE(a.canadian_biometric_date) >= ?
              AND DATE(a.canadian_biometric_date) <= ?
              AND (a.canadian_client_attended_biometrics IS NULL OR a.canadian_client_attended_biometrics = 0)
        ";

        $advisorFilter = '';
        if ($userRole === 'Asesor') {
            $advisorFilter = ' AND a.created_by = ?';
        }

        $sql = "($sqlGeneric $advisorFilter) UNION ALL ($sqlBiometric $advisorFilter) ORDER BY appointment_date ASC";

        $params = [$userId, $today, $deadline];
        if ($userRole === 'Asesor') {
            $params[] = $userId;
        }
        $params[] = $userId;
        $params[] = $today;
        $params[] = $deadline;
        if ($userRole === 'Asesor') {
            $params[] = $userId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Número de conversaciones de WhatsApp esperando atención humana.
 * Devuelve 0 si la migración V2 aún no fue aplicada.
 */
function getWhatsAppPendingCount() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) AS total FROM bot_conversations WHERE status = 'WAITING_HUMAN'");
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Normaliza las formas de pago usadas por el módulo financiero.
 */
function financialNormalizePaymentMethod($method) {
    $value = strtoupper(trim((string) $method));
    $value = str_replace(['Á','É','Í','Ó','Ú','/','-'], ['A','E','I','O','U','_','_'], $value);

    if ($value === 'EFECTIVO' || $value === 'CASH') return 'EFECTIVO';
    if (strpos($value, 'DEPOS') !== false) return 'DEPOSITO_BANCARIO';
    if (strpos($value, 'TRANSFER') !== false) return 'TRANSFERENCIA';
    if (strpos($value, 'TARJETA') !== false || strpos($value, 'TERMINAL') !== false || strpos($value, 'CARD') !== false) return 'TERMINAL_TARJETA';
    return 'OTRO';
}

function financialPaymentMethodLabel($method) {
    $method = financialNormalizePaymentMethod($method);
    if ($method === 'EFECTIVO') return 'Efectivo';
    if ($method === 'TRANSFERENCIA') return 'Transferencia bancaria';
    if ($method === 'DEPOSITO_BANCARIO') return 'Depósito bancario';
    if ($method === 'TERMINAL_TARJETA') return 'Terminal / Tarjeta';
    return 'Otro método autorizado';
}

function financialGetActiveCashDestinations($db = null) {
    try {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $stmt = $db->query("SELECT id, name, is_free_cash FROM financial_envelopes WHERE status = 'ACTIVO' ORDER BY is_free_cash DESC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function financialClosedWeekForDate($date, $db = null) {
    try {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $stmt = $db->prepare("SELECT * FROM financial_weekly_closures WHERE ? BETWEEN week_start AND week_end AND status = 'CERRADO' LIMIT 1");
        $stmt->execute([$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Un periodo cerrado sólo acepta cambios durante una autorización temporal
 * generada por un Administrador desde Balance Semanal.
 */
function financialCanWriteDate($date, $db = null) {
    $closed = financialClosedWeekForDate($date, $db);
    if (!$closed) {
        return true;
    }

    if (!empty($closed['correction_authorized_until']) && strtotime($closed['correction_authorized_until']) >= time()) {
        return true;
    }

    return false;
}

function financialWriteIsCorrection($date, $db = null) {
    return financialClosedWeekForDate($date, $db) !== null;
}

function financialLogCorrectionIfNeeded($date, $entityType, $entityId, $metadata = [], $db = null) {
    try {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $closed = financialClosedWeekForDate($date, $db);
        if (!$closed) {
            return;
        }

        $reason = trim((string) ($closed['correction_reason'] ?? 'Corrección autorizada de periodo cerrado'));
        $stmt = $db->prepare("INSERT INTO financial_correction_log
            (weekly_closure_id, action_type, entity_type, entity_id, reason, metadata_json, authorized_by)
            VALUES (?, 'MOVIMIENTO_POSTERIOR_AL_CIERRE', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $closed['id'],
            $entityType,
            $entityId,
            $reason !== '' ? $reason : 'Corrección autorizada de periodo cerrado',
            json_encode($metadata, JSON_UNESCAPED_UNICODE),
            $closed['correction_authorized_by'] ?: ($_SESSION['user_id'] ?? 0)
        ]);
    } catch (Exception $e) {
        error_log('No se pudo registrar corrección financiera: ' . $e->getMessage());
    }
}

function financialUpdateApplicationStatus(PDO $db, $applicationId) {
    if (function_exists('incomeControlTablesAvailable') && incomeControlTablesAvailable($db)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM income_sales WHERE application_id=? AND financial_status<>'CANCELADO'");
            $stmt->execute([$applicationId]);
            if ((int)$stmt->fetchColumn() > 0) {
                incomeRefreshApplicationFinancialStatus($db, (int)$applicationId);
                return;
            }
        } catch (Throwable $e) {}
    }

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM financial_costs WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    $totalCosts = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    $totalPaid = (float) $stmt->fetchColumn();
    $balance = $totalCosts - $totalPaid;

    if ($totalPaid <= 0) {
        $status = FINANCIAL_PENDIENTE;
    } elseif ($balance > 0.009) {
        $status = FINANCIAL_PARCIAL;
    } else {
        $status = FINANCIAL_PAGADO;
    }

    $stmt = $db->prepare("INSERT INTO financial_status (application_id, total_costs, total_paid, balance, status)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE total_costs=VALUES(total_costs), total_paid=VALUES(total_paid), balance=VALUES(balance), status=VALUES(status)");
    $stmt->execute([$applicationId, $totalCosts, $totalPaid, $balance, $status]);
}

function financialSyncPaymentCashMovement(PDO $db, $paymentId) {
    $stmt = $db->prepare("SELECT p.*, a.client_name, a.type, a.subtype
        FROM payments p
        INNER JOIN applications a ON a.id = p.application_id
        WHERE p.id = ? LIMIT 1");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) return;

    $db->prepare("DELETE FROM financial_envelope_movements WHERE source_type='PAGO' AND source_id=?")->execute([$paymentId]);

    if (financialNormalizePaymentMethod($payment['payment_method']) !== 'EFECTIVO' || empty($payment['cash_envelope_id'])) {
        return;
    }

    $service = trim((string) ($payment['service_snapshot'] ?? ''));
    if ($service === '') {
        $service = trim(($payment['type'] ?? '') . (($payment['subtype'] ?? '') !== '' ? ' - ' . $payment['subtype'] : ''));
    }
    $client = trim((string) ($payment['client_name_snapshot'] ?? '')) ?: ($payment['client_name'] ?? null);

    $stmt = $db->prepare("INSERT INTO financial_envelope_movements
        (envelope_id, movement_date, movement_type, amount, concept, application_id, client_name, service_name, reference, source_type, source_id, created_by, notes)
        VALUES (?, ?, 'ENTRADA', ?, ?, ?, ?, ?, ?, 'PAGO', ?, ?, ?)");
    $stmt->execute([
        $payment['cash_envelope_id'],
        $payment['payment_date'],
        $payment['amount'],
        'Pago de cliente',
        $payment['application_id'],
        $client,
        $service,
        $payment['reference'],
        $paymentId,
        $payment['registered_by'],
        $payment['notes']
    ]);
}

function financialSyncExpenseCashMovement(PDO $db, $expenseId) {
    $stmt = $db->prepare("SELECT * FROM financial_expenses WHERE id = ? LIMIT 1");
    $stmt->execute([$expenseId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$expense) return;

    $db->prepare("DELETE FROM financial_envelope_movements WHERE source_type='EGRESO' AND source_id=?")->execute([$expenseId]);

    if (financialNormalizePaymentMethod($expense['payment_method'] ?? '') !== 'EFECTIVO' || empty($expense['cash_envelope_id'])) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO financial_envelope_movements
        (envelope_id, movement_date, movement_type, amount, concept, reference, source_type, source_id, created_by, notes)
        VALUES (?, ?, 'SALIDA', ?, ?, ?, 'EGRESO', ?, ?, ?)");
    $stmt->execute([
        $expense['cash_envelope_id'],
        $expense['expense_date'],
        $expense['amount'],
        $expense['concept'],
        $expense['reference'] ?? null,
        $expenseId,
        $expense['created_by'],
        $expense['notes']
    ]);
}

function financialSyncAdvisorIncomeCashMovement(PDO $db, $recordId) {
    $stmt = $db->prepare("SELECT ir.*, ic.income_type
        FROM advisor_income_records ir
        INNER JOIN advisor_income_catalog ic ON ic.id = ir.income_type_id
        WHERE ir.id = ? LIMIT 1");
    $stmt->execute([$recordId]);
    $income = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$income) return;

    $db->prepare("DELETE FROM financial_envelope_movements WHERE source_type='INGRESO_ADICIONAL' AND source_id=?")->execute([$recordId]);

    if (financialNormalizePaymentMethod($income['payment_method'] ?? '') !== 'EFECTIVO' || empty($income['cash_envelope_id'])) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO financial_envelope_movements
        (envelope_id, movement_date, movement_type, amount, concept, application_id, client_name, service_name, reference, source_type, source_id, created_by, notes)
        VALUES (?, DATE(?), 'ENTRADA', ?, ?, ?, ?, ?, ?, 'INGRESO_ADICIONAL', ?, ?, ?)");
    $stmt->execute([
        $income['cash_envelope_id'],
        $income['income_datetime'],
        $income['amount'],
        $income['income_type'],
        $income['application_id'] ?: null,
        $income['client_name'] ?: null,
        $income['service_name'] ?: $income['income_type'],
        $income['reference'] ?: null,
        $recordId,
        $income['created_by'],
        $income['note']
    ]);
}


function incomeControlTablesAvailable(PDO $db): bool {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('income_catalog','income_sales','income_payments','income_payment_concepts')");
        return (int)$stmt->fetchColumn() === 4;
    } catch (Throwable $e) {
        return false;
    }
}

function incomeGetSaleTotals(PDO $db, int $saleId): array {
    $stmt = $db->prepare("SELECT s.total_amount,
        COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) validated_paid,
        COALESCE(SUM(CASE WHEN p.validation_status='PENDIENTE' THEN p.amount ELSE 0 END),0) pending_validation
        FROM income_sales s LEFT JOIN income_payments p ON p.sale_id=s.id
        WHERE s.id=? GROUP BY s.id,s.total_amount");
    $stmt->execute([$saleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_amount'=>0,'validated_paid'=>0,'pending_validation'=>0];
    $total=(float)$row['total_amount'];
    $paid=(float)$row['validated_paid'];
    return [
        'total'=>$total,
        'paid'=>$paid,
        'pending_validation'=>(float)$row['pending_validation'],
        'balance'=>max(0,$total-$paid),
        'progress'=>$total>0?min(100,($paid/$total)*100):0,
    ];
}

function incomeRefreshApplicationFinancialStatus(PDO $db, int $applicationId): void {
    if (!incomeControlTablesAvailable($db)) return;

    $stmt=$db->prepare("SELECT
        COALESCE(SUM(CASE WHEN s.financial_status<>'CANCELADO' AND s.approval_status='VALIDADA' THEN s.total_amount ELSE 0 END),0) total_sales,
        COALESCE((SELECT SUM(p.amount) FROM income_payments p
          INNER JOIN income_sales sx ON sx.id=p.sale_id
          WHERE sx.application_id=? AND sx.financial_status<>'CANCELADO'
            AND sx.approval_status='VALIDADA'
            AND p.validation_status='VALIDADO'),0) total_paid
        FROM income_sales s WHERE s.application_id=?");
    $stmt->execute([$applicationId,$applicationId]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_sales'=>0,'total_paid'=>0];
    $total=(float)$row['total_sales']; $paid=(float)$row['total_paid']; $balance=max(0,$total-$paid);

    if($paid<=0.009)$status=FINANCIAL_PENDIENTE;
    elseif($balance>0.009)$status=FINANCIAL_PARCIAL;
    else $status=FINANCIAL_PAGADO;

    $stmt=$db->prepare("INSERT INTO financial_status(application_id,total_costs,total_paid,balance,status)
      VALUES(?,?,?,?,?)
      ON DUPLICATE KEY UPDATE total_costs=VALUES(total_costs),total_paid=VALUES(total_paid),balance=VALUES(balance),status=VALUES(status)");
    $stmt->execute([$applicationId,$total,$paid,$balance,$status]);
}

function incomeRefreshSaleStatus(PDO $db, int $saleId): void {
    $totals=incomeGetSaleTotals($db,$saleId);
    if($totals['paid']<=0.009)$status='PENDIENTE';
    elseif($totals['balance']>0.009)$status='PARCIAL';
    else $status='LIQUIDADO';

    $db->prepare("UPDATE income_sales SET financial_status=?,updated_at=NOW() WHERE id=? AND financial_status<>'CANCELADO'")
       ->execute([$status,$saleId]);

    $stmt=$db->prepare("SELECT application_id FROM income_sales WHERE id=? LIMIT 1");
    $stmt->execute([$saleId]);
    $applicationId=(int)$stmt->fetchColumn();
    if($applicationId>0) incomeRefreshApplicationFinancialStatus($db,$applicationId);
}

function incomeSyncValidatedPaymentToLegacy(PDO $db, int $incomePaymentId): ?int {
    $stmt=$db->prepare("SELECT p.*,s.client_name_snapshot,s.client_folio_snapshot,s.service_name_snapshot,s.quick_client_id
      FROM income_payments p INNER JOIN income_sales s ON s.id=p.sale_id
      WHERE p.id=? LIMIT 1");
    $stmt->execute([$incomePaymentId]);
    $p=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$p || $p['validation_status']!=='VALIDADO') return null;

    // Siempre sincronizar las tres cuentas operativas.
    if (function_exists('financeSyncValidatedIncomePayment')) {
        financeSyncValidatedIncomePayment($db, $incomePaymentId);
    }

    // Las ventas rápidas no tienen una application formal, por lo que no generan
    // registro legacy en payments/financial_status.
    $applicationId = (int)($p['application_id'] ?? 0);
    if ($applicationId <= 0) {
        incomeRefreshSaleStatus($db,(int)$p['sale_id']);
        return null;
    }

    if(!empty($p['legacy_payment_id'])) {
        incomeRefreshSaleStatus($db,(int)$p['sale_id']);
        return (int)$p['legacy_payment_id'];
    }

    $sourceKey='INCOME-CONTROL-'.$incomePaymentId;
    $stmt=$db->prepare("SELECT id FROM payments WHERE source_key=? LIMIT 1");
    $stmt->execute([$sourceKey]);
    $legacyId=(int)($stmt->fetchColumn()?:0);

    if($legacyId<=0){
        $stmt=$db->prepare("INSERT INTO payments
          (application_id,amount,payment_method,cash_envelope_id,client_name_snapshot,service_snapshot,
           payment_group_key,source_key,reference,notes,registered_by,payment_date)
          VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
          $applicationId,$p['amount'],$p['payment_method'],$p['cash_envelope_id']?:null,
          $p['client_name_snapshot'],$p['service_name_snapshot'],'INCOME-SALE-'.$p['sale_id'],
          $sourceKey,$p['reference']?:null,$p['notes']?:null,$p['registered_by'],$p['payment_date']
        ]);
        $legacyId=(int)$db->lastInsertId();
    }

    $db->prepare("UPDATE income_payments SET legacy_payment_id=? WHERE id=?")->execute([$legacyId,$incomePaymentId]);

    // Se conserva la sincronización legacy de efectivo cuando exista un sobre/caja,
    // pero el saldo operativo V5 ya vive en finance_accounts.
    financialSyncPaymentCashMovement($db,$legacyId);
    incomeRefreshSaleStatus($db,(int)$p['sale_id']);
    return $legacyId;
}


/**
 * ============================================================
 * V5 - Tres cuentas operativas: Efectivo, Terminal, Transferencia
 * ============================================================
 */
function financeAccountCodeFromPaymentMethod($method): ?string {
    $method = financialNormalizePaymentMethod($method);
    if ($method === 'EFECTIVO') return 'EFECTIVO';
    if ($method === 'TERMINAL_TARJETA') return 'TERMINAL';
    if ($method === 'TRANSFERENCIA') return 'TRANSFERENCIA';
    return null;
}

function financeAccountId(PDO $db, string $code): ?int {
    try {
        $stmt=$db->prepare("SELECT id FROM finance_accounts WHERE code=? AND is_active=1 LIMIT 1");
        $stmt->execute([$code]);
        $id=(int)($stmt->fetchColumn()?:0);
        return $id>0?$id:null;
    } catch(Throwable $e) {
        return null;
    }
}

function financeSyncValidatedIncomePayment(PDO $db, int $paymentId): void {
    try {
        $stmt=$db->prepare("
            SELECT p.*,s.client_name_snapshot,s.client_folio_snapshot,s.service_name_snapshot,
                   pc.concept AS payment_concept
            FROM income_payments p
            INNER JOIN income_sales s ON s.id=p.sale_id
            LEFT JOIN income_payment_concepts pc ON pc.code=p.payment_concept_code
            WHERE p.id=? LIMIT 1
        ");
        $stmt->execute([$paymentId]);
        $p=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$p) return;

        // Si el pago deja de estar validado, retirar su movimiento de cuenta.
        if($p['validation_status']!=='VALIDADO'){
            $db->prepare("DELETE FROM finance_account_movements WHERE source_type='INCOME_PAYMENT' AND source_id=?")
               ->execute([$paymentId]);
            return;
        }

        $code=financeAccountCodeFromPaymentMethod($p['payment_method']);
        if(!$code) return;
        $accountId=financeAccountId($db,$code);
        if(!$accountId) return;

        $db->prepare("DELETE FROM finance_account_movements WHERE source_type='INCOME_PAYMENT' AND source_id=?")
           ->execute([$paymentId]);

        $stmt=$db->prepare("
            INSERT INTO finance_account_movements
              (account_id,movement_date,movement_type,amount,concept,client_name,client_folio,
               service_name,reference,source_type,source_id,created_by)
            VALUES (?,?, 'INGRESO', ?,?,?,?,?,?,'INCOME_PAYMENT',?,?)
        ");
        $stmt->execute([
            $accountId,
            $p['payment_date'],
            $p['amount'],
            $p['payment_concept'] ?: 'Pago de cliente',
            $p['client_name_snapshot'],
            $p['client_folio_snapshot'],
            $p['service_name_snapshot'],
            $p['reference'] ?: null,
            $paymentId,
            $p['registered_by']
        ]);
    } catch(Throwable $e) {
        error_log('No se pudo sincronizar ingreso a cuenta V5: '.$e->getMessage());
    }
}

function financeSyncExpenseAccountMovement(PDO $db, int $expenseId): void {
    try {
        $stmt=$db->prepare("SELECT * FROM financial_expenses WHERE id=? LIMIT 1");
        $stmt->execute([$expenseId]);
        $e=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$e) return;

        $accountId=financeAccountId($db,'EFECTIVO');
        if(!$accountId) return;

        $db->prepare("DELETE FROM finance_account_movements WHERE source_type='EXPENSE' AND source_id=?")
           ->execute([$expenseId]);

        $stmt=$db->prepare("
            INSERT INTO finance_account_movements
              (account_id,movement_date,movement_type,amount,concept,reference,source_type,source_id,created_by)
            VALUES (?,?,'EGRESO',?,?,?,'EXPENSE',?,?)
        ");
        $stmt->execute([
            $accountId,
            $e['expense_date'],
            $e['amount'],
            $e['concept'],
            $e['reference'] ?: $e['expense_folio'],
            $expenseId,
            $e['created_by']
        ]);
    } catch(Throwable $e) {
        error_log('No se pudo sincronizar egreso a cuenta V5: '.$e->getMessage());
    }
}

function financeGetAccountBalances(PDO $db, ?string $throughDate=null): array {
    $result=[
        'EFECTIVO'=>0.0,
        'TERMINAL'=>0.0,
        'TRANSFERENCIA'=>0.0,
        'TOTAL'=>0.0,
    ];

    try {
        $dateSql = $throughDate ? " AND m.movement_date<=?" : "";
        $sql="SELECT a.code,a.opening_balance,
                    COALESCE(SUM(CASE
                      WHEN m.movement_type IN ('INGRESO','AJUSTE_ENTRADA') THEN m.amount
                      WHEN m.movement_type IN ('EGRESO','AJUSTE_SALIDA') THEN -m.amount
                      ELSE 0 END),0) movement_balance
              FROM finance_accounts a
              LEFT JOIN finance_account_movements m ON m.account_id=a.id".$dateSql."
              WHERE a.is_active=1
              GROUP BY a.id,a.code,a.opening_balance";
        $stmt=$db->prepare($sql);
        $stmt->execute($throughDate?[$throughDate]:[]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
            $value=(float)$row['opening_balance']+(float)$row['movement_balance'];
            $result[$row['code']]=$value;
        }
    } catch(Throwable $e) {
        error_log('No se pudieron obtener saldos de cuentas V5: '.$e->getMessage());
    }

    $result['TOTAL']=$result['EFECTIVO']+$result['TERMINAL']+$result['TRANSFERENCIA'];
    return $result;
}

function financeCashAvailable(PDO $db, ?string $throughDate=null): float {
    $balances=financeGetAccountBalances($db,$throughDate);
    return (float)($balances['EFECTIVO']??0);
}


function parseCurrencyInput($value): float {
    if ($value === null) return 0.0;
    $raw = trim((string)$value);
    if ($raw === '') return 0.0;
    $raw = str_replace(['$', ',', ' ', "\xc2\xa0"], '', $raw);
    return is_numeric($raw) ? (float)$raw : 0.0;
}

function financePaymentMethodAccountLabel($method): string {
    $code=financeAccountCodeFromPaymentMethod($method);
    if($code==='EFECTIVO') return 'Efectivo / Ahorro';
    if($code==='TERMINAL') return 'Terminal';
    if($code==='TRANSFERENCIA') return 'Transferencia';
    return 'No aplicable';
}

/**
 * Ventas rechazadas que requieren atención de la asesora actual.
 * Se usa como aviso visual en el menú lateral.
 */
function getAdvisorRejectedSalesCount() {
    try {
        if (($_SESSION['user_role'] ?? '') !== ROLE_ASESOR) return 0;
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return 0;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM income_sales WHERE advisor_id=? AND approval_status='RECHAZADA'");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
