<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class WhatsAppController extends BaseController {
    private function requireWhatsAppAccess() {
        $this->requireRole([ROLE_ADMIN, ROLE_ASESOR]);
    }

    private function isAdmin() {
        return $this->getUserRole() === ROLE_ADMIN;
    }

    private function csrfToken() {
        if (empty($_SESSION['whatsapp_csrf'])) {
            $_SESSION['whatsapp_csrf'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['whatsapp_csrf'];
    }

    private function verifyCsrf() {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $known = (string) ($_SESSION['whatsapp_csrf'] ?? '');
        if ($token === '' || $known === '' || !hash_equals($known, $token)) {
            http_response_code(419);
            die('La sesión del formulario expiró. Recargue la página e intente nuevamente.');
        }
    }

    private function setFlash($type, $message) {
        $_SESSION['whatsapp_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash() {
        $flash = $_SESSION['whatsapp_flash'] ?? null;
        unset($_SESSION['whatsapp_flash']);
        return $flash;
    }

    private function serviceName($code) {
        if (!$code) return 'Sin servicio seleccionado';
        try {
            $stmt = $this->db->prepare('SELECT name FROM bot_service_catalog WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
            $row = $stmt->fetch();
            return $row['name'] ?? $code;
        } catch (Throwable $e) {
            return $code;
        }
    }

    private function conversationFilters($filter = '', $search = '') {
        $role = $this->getUserRole();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $where = [];
        $params = [];

        if ($role === ROLE_ASESOR) {
            // V3.4: cada asesora ve únicamente su cartera asignada.
            $where[] = "c.assigned_agent_id = ? AND c.status <> 'CLOSED'";
            $params[] = $userId;
            if ($filter === 'waiting') {
                $where[] = "c.status = 'WAITING_HUMAN'";
            } elseif ($filter === 'mine') {
                // El filtro base ya limita a la asesora actual.
            }
        } else {
            if ($filter === 'bot') $where[] = "c.mode = 'BOT' AND c.status = 'OPEN'";
            elseif ($filter === 'waiting') $where[] = "c.status = 'WAITING_HUMAN'";
            elseif ($filter === 'active') $where[] = "c.status = 'IN_PROGRESS'";
            elseif ($filter === 'closed') $where[] = "c.status = 'CLOSED'";
        }

        $search = trim((string)$search);
        if ($search !== '') {
            $digits = preg_replace('/\\D+/', '', $search);
            $where[] = "(bc.profile_name LIKE ? OR bc.phone LIKE ? OR bc.wa_id LIKE ? OR bsc.name LIKE ?"
                . ($digits !== '' ? " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(bc.phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') LIKE ? OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(bc.wa_id, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?" : '')
                . ")";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
            if ($digits !== '') {
                $digitsLike = '%' . $digits . '%';
                array_push($params, $digitsLike, $digitsLike);
            }
        }

        return [$where, $params];
    }

    private function countConversations($filter = '', $search = '') {
        [$where, $params] = $this->conversationFilters($filter, $search);
        $sql = "SELECT COUNT(*) total
                  FROM bot_conversations c
                  INNER JOIN bot_contacts bc ON bc.id = c.contact_id
                  LEFT JOIN bot_service_catalog bsc ON bsc.code = c.service_code";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    private function listConversations($filter = '', $search = '', $limit = 60, $offset = 0) {
        [$where, $params] = $this->conversationFilters($filter, $search);
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        $sql = "SELECT c.id, c.contact_id, c.service_code, c.mode, c.status, c.assigned_agent_id,
                       c.assigned_agent_name, c.handoff_reason, c.handoff_requested_at, c.handoff_taken_at,
                       c.last_message_at, c.last_customer_message_at, c.last_staff_message_at, c.closed_at,
                       bc.wa_id, bc.phone, bc.profile_name, bc.contact_type, bc.lead_status,
                       bc.is_existing_client, bc.application_id,
                       bsc.name AS service_name,
                       (SELECT bm.message_text FROM bot_messages bm WHERE bm.contact_id = c.contact_id ORDER BY bm.id DESC LIMIT 1) AS last_message_text
                  FROM bot_conversations c
                  INNER JOIN bot_contacts bc ON bc.id = c.contact_id
                  LEFT JOIN bot_service_catalog bsc ON bsc.code = c.service_code";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY
                  CASE c.status WHEN 'WAITING_HUMAN' THEN 0 WHEN 'IN_PROGRESS' THEN 1 WHEN 'OPEN' THEN 2 ELSE 3 END,
                  COALESCE(c.last_message_at, c.updated_at) DESC
                  LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function canOpenConversation($conversation) {
        if (!$conversation) return false;
        if ($this->isAdmin()) return true;
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return (int)$conversation['assigned_agent_id'] === $userId
            && $conversation['status'] !== 'CLOSED';
    }

    private function getConversation($id) {
        $stmt = $this->db->prepare("SELECT c.*, bc.wa_id, bc.phone, bc.profile_name, bc.contact_type, bc.lead_status,
                                           bc.is_existing_client, bc.client_user_id, bc.application_id,
                                           bsc.name AS service_name
                                      FROM bot_conversations c
                                      INNER JOIN bot_contacts bc ON bc.id = c.contact_id
                                      LEFT JOIN bot_service_catalog bsc ON bsc.code = c.service_code
                                     WHERE c.id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $conversation = $stmt->fetch();
        if (!$this->canOpenConversation($conversation)) return null;
        return $conversation;
    }

    private function parseMessageRow($row) {
        $meta = [];
        if (!empty($row['payload_json'])) {
            $payload = json_decode($row['payload_json'], true);
            if (is_array($payload) && isset($payload['_crm_meta']) && is_array($payload['_crm_meta'])) {
                $meta = $payload['_crm_meta'];
            }
        }

        $senderType = $meta['sender_type'] ?? ($row['direction'] === 'IN' ? 'USER' : 'BOT');
        $senderName = $meta['sender_name'] ?? null;
        if (!$senderName) {
            if ($senderType === 'USER') $senderName = 'Cliente';
            elseif ($senderType === 'BOT') $senderName = 'Bot';
            elseif ($senderType === 'ADMIN') $senderName = 'Administrador';
            else $senderName = 'Asesora';
        }

        return [
            'id' => (int)$row['id'],
            'direction' => $row['direction'],
            'message_type' => $row['message_type'],
            'message_text' => $row['message_text'] ?? '',
            'created_at' => $row['created_at'],
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'sender_role' => $meta['sender_role'] ?? null,
            'sender_user_id' => isset($meta['sender_user_id']) ? (int)$meta['sender_user_id'] : null,
        ];
    }

    private function getMessages($contactId) {
        $stmt = $this->db->prepare("SELECT id, direction, message_type, message_text, payload_json, created_at
                                      FROM bot_messages
                                     WHERE contact_id = ?
                                     ORDER BY id ASC
                                     LIMIT 600");
        $stmt->execute([(int)$contactId]);
        return array_map([$this, 'parseMessageRow'], $stmt->fetchAll());
    }

    private function getEvents($conversationId) {
        $stmt = $this->db->prepare("SELECT id, event_type, actor_user_id, actor_name, actor_role, detail, created_at
                                      FROM bot_conversation_events
                                     WHERE conversation_id = ?
                                     ORDER BY id DESC
                                     LIMIT 100");
        $stmt->execute([(int)$conversationId]);
        return $stmt->fetchAll();
    }

    private function getProfileAnswers($contactId, $serviceCode = null) {
        // Mostramos todos los perfilamientos del contacto para conservar visibles
        // los trámites anteriores cuando el cliente agrega un servicio nuevo.
        $stmt = $this->db->prepare("SELECT bpa.service_code, bpa.question_key, bpa.question_label,
                                           bpa.answer_value, bpa.answer_label, bpa.updated_at,
                                           COALESCE(bsc.name, bpa.service_code) AS service_name,
                                           COALESCE(bsc.sort_order, 999) AS service_sort
                                      FROM bot_profile_answers bpa
                                      LEFT JOIN bot_service_catalog bsc ON bsc.code = bpa.service_code
                                     WHERE bpa.contact_id = ?
                                       AND bpa.service_code <> 'CONTACTO'
                                     ORDER BY service_sort ASC, bpa.service_code ASC, bpa.id ASC");
        $stmt->execute([(int)$contactId]);
        return $stmt->fetchAll();
    }


    private function formalizationSchemaReady() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'bot_application_links'
            ");
            return (int)($stmt->fetch()['total'] ?? 0) === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function normalizeSearchText($value) {
        $value = mb_strtolower(trim((string)$value), 'UTF-8');
        return strtr($value, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n',
        ]);
    }

    private function serviceMatchesForm($serviceCode, $form) {
        $type = $this->normalizeSearchText($form['type'] ?? '');
        $text = $this->normalizeSearchText(
            ($form['name'] ?? '') . ' ' . ($form['subtype'] ?? '')
        );

        switch ($serviceCode) {
            case '01_VISA_USA_PRIMERA_VEZ':
                return $type === 'visa'
                    && strpos($text, 'americana') !== false
                    && strpos($text, 'primera') !== false;
            case '02_VISA_USA_RENOVACION':
                return $type === 'visa'
                    && strpos($text, 'americana') !== false
                    && strpos($text, 'renov') !== false;
            case '03_PASAPORTE_MEXICANO':
                return $type === 'pasaporte'
                    && strpos($text, 'mexican') !== false;
            case '04_PASAPORTE_AMERICANO':
                return $type === 'pasaporte'
                    && (strpos($text, 'american') !== false || strpos($text, 'estadounid') !== false);
            case '05_VISA_CANADA':
                return $type === 'visa'
                    && strpos($text, 'canad') !== false;
            case '06_ETA_CANADA':
                return ($type === 'eta' || strpos($text, 'eta') !== false)
                    && strpos($text, 'canad') !== false;
            case '07_VISA_AUSTRALIA':
                return $type === 'visa'
                    && strpos($text, 'australia') !== false;
            case '08_VISA_CHINA':
                return $type === 'visa'
                    && strpos($text, 'china') !== false;
            default:
                return false;
        }
    }

    private function getCompatibleForms($serviceCode) {
        $stmt = $this->db->query("
            SELECT id, name, type, subtype, version, fields_json
            FROM forms
            WHERE is_published = 1
            ORDER BY type, name
        ");
        $forms = [];
        foreach ($stmt->fetchAll() as $form) {
            if ($this->serviceMatchesForm($serviceCode, $form)) {
                $forms[] = $form;
            }
        }
        return $forms;
    }

    private function getServiceModeHint($contactId, $serviceCode) {
        try {
            $stmt = $this->db->prepare("
                SELECT answer_value, answer_label
                FROM bot_profile_answers
                WHERE contact_id = ? AND service_code = ?
                  AND LOWER(question_key) IN ('document_situation','modalidad','tipo_tramite','passport_situation')
                ORDER BY updated_at DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute([(int)$contactId, $serviceCode]);
            $row = $stmt->fetch();
            if (!$row) return '';

            $text = $this->normalizeSearchText(
                ($row['answer_value'] ?? '') . ' ' . ($row['answer_label'] ?? '')
            );
            if (strpos($text, 'renov') !== false) return 'RENOVACION';
            if (strpos($text, 'first') !== false || strpos($text, 'primera') !== false) return 'PRIMERA_VEZ';
        } catch (Throwable $e) {}
        return '';
    }

    private function getFormalizationServices($conversation) {
        if (!$conversation) return [];

        $services = [];
        try {
            $stmt = $this->db->prepare("
                SELECT bpa.service_code,
                       COALESCE(bsc.name, bpa.service_code) AS service_name,
                       COUNT(*) AS answer_count,
                       MAX(bpa.updated_at) AS last_profile_at
                FROM bot_profile_answers bpa
                LEFT JOIN bot_service_catalog bsc ON bsc.code = bpa.service_code
                WHERE bpa.contact_id = ?
                  AND bpa.service_code <> 'CONTACTO'
                GROUP BY bpa.service_code, bsc.name
                ORDER BY MAX(bpa.updated_at) DESC
            ");
            $stmt->execute([(int)$conversation['contact_id']]);
            foreach ($stmt->fetchAll() as $row) {
                $services[$row['service_code']] = $row;
            }
        } catch (Throwable $e) {}

        // Los perfilamientos estructurados V3 pueden contener información
        // comercial adicional (por ejemplo, si el cliente indicó que desea continuar).
        try {
            $stmt = $this->db->prepare("
                SELECT bpp.service_code,
                       COALESCE(bsc.name, bpp.service_code) AS service_name,
                       bpp.interes_continuar,
                       bpp.completed_at,
                       bpp.updated_at
                FROM bot_prospect_profiles bpp
                LEFT JOIN bot_service_catalog bsc ON bsc.code = bpp.service_code
                WHERE bpp.contact_id = ?
            ");
            $stmt->execute([(int)$conversation['contact_id']]);
            foreach ($stmt->fetchAll() as $row) {
                if (!isset($services[$row['service_code']])) {
                    $services[$row['service_code']] = [
                        'service_code' => $row['service_code'],
                        'service_name' => $row['service_name'],
                        'answer_count' => 0,
                        'last_profile_at' => $row['updated_at'],
                    ];
                }
                $services[$row['service_code']]['commercial_interest'] = $row['interes_continuar'];
                $services[$row['service_code']]['profile_completed_at'] = $row['completed_at'];
            }
        } catch (Throwable $e) {}

        $currentCode = trim((string)($conversation['service_code'] ?? ''));
        if ($currentCode !== ''
            && !in_array($currentCode, ['10_CLIENTE_SEGUIMIENTO','11_ASESOR','CONTACTO'], true)
            && !isset($services[$currentCode])) {
            $services[$currentCode] = [
                'service_code' => $currentCode,
                'service_name' => $this->serviceName($currentCode),
                'answer_count' => 0,
                'last_profile_at' => null,
            ];
        }

        $links = [];
        if ($this->formalizationSchemaReady()) {
            try {
                $stmt = $this->db->prepare("
                    SELECT bal.service_code, bal.application_id, bal.form_id,
                           a.folio, a.status, a.type, a.subtype, a.created_at
                    FROM bot_application_links bal
                    INNER JOIN applications a ON a.id = bal.application_id
                    WHERE bal.contact_id = ?
                ");
                $stmt->execute([(int)$conversation['contact_id']]);
                foreach ($stmt->fetchAll() as $row) {
                    $links[$row['service_code']] = $row;
                }
            } catch (Throwable $e) {}
        }

        $result = [];
        foreach ($services as $serviceCode => $service) {
            $service['link'] = $links[$serviceCode] ?? null;
            $service['compatibility_note'] = null;

            $forms = $service['link'] ? [] : $this->getCompatibleForms($serviceCode);
            if (!$service['link'] && in_array($serviceCode, ['03_PASAPORTE_MEXICANO','04_PASAPORTE_AMERICANO','05_VISA_CANADA'], true)) {
                $modeHint = $this->getServiceModeHint((int)$conversation['contact_id'], $serviceCode);
                if ($modeHint !== '') {
                    $filtered = [];
                    foreach ($forms as $form) {
                        $text = $this->normalizeSearchText(($form['name'] ?? '') . ' ' . ($form['subtype'] ?? ''));
                        $matches = $modeHint === 'RENOVACION'
                            ? strpos($text, 'renov') !== false
                            : strpos($text, 'primera') !== false;
                        if ($matches) $filtered[] = $form;
                    }
                    if ($filtered) {
                        $forms = $filtered;
                    } elseif ($forms) {
                        $forms = [];
                        $service['compatibility_note'] = $modeHint === 'RENOVACION'
                            ? 'El bot identificó una renovación, pero no hay un formulario de renovación publicado para este servicio.'
                            : 'El bot identificó primera vez, pero no hay un formulario de primera vez publicado para este servicio.';
                    }
                }
            }

            $service['forms'] = $forms;
            $result[] = $service;
        }
        return $result;
    }

    private function profileServiceExists($contactId, $serviceCode) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM bot_profile_answers
                WHERE contact_id = ? AND service_code = ?
            ");
            $stmt->execute([(int)$contactId, $serviceCode]);
            if ((int)$stmt->fetchColumn() > 0) return true;
        } catch (Throwable $e) {}

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM bot_prospect_profiles
                WHERE contact_id = ? AND service_code = ?
            ");
            $stmt->execute([(int)$contactId, $serviceCode]);
            if ((int)$stmt->fetchColumn() > 0) return true;
        } catch (Throwable $e) {}

        return false;
    }

    private function getExistingApplicationLink($contactId, $serviceCode) {
        if (!$this->formalizationSchemaReady()) return null;
        $stmt = $this->db->prepare("
            SELECT bal.*, a.folio, a.status, a.form_id AS application_form_id
            FROM bot_application_links bal
            INNER JOIN applications a ON a.id = bal.application_id
            WHERE bal.contact_id = ? AND bal.service_code = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$contactId, $serviceCode]);
        return $stmt->fetch() ?: null;
    }

    private function getCapturedContactName($contactId, $fallback = '') {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(NULLIF(answer_label,''), answer_value) AS full_name
                FROM bot_profile_answers
                WHERE contact_id = ?
                  AND service_code = 'CONTACTO'
                  AND question_key = 'NOMBRE_COMPLETO'
                ORDER BY updated_at DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute([(int)$contactId]);
            $name = trim((string)($stmt->fetchColumn() ?: ''));
            if ($name !== '') return $name;
        } catch (Throwable $e) {}

        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(NULLIF(answer_label,''), answer_value) AS full_name
                FROM bot_profile_answers
                WHERE contact_id = ?
                  AND UPPER(question_key) IN ('FULL_NAME','NOMBRE_COMPLETO','NOMBRE_SOLICITANTE')
                ORDER BY updated_at DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute([(int)$contactId]);
            $name = trim((string)($stmt->fetchColumn() ?: ''));
            if ($name !== '') return $name;
        } catch (Throwable $e) {}

        return trim((string)$fallback);
    }

    private function splitFullName($fullName) {
        $parts = preg_split('/\s+/u', trim((string)$fullName), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($parts);

        if ($count <= 1) {
            return [
                'nombre' => $parts[0] ?? '',
                'apellido_paterno' => '',
                'apellido_materno' => '',
                'apellidos' => '',
            ];
        }

        if ($count === 2) {
            return [
                'nombre' => $parts[0],
                'apellido_paterno' => $parts[1],
                'apellido_materno' => '',
                'apellidos' => $parts[1],
            ];
        }

        if ($count === 3) {
            return [
                'nombre' => $parts[0],
                'apellido_paterno' => $parts[1],
                'apellido_materno' => $parts[2],
                'apellidos' => $parts[1] . ' ' . $parts[2],
            ];
        }

        $apellidoMaterno = array_pop($parts);
        $apellidoPaterno = array_pop($parts);
        $nombre = implode(' ', $parts);
        return [
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'apellidos' => trim($apellidoPaterno . ' ' . $apellidoMaterno),
        ];
    }

    private function loadBotPrefill($conversation, $serviceCode) {
        $contactId = (int)$conversation['contact_id'];
        $answers = [];

        try {
            $stmt = $this->db->prepare("
                SELECT question_key, question_label, answer_value, answer_label, updated_at
                FROM bot_profile_answers
                WHERE contact_id = ? AND service_code = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$contactId, $serviceCode]);
            foreach ($stmt->fetchAll() as $row) {
                $answers[] = [
                    'key' => $row['question_key'],
                    'label' => $row['question_label'],
                    'value' => $row['answer_value'],
                    'answer' => $row['answer_label'] ?: $row['answer_value'],
                    'updated_at' => $row['updated_at'],
                ];
            }
        } catch (Throwable $e) {}

        $structured = null;
        $applicants = [];
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM bot_prospect_profiles
                WHERE contact_id = ? AND service_code = ?
                LIMIT 1
            ");
            $stmt->execute([$contactId, $serviceCode]);
            $structured = $stmt->fetch() ?: null;

            if ($structured && !empty($structured['id'])) {
                $stmt = $this->db->prepare("
                    SELECT applicant_number, age, occupation, previous_visa_status,
                           visa_expiry_text, previous_visa_grant_age_text, needs_advisor_review
                    FROM bot_profile_applicants
                    WHERE profile_id = ?
                    ORDER BY applicant_number
                ");
                $stmt->execute([(int)$structured['id']]);
                $applicants = $stmt->fetchAll();
            }
        } catch (Throwable $e) {
            $structured = null;
            $applicants = [];
        }

        $fullName = $this->getCapturedContactName($contactId, $conversation['profile_name'] ?? '');
        $email = '';
        foreach ($answers as $answer) {
            $key = strtoupper(trim((string)$answer['key']));
            if ($email === '' && in_array($key, ['EMAIL','CORREO','CORREO_ELECTRONICO'], true)) {
                $email = trim((string)$answer['answer']);
            }
        }

        return [
            'source' => 'WhatsApp / Bot',
            'contact_id' => $contactId,
            'conversation_id' => (int)$conversation['id'],
            'service_code' => $serviceCode,
            'service_name' => $this->serviceName($serviceCode),
            'full_name' => $fullName,
            'phone' => trim((string)($conversation['phone'] ?? '')),
            'email' => $email,
            'assigned_agent_id' => (int)($conversation['assigned_agent_id'] ?? 0),
            'assigned_agent_name' => trim((string)($conversation['assigned_agent_name'] ?? '')),
            'answers' => $answers,
            'structured_profile' => $structured,
            'applicants' => $applicants,
            'captured_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function getAnswerByKey($prefill, $keys) {
        $keys = array_map('strtoupper', $keys);
        foreach (($prefill['answers'] ?? []) as $answer) {
            if (in_array(strtoupper((string)($answer['key'] ?? '')), $keys, true)) {
                return trim((string)($answer['answer'] ?? $answer['value'] ?? ''));
            }
        }
        return '';
    }

    private function buildApplicationData($form, $prefill) {
        $name = $this->splitFullName($prefill['full_name'] ?? '');
        $phone = trim((string)($prefill['phone'] ?? ''));
        if ($phone !== '' && $phone[0] !== '+') $phone = '+' . $phone;

        $data = [
            'nombre' => $name['nombre'],
            'apellidos' => $name['apellidos'],
            'email' => trim((string)($prefill['email'] ?? '')),
            'telefono' => $phone,
            'nombre_cliente' => trim((string)($prefill['full_name'] ?? '')),
            'pago' => '',
            'fecha_cita' => '',
            'origen' => 'WhatsApp / Bot',
            'bot_contact_id' => (int)($prefill['contact_id'] ?? 0),
            'bot_conversation_id' => (int)($prefill['conversation_id'] ?? 0),
            'bot_service_code' => (string)($prefill['service_code'] ?? ''),
        ];

        $fields = json_decode((string)($form['fields_json'] ?? ''), true);
        $fields = is_array($fields) ? ($fields['fields'] ?? []) : [];

        $motivoViaje = '';
        if (!empty($prefill['structured_profile']['motivo_viaje'])) {
            $motivoViaje = trim((string)$prefill['structured_profile']['motivo_viaje']);
        }
        if ($motivoViaje === '') {
            $motivoViaje = $this->getAnswerByKey($prefill, ['MOTIVO_VIAJE','PURPOSE']);
        }

        foreach ($fields as $field) {
            $id = trim((string)($field['id'] ?? ''));
            if ($id === '') continue;
            $label = $this->normalizeSearchText($field['label'] ?? '');

            if (strpos($label, 'apellido paterno') !== false && $name['apellido_paterno'] !== '') {
                $data[$id] = $name['apellido_paterno'];
            } elseif (strpos($label, 'apellido materno') !== false && $name['apellido_materno'] !== '') {
                $data[$id] = $name['apellido_materno'];
            } elseif (
                (preg_match('/^nombres?\b/u', $label) || strpos($label, 'nombres como en pasaporte') !== false)
                && $name['nombre'] !== ''
            ) {
                $data[$id] = $name['nombre'];
            } elseif (
                (strpos($label, 'nombre completo') !== false || strpos($label, 'nombre del solicitante') !== false)
                && !empty($prefill['full_name'])
            ) {
                $data[$id] = $prefill['full_name'];
            } elseif (
                (strpos($label, 'telefono personal') !== false || $label === 'telefono' || $label === 'telefono:')
                && $phone !== ''
            ) {
                $data[$id] = $phone;
            } elseif (
                (strpos($label, 'correo electronico') !== false || $label === 'email')
                && !empty($prefill['email'])
            ) {
                $data[$id] = $prefill['email'];
            } elseif (
                $motivoViaje !== ''
                && strpos($label, 'motivo') !== false
                && strpos($label, 'viaje') !== false
            ) {
                $data[$id] = $motivoViaje;
            }
        }

        return $data;
    }

    private function findLegacyApplicationForService($conversation, $serviceCode) {
        $applicationId = (int)($conversation['application_id'] ?? 0);
        if ($applicationId <= 0) return null;

        $stmt = $this->db->prepare("
            SELECT a.id, a.folio, a.status, a.form_id, f.name, f.type, f.subtype, f.version, f.fields_json
            FROM applications a
            LEFT JOIN forms f ON f.id = a.form_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch();
        if (!$app) return null;

        if (!empty($app['form_id']) && $this->serviceMatchesForm($serviceCode, $app)) {
            return $app;
        }
        return null;
    }

    private function insertApplicationLink($conversation, $serviceCode, $applicationId, $formId, $ownerId, $prefill) {
        $stmt = $this->db->prepare("
            INSERT INTO bot_application_links
              (contact_id, conversation_id, service_code, application_id, form_id,
               assigned_agent_id, created_by, prefill_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              application_id = VALUES(application_id),
              form_id = VALUES(form_id),
              conversation_id = VALUES(conversation_id),
              assigned_agent_id = VALUES(assigned_agent_id),
              prefill_json = COALESCE(prefill_json, VALUES(prefill_json)),
              updated_at = NOW()
        ");
        $stmt->execute([
            (int)$conversation['contact_id'],
            (int)$conversation['id'],
            $serviceCode,
            (int)$applicationId,
            (int)$formId ?: null,
            (int)$ownerId ?: null,
            (int)($_SESSION['user_id'] ?? 0),
            $prefill ? json_encode($prefill, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }


    private function getStats() {
        $stats = ['bot' => 0, 'waiting' => 0, 'active' => 0, 'closed' => 0, 'mine' => 0];

        try {
            if ($this->getUserRole() === ROLE_ASESOR) {
                $userId = (int)($_SESSION['user_id'] ?? 0);

                $stmt = $this->db->prepare("SELECT
                        SUM(CASE WHEN assigned_agent_id = ? AND status = 'WAITING_HUMAN' THEN 1 ELSE 0 END) AS waiting,
                        SUM(CASE WHEN assigned_agent_id = ? AND status <> 'CLOSED' THEN 1 ELSE 0 END) AS mine,
                        SUM(CASE WHEN assigned_agent_id = ? AND status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS active,
                        SUM(CASE WHEN assigned_agent_id = ? AND status = 'OPEN' AND mode = 'BOT' THEN 1 ELSE 0 END) AS bot
                    FROM bot_conversations");
                $stmt->execute([$userId, $userId, $userId, $userId]);
                $row = $stmt->fetch() ?: [];
                $stats['waiting'] = (int)($row['waiting'] ?? 0);
                $stats['mine'] = (int)($row['mine'] ?? 0);
                $stats['active'] = (int)($row['active'] ?? 0);
                $stats['bot'] = (int)($row['bot'] ?? 0);
                return $stats;
            }

            $stmt = $this->db->query("SELECT status, mode, COUNT(*) total FROM bot_conversations GROUP BY status, mode");
            foreach ($stmt->fetchAll() as $row) {
                if ($row['status'] === 'WAITING_HUMAN') $stats['waiting'] += (int)$row['total'];
                elseif ($row['status'] === 'IN_PROGRESS') $stats['active'] += (int)$row['total'];
                elseif ($row['status'] === 'CLOSED') $stats['closed'] += (int)$row['total'];
                elseif ($row['mode'] === 'BOT') $stats['bot'] += (int)$row['total'];
            }
        } catch (Throwable $e) {}

        return $stats;
    }

    private function botApiRequest($action, $conversationId, $extra = []) {
        $url = defined('WHATSAPP_BOT_API_URL') ? trim((string)WHATSAPP_BOT_API_URL) : '';
        $key = defined('WHATSAPP_BOT_API_KEY') ? trim((string)WHATSAPP_BOT_API_KEY) : '';
        if ($url === '' || $key === '' || $key === 'CAMBIAR_ESTA_CLAVE') {
            throw new RuntimeException('Falta configurar WHATSAPP_BOT_API_URL / WHATSAPP_BOT_API_KEY en config/whatsapp.local.php.');
        }

        $payload = array_merge([
            'action' => $action,
            'conversationId' => (int)$conversationId,
            'actorUserId' => (int)($_SESSION['user_id'] ?? 0),
            'actorName' => (string)($_SESSION['user_name'] ?? 'Usuario CRM'),
            'actorRole' => (string)($_SESSION['user_role'] ?? ''),
        ], $extra);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $status = 0;
        $body = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Admin-Key: ' . $key,
                ],
                CURLOPT_POSTFIELDS => $json,
            ]);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($body === false) throw new RuntimeException('No se pudo comunicar con Firebase: ' . $curlError);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nX-Admin-Key: {$key}\r\n",
                    'content' => $json,
                    'timeout' => 20,
                    'ignore_errors' => true,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $status = (int)$m[1];
            if ($body === false) throw new RuntimeException('No se pudo comunicar con Firebase.');
        }

        $decoded = json_decode((string)$body, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['ok'])) {
            $message = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'Error desconocido') : 'Respuesta inválida de Firebase';
            throw new RuntimeException("Firebase respondió HTTP {$status}: {$message}");
        }
        return $decoded;
    }

    public function index() {
        $this->requireWhatsAppAccess();
        $filter = trim((string)($_GET['filter'] ?? ''));
        $search = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($search) > 100) $search = mb_substr($search, 0, 100);

        $perPage = 60;
        $totalConversations = $this->countConversations($filter, $search);
        $totalPages = max(1, (int)ceil($totalConversations / $perPage));
        $page = max(1, (int)($_GET['page'] ?? 1));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $conversations = $this->listConversations($filter, $search, $perPage, $offset);
        $selectedId = (int)($_GET['conversation'] ?? 0);
        if (!$selectedId && !empty($conversations)) $selectedId = (int)$conversations[0]['id'];
        $selected = $selectedId ? $this->getConversation($selectedId) : null;

        $data = [
            'filter' => $filter,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalConversations' => $totalConversations,
            'conversations' => $conversations,
            'selected' => $selected,
            'messages' => $selected ? $this->getMessages($selected['contact_id']) : [],
            'events' => $selected ? $this->getEvents($selected['id']) : [],
            'profileAnswers' => $selected ? $this->getProfileAnswers($selected['contact_id'], $selected['service_code']) : [],
            'formalizationServices' => $selected ? $this->getFormalizationServices($selected) : [],
            'formalizationReady' => $this->formalizationSchemaReady(),
            'stats' => $this->getStats(),
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->pullFlash(),
            'isAdmin' => $this->isAdmin(),
            'currentUserId' => (int)($_SESSION['user_id'] ?? 0),
        ];
        $this->view('whatsapp/index', $data);
    }

    public function conversationJson($id) {
        $this->requireWhatsAppAccess();
        $conversation = $this->getConversation((int)$id);
        if (!$conversation) return $this->json(['ok' => false, 'error' => 'NOT_FOUND'], 404);
        return $this->json([
            'ok' => true,
            'conversation' => $conversation,
            'messages' => $this->getMessages($conversation['contact_id']),
            'events' => $this->getEvents($conversation['id']),
            'profileAnswers' => $this->getProfileAnswers($conversation['contact_id'], $conversation['service_code']),
        ]);
    }

    public function listJson() {
        $this->requireWhatsAppAccess();
        $filter = trim((string)($_GET['filter'] ?? ''));
        $search = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($search) > 100) $search = mb_substr($search, 0, 100);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 60;
        $total = $this->countConversations($filter, $search);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        return $this->json([
            'ok' => true,
            'conversations' => $this->listConversations($filter, $search, $perPage, ($page - 1) * $perPage),
            'stats' => $this->getStats(),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }


    public function createApplication($id) {
        $this->requireWhatsAppAccess();
        $this->verifyCsrf();
        $conversationId = (int)$id;

        try {
            if (!$this->formalizationSchemaReady()) {
                throw new RuntimeException('Falta ejecutar la migración 08_whatsapp_a_solicitudes_v4.sql.');
            }

            $conversation = $this->getConversation($conversationId);
            if (!$conversation) {
                throw new RuntimeException('Conversación no disponible.');
            }

            $serviceCode = trim((string)($_POST['service_code'] ?? ''));
            $formId = (int)($_POST['form_id'] ?? 0);

            if ($serviceCode === '' || in_array($serviceCode, ['CONTACTO','10_CLIENTE_SEGUIMIENTO','11_ASESOR'], true)) {
                throw new RuntimeException('Seleccione un perfilamiento válido para convertir.');
            }

            $isCurrentService = $serviceCode === trim((string)($conversation['service_code'] ?? ''));
            if (!$isCurrentService && !$this->profileServiceExists($conversation['contact_id'], $serviceCode)) {
                throw new RuntimeException('Ese perfilamiento no pertenece a este contacto.');
            }

            $existing = $this->getExistingApplicationLink($conversation['contact_id'], $serviceCode);
            if ($existing) {
                $message = 'Este perfilamiento ya tiene una solicitud: ' . $existing['folio'] . '.';
                $this->setFlash('success', $message);
                $_SESSION['success'] = $message;
                $this->redirect('/solicitudes/ver/' . (int)$existing['application_id']);
            }

            // Compatibilidad con la vinculación antigua bot_contacts.application_id.
            $legacyApp = $this->findLegacyApplicationForService($conversation, $serviceCode);
            if ($legacyApp) {
                $prefill = $this->loadBotPrefill($conversation, $serviceCode);
                $this->insertApplicationLink(
                    $conversation,
                    $serviceCode,
                    (int)$legacyApp['id'],
                    (int)$legacyApp['form_id'],
                    (int)($conversation['assigned_agent_id'] ?? 0),
                    $prefill
                );
                $message = 'La solicitud existente ' . $legacyApp['folio'] . ' quedó vinculada al perfilamiento de WhatsApp.';
                $this->setFlash('success', $message);
                $_SESSION['success'] = $message;
                $this->redirect('/solicitudes/ver/' . (int)$legacyApp['id']);
            }

            $forms = $this->getCompatibleForms($serviceCode);
            $selectedForm = null;
            foreach ($forms as $candidate) {
                if ((int)$candidate['id'] === $formId) {
                    $selectedForm = $candidate;
                    break;
                }
            }
            if (!$selectedForm) {
                throw new RuntimeException('Seleccione un formulario publicado compatible con este trámite.');
            }

            $ownerId = (int)($conversation['assigned_agent_id'] ?? 0);
            if ($ownerId <= 0) {
                throw new RuntimeException('El cliente todavía no tiene una asesora asignada. Asigne la conversación antes de formalizar el trámite.');
            }

            $stmt = $this->db->prepare("
                SELECT id, full_name
                FROM users
                WHERE id = ? AND role = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$ownerId, ROLE_ASESOR]);
            $owner = $stmt->fetch();
            if (!$owner) {
                throw new RuntimeException('La asesora asignada no está activa o ya no tiene rol de Asesor.');
            }

            if (!$this->isAdmin() && $ownerId !== (int)($_SESSION['user_id'] ?? 0)) {
                throw new RuntimeException('Sólo la asesora asignada puede convertir este cliente en solicitud.');
            }

            $prefill = $this->loadBotPrefill($conversation, $serviceCode);
            $fullName = trim((string)($prefill['full_name'] ?? ''));
            if ($fullName === '') {
                $fullName = trim((string)($conversation['profile_name'] ?? ''));
            }
            if ($fullName === '') {
                $fullName = 'Cliente WhatsApp +' . trim((string)($conversation['phone'] ?? ''));
            }

            $prefill['full_name'] = $fullName;
            $applicationData = $this->buildApplicationData($selectedForm, $prefill);

            $isCanadian = in_array($serviceCode, ['05_VISA_CANADA','06_ETA_CANADA'], true) ? 1 : 0;
            $canadianTipo = null;
            $canadianModalidad = null;
            if ($isCanadian) {
                $canadianTipo = $serviceCode === '06_ETA_CANADA' ? 'ETA Canadiense' : 'Visa Canadiense';
                $formText = $this->normalizeSearchText(($selectedForm['name'] ?? '') . ' ' . ($selectedForm['subtype'] ?? ''));
                $canadianModalidad = strpos($formText, 'renov') !== false ? 'Renovación' : 'Primera vez';
            }

            $lockName = 'crmvisas_folio_' . date('Y');
            $gotLock = false;
            $applicationId = 0;
            $folio = '';

            try {
                $stmt = $this->db->prepare("SELECT GET_LOCK(?, 8)");
                $stmt->execute([$lockName]);
                $gotLock = (int)$stmt->fetchColumn() === 1;
                if (!$gotLock) {
                    throw new RuntimeException('No se pudo reservar un folio. Intente nuevamente.');
                }

                $this->db->beginTransaction();

                $year = date('Y');
                $stmt = $this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(folio, -6) AS UNSIGNED)) AS last_number
                    FROM applications
                    WHERE folio LIKE ?
                ");
                $stmt->execute(["VISA-$year-%"]);
                $lastNumber = (int)($stmt->fetch()['last_number'] ?? 0);
                $folio = sprintf('VISA-%s-%06d', $year, $lastNumber + 1);

                $stmt = $this->db->prepare("
                    INSERT INTO applications
                      (folio, form_id, form_version, type, subtype,
                       is_canadian_visa, canadian_tipo, canadian_modalidad,
                       data_json, client_name, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $folio,
                    (int)$selectedForm['id'],
                    (int)($selectedForm['version'] ?? 1),
                    $selectedForm['type'],
                    $selectedForm['subtype'],
                    $isCanadian,
                    $canadianTipo,
                    $canadianModalidad,
                    json_encode($applicationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $fullName,
                    $ownerId,
                ]);
                $applicationId = (int)$this->db->lastInsertId();

                $stmt = $this->db->prepare("
                    INSERT INTO status_history (application_id, previous_status, new_status, comment, changed_by)
                    VALUES (?, NULL, ?, ?, ?)
                ");
                $stmt->execute([
                    $applicationId,
                    STATUS_NUEVO,
                    'Solicitud creada desde WhatsApp con datos precargados del bot.',
                    (int)($_SESSION['user_id'] ?? 0),
                ]);

                $stmt = $this->db->prepare("
                    INSERT INTO financial_status
                      (application_id, total_costs, total_paid, balance, status)
                    VALUES (?, 0, 0, 0, ?)
                ");
                $stmt->execute([$applicationId, FINANCIAL_PENDIENTE]);

                $this->insertApplicationLink(
                    $conversation,
                    $serviceCode,
                    $applicationId,
                    (int)$selectedForm['id'],
                    $ownerId,
                    $prefill
                );

                $stmt = $this->db->prepare("
                    UPDATE bot_contacts
                    SET application_id = COALESCE(application_id, ?),
                        is_existing_client = 1,
                        lead_status = 'SOLICITUD_CREADA',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$applicationId, (int)$conversation['contact_id']]);

                $stmt = $this->db->prepare("
                    INSERT INTO bot_conversation_events
                      (conversation_id, event_type, actor_user_id, actor_name, actor_role, detail, metadata_json)
                    VALUES (?, 'APPLICATION_CREATED', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    (int)$conversation['id'],
                    (int)($_SESSION['user_id'] ?? 0),
                    (string)($_SESSION['user_name'] ?? 'Usuario CRM'),
                    (string)($_SESSION['user_role'] ?? ''),
                    'Perfilamiento convertido en solicitud formal ' . $folio . '.',
                    json_encode([
                        'application_id' => $applicationId,
                        'folio' => $folio,
                        'service_code' => $serviceCode,
                        'form_id' => (int)$selectedForm['id'],
                        'assigned_agent_id' => $ownerId,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

                $this->db->commit();
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                throw $e;
            } finally {
                if ($gotLock) {
                    try {
                        $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
                        $stmt->execute([$lockName]);
                    } catch (Throwable $e) {}
                }
            }

            logAdminControlEvent(
                'solicitudes',
                'crear_desde_whatsapp',
                "Solicitud $folio creada desde WhatsApp",
                [
                    'application_id' => $applicationId,
                    'folio' => $folio,
                    'entity_type' => 'solicitud',
                    'entity_id' => $applicationId,
                    'priority' => 'alta',
                    'metadata' => [
                        'origen' => 'WhatsApp / Bot',
                        'contact_id' => (int)$conversation['contact_id'],
                        'conversation_id' => (int)$conversation['id'],
                        'service_code' => $serviceCode,
                        'asesora_id' => $ownerId,
                        'asesora' => $owner['full_name'] ?? null,
                    ],
                ]
            );

            $message = 'Solicitud ' . $folio . ' creada con los datos del bot y asignada a ' . ($owner['full_name'] ?: 'la asesora correspondiente') . '.';
            $this->setFlash('success', $message);
            $_SESSION['success'] = $message;
            $this->redirect('/solicitudes/ver/' . $applicationId);

        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/whatsapp?conversation=' . $conversationId);
        }
    }

    public function take($id) {
        $this->requireWhatsAppAccess();
        $this->verifyCsrf();
        try {
            $conversation = $this->getConversation((int)$id);
            if (!$conversation) throw new RuntimeException('Conversación no disponible.');
            if ($this->getUserRole() === ROLE_ASESOR) {
                $assignedToMe = (int)($conversation['assigned_agent_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
                $canTakeWaiting = $conversation['status'] === 'WAITING_HUMAN';
                $canResumeMine = $conversation['status'] === 'OPEN' && $conversation['mode'] === 'BOT' && $assignedToMe;
                if (!$canTakeWaiting && !$canResumeMine) {
                    throw new RuntimeException('La conversación ya no está disponible para tomar.');
                }
            }
            $this->botApiRequest('take', (int)$id);
            $this->setFlash('success', 'Conversación asignada correctamente.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('/whatsapp?conversation=' . (int)$id);
    }

    public function send($id) {
        $this->requireWhatsAppAccess();
        $this->verifyCsrf();
        $message = trim((string)($_POST['message'] ?? ''));
        try {
            if ($message === '') throw new RuntimeException('Escriba un mensaje antes de enviar.');
            if (mb_strlen($message) > 4000) throw new RuntimeException('El mensaje es demasiado largo.');
            $conversation = $this->getConversation((int)$id);
            if (!$conversation) throw new RuntimeException('Conversación no disponible.');
            $this->botApiRequest('send', (int)$id, ['message' => $message]);
            $this->setFlash('success', 'Mensaje enviado por WhatsApp.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('/whatsapp?conversation=' . (int)$id);
    }

    public function sendTemplate($id) {
        $this->requireWhatsAppAccess();
        $this->verifyCsrf();

        $message = trim((string)($_POST['template_message'] ?? ''));

        try {
            if ($message === '') throw new RuntimeException('Escriba el mensaje de seguimiento de la plantilla.');
            if (mb_strlen($message) > 1000) throw new RuntimeException('El mensaje de seguimiento es demasiado largo.');

            $conversation = $this->getConversation((int)$id);
            if (!$conversation) throw new RuntimeException('Conversación no disponible.');
            if ($conversation['status'] === 'CLOSED') throw new RuntimeException('No se pueden enviar plantillas a una conversación cerrada.');

            if (!$this->isAdmin() && (int)($conversation['assigned_agent_id'] ?? 0) !== (int)($_SESSION['user_id'] ?? 0)) {
                throw new RuntimeException('Este cliente no está asignado a su cuenta.');
            }

            $this->botApiRequest('send_template', (int)$id, [
                'message' => $message,
                'templateName' => 'seguimiento_tramite_visas',
                'templateLanguage' => 'es_MX',
            ]);

            $this->setFlash('success', 'Plantilla seguimiento_tramite_visas enviada correctamente.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('/whatsapp?conversation=' . (int)$id);
    }

    public function release($id) {
        $this->requireWhatsAppAccess();
        $this->verifyCsrf();
        try {
            $conversation = $this->getConversation((int)$id);
            if (!$conversation) throw new RuntimeException('Conversación no disponible.');
            $this->botApiRequest('release', (int)$id);
            $this->setFlash('success', 'La conversación volvió al bot y permanece asignada a la misma asesora.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('/whatsapp?conversation=' . (int)$id);
    }

    public function close($id) {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCsrf();
        try {
            $this->botApiRequest('close', (int)$id);
            $this->setFlash('success', 'Conversación cerrada. No se eliminó ningún mensaje ni intervención.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('/whatsapp?conversation=' . (int)$id);
    }
}
