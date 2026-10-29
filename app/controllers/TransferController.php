<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class TransferController extends BaseController
{
    private const STATUS_ACTIVE = 'ACTIVO';
    private const STATUS_FINISHED = 'FINALIZADO';
    private const CAPACITIES = [69, 49, 45, 20];

    public function index()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);

        $role = $this->getUserRole();
        $activeTransfers = $this->getTransfers(self::STATUS_ACTIVE);
        $finishedTransfers = in_array($role, [ROLE_ADMIN, ROLE_GERENTE], true)
            ? $this->getTransfers(self::STATUS_FINISHED)
            : [];

        $this->view('transfers/index', [
            'activeTransfers' => $activeTransfers,
            'finishedTransfers' => $finishedTransfers,
            'canCreate' => $role === ROLE_ADMIN,
            'canSeeHistory' => in_array($role, [ROLE_ADMIN, ROLE_GERENTE], true),
            'capacityOptions' => $this->capacityOptions(),
        ]);
    }

    public function create()
    {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/traslados');
        }

        $date = trim($_POST['transfer_date'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (!$this->validDate($date)) {
            $_SESSION['error'] = 'Seleccione una fecha válida para el traslado.';
            $this->redirect('/traslados');
        }

        if (!in_array($capacity, self::CAPACITIES, true)) {
            $_SESSION['error'] = 'Seleccione una capacidad válida: 69, 49, 45 o VAN de 20 lugares.';
            $this->redirect('/traslados');
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM travel_transfers WHERE transfer_date=? LIMIT 1");
            $stmt->execute([$date]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Ya existe un traslado registrado para esa fecha.';
                $this->redirect('/traslados');
            }

            $vehicleLabel = $capacity === 20 ? 'VAN 20 lugares' : 'Autobús ' . $capacity . ' lugares';
            $stmt = $this->db->prepare("
                INSERT INTO travel_transfers
                    (transfer_date, capacity, vehicle_label, status, notes, created_by)
                VALUES (?, ?, ?, 'ACTIVO', ?, ?)
            ");
            $stmt->execute([$date, $capacity, $vehicleLabel, $notes !== '' ? $notes : null, $_SESSION['user_id']]);
            $id = (int)$this->db->lastInsertId();

            logAudit('create', 'traslados', "Traslado #{$id} creado para {$date} con capacidad {$capacity}");
            $_SESSION['success'] = 'Traslado creado correctamente.';
            $this->redirect('/traslados/ver/' . $id);
        } catch (PDOException $e) {
            error_log('Error al crear traslado: ' . $e->getMessage());
            $_SESSION['error'] = 'No fue posible crear el traslado.';
            $this->redirect('/traslados');
        }
    }

    public function show($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);
        $id = (int)$id;
        $transfer = $this->getTransfer($id);

        if (!$transfer) {
            http_response_code(404);
            die('Traslado no encontrado.');
        }

        $role = $this->getUserRole();
        if ($transfer['status'] === self::STATUS_FINISHED && $role === ROLE_ASESOR) {
            $_SESSION['error'] = 'Este traslado ya fue finalizado y ya no está disponible para asesoras.';
            $this->redirect('/traslados');
        }

        $rows = $this->getTransferClients($id, $role);
        $occupiedSeats = $this->getOccupiedSeats($id);
        $availableSeats = [];
        for ($seat = 1; $seat <= (int)$transfer['capacity']; $seat++) {
            if (!in_array($seat, $occupiedSeats, true)) {
                $availableSeats[] = $seat;
            }
        }

        $applications = $transfer['status'] === self::STATUS_ACTIVE
            ? $this->getApplicationsForSelection($role)
            : [];
        $advisors = in_array($role, [ROLE_ADMIN, ROLE_GERENTE], true)
            ? $this->getActiveAdvisors()
            : [];

        $stats = $this->getTransferStats($id, (int)$transfer['capacity']);

        $this->view('transfers/show', [
            'transfer' => $transfer,
            'rows' => $rows,
            'stats' => $stats,
            'availableSeats' => $availableSeats,
            'occupiedSeats' => $occupiedSeats,
            'applications' => $applications,
            'advisors' => $advisors,
            'canCreateCloseReopen' => $role === ROLE_ADMIN,
            'canDelete' => $role === ROLE_ADMIN,
            'canManageAllClients' => in_array($role, [ROLE_ADMIN, ROLE_GERENTE], true),
            'canEdit' => $transfer['status'] === self::STATUS_ACTIVE,
            'isAdvisor' => $role === ROLE_ASESOR,
        ]);
    }

    public function storeClient()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/traslados');
        }

        $transferId = (int)($_POST['transfer_id'] ?? 0);
        $transfer = $this->getTransfer($transferId);
        if (!$transfer || $transfer['status'] !== self::STATUS_ACTIVE) {
            $_SESSION['error'] = 'El traslado no está disponible para agregar clientes.';
            $this->redirect('/traslados');
        }

        $role = $this->getUserRole();
        $advisorId = $role === ROLE_ASESOR
            ? (int)$_SESSION['user_id']
            : (int)($_POST['advisor_id'] ?? 0);
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $seatNumber = (int)($_POST['seat_number'] ?? 0);
        $clientName = trim($_POST['client_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $appointmentAt = $this->normalizeDateTime($_POST['review_appointment'] ?? '');

        if (!$this->validateAdvisor($advisorId)) {
            $_SESSION['error'] = 'Seleccione una asesora válida.';
            $this->redirect('/traslados/ver/' . $transferId);
        }

        if ($applicationId > 0) {
            $application = $this->getApplicationForUser($applicationId, $role);
            if (!$application) {
                $_SESSION['error'] = 'No tiene acceso al expediente seleccionado.';
                $this->redirect('/traslados/ver/' . $transferId);
            }
            if ($clientName === '') {
                $clientName = $this->extractClientName($application);
            }
            if ($phone === '') {
                $phone = $this->extractPhone($application['data_json'] ?? '');
            }
        }

        $error = $this->validateClientData($transfer, $seatNumber, $clientName, $phone, $appointmentAt);
        if ($error) {
            $_SESSION['error'] = $error;
            $this->redirect('/traslados/ver/' . $transferId);
        }

        try {
            if ($this->seatIsOccupied($transferId, $seatNumber)) {
                $_SESSION['error'] = 'El número de lugar seleccionado ya está ocupado.';
                $this->redirect('/traslados/ver/' . $transferId);
            }

            if ($applicationId > 0 && $this->applicationAlreadyInTransfer($transferId, $applicationId)) {
                $_SESSION['error'] = 'Ese expediente ya está agregado a este traslado.';
                $this->redirect('/traslados/ver/' . $transferId);
            }

            $stmt = $this->db->prepare("
                INSERT INTO travel_transfer_clients
                    (transfer_id, application_id, advisor_id, seat_number, client_name, phone,
                     review_appointment, is_ready, created_by, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([
                $transferId,
                $applicationId > 0 ? $applicationId : null,
                $advisorId,
                $seatNumber,
                $clientName,
                $phone !== '' ? $phone : null,
                $appointmentAt,
                $_SESSION['user_id'],
                $_SESSION['user_id'],
            ]);

            logAudit('create', 'traslados', "Cliente {$clientName} agregado al traslado #{$transferId}, lugar {$seatNumber}");
            $_SESSION['success'] = 'Cliente agregado al traslado.';
        } catch (PDOException $e) {
            error_log('Error al agregar cliente a traslado: ' . $e->getMessage());
            $_SESSION['error'] = 'No fue posible agregar al cliente. Verifique que el lugar no esté repetido.';
        }

        $this->redirect('/traslados/ver/' . $transferId);
    }

    public function updateClient($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);
        $id = (int)$id;
        $row = $this->getClientRow($id);
        if (!$row) {
            http_response_code(404);
            die('Registro no encontrado.');
        }

        $transfer = $this->getTransfer((int)$row['transfer_id']);
        if (!$transfer || $transfer['status'] !== self::STATUS_ACTIVE) {
            $_SESSION['error'] = 'El traslado ya está finalizado y no puede modificarse.';
            $this->redirect('/traslados');
        }

        $role = $this->getUserRole();
        if ($role === ROLE_ASESOR && (int)$row['advisor_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            die('No tiene permiso para modificar este cliente.');
        }

        $advisorId = $role === ROLE_ASESOR
            ? (int)$_SESSION['user_id']
            : (int)($_POST['advisor_id'] ?? $row['advisor_id']);
        $seatNumber = (int)($_POST['seat_number'] ?? 0);
        $clientName = trim($_POST['client_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $appointmentAt = $this->normalizeDateTime($_POST['review_appointment'] ?? '');

        if (!$this->validateAdvisor($advisorId)) {
            $_SESSION['error'] = 'Seleccione una asesora válida.';
            $this->redirect('/traslados/ver/' . $row['transfer_id']);
        }

        $error = $this->validateClientData($transfer, $seatNumber, $clientName, $phone, $appointmentAt);
        if ($error) {
            $_SESSION['error'] = $error;
            $this->redirect('/traslados/ver/' . $row['transfer_id']);
        }

        try {
            if ($this->seatIsOccupied((int)$row['transfer_id'], $seatNumber, $id)) {
                $_SESSION['error'] = 'El número de lugar seleccionado ya está ocupado.';
                $this->redirect('/traslados/ver/' . $row['transfer_id']);
            }

            $stmt = $this->db->prepare("
                UPDATE travel_transfer_clients
                SET advisor_id=?, seat_number=?, client_name=?, phone=?, review_appointment=?, updated_by=?, updated_at=CURRENT_TIMESTAMP
                WHERE id=?
            ");
            $stmt->execute([
                $advisorId,
                $seatNumber,
                $clientName,
                $phone !== '' ? $phone : null,
                $appointmentAt,
                $_SESSION['user_id'],
                $id,
            ]);

            logAudit('update', 'traslados', "Cliente #{$id} actualizado en traslado #{$row['transfer_id']}");
            $_SESSION['success'] = 'Información del cliente actualizada.';
        } catch (PDOException $e) {
            error_log('Error al actualizar cliente de traslado: ' . $e->getMessage());
            $_SESSION['error'] = 'No fue posible actualizar el registro.';
        }

        $this->redirect('/traslados/ver/' . $row['transfer_id']);
    }

    public function toggleReady($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);
        $id = (int)$id;
        $row = $this->getClientRow($id);
        if (!$row) {
            http_response_code(404);
            die('Registro no encontrado.');
        }

        $transfer = $this->getTransfer((int)$row['transfer_id']);
        if (!$transfer || $transfer['status'] !== self::STATUS_ACTIVE) {
            $_SESSION['error'] = 'El traslado está finalizado.';
            $this->redirect('/traslados');
        }

        if ($this->getUserRole() === ROLE_ASESOR && (int)$row['advisor_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            die('No tiene permiso para modificar este cliente.');
        }

        $newValue = (int)!((bool)$row['is_ready']);
        $stmt = $this->db->prepare("UPDATE travel_transfer_clients SET is_ready=?, updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->execute([$newValue, $_SESSION['user_id'], $id]);

        logAudit('update', 'traslados', "Cliente #{$id} marcado TODO LISTO=" . $newValue);
        $_SESSION['success'] = $newValue ? 'Cliente marcado como TODO LISTO.' : 'Marca TODO LISTO retirada.';
        $this->redirect('/traslados/ver/' . $row['transfer_id']);
    }

    public function deleteClient($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE, ROLE_ASESOR]);
        $id = (int)$id;
        $row = $this->getClientRow($id);
        if (!$row) {
            http_response_code(404);
            die('Registro no encontrado.');
        }

        $transfer = $this->getTransfer((int)$row['transfer_id']);
        if (!$transfer || $transfer['status'] !== self::STATUS_ACTIVE) {
            $_SESSION['error'] = 'El traslado está finalizado.';
            $this->redirect('/traslados');
        }

        if ($this->getUserRole() === ROLE_ASESOR && (int)$row['advisor_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            die('No tiene permiso para retirar este cliente.');
        }

        $stmt = $this->db->prepare("DELETE FROM travel_transfer_clients WHERE id=?");
        $stmt->execute([$id]);

        logAudit('delete', 'traslados', "Cliente {$row['client_name']} retirado del traslado #{$row['transfer_id']}");
        $_SESSION['success'] = 'Cliente retirado del traslado.';
        $this->redirect('/traslados/ver/' . $row['transfer_id']);
    }

    public function close($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $id = (int)$id;
        $transfer = $this->getTransfer($id);
        if (!$transfer) {
            http_response_code(404);
            die('Traslado no encontrado.');
        }

        if ($transfer['status'] === self::STATUS_FINISHED) {
            $_SESSION['error'] = 'El traslado ya está finalizado.';
            $this->redirect('/traslados/ver/' . $id);
        }

        $stmt = $this->db->prepare("
            UPDATE travel_transfers
            SET status='FINALIZADO', closed_by=?, closed_at=NOW(), updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$_SESSION['user_id'], $id]);

        logAudit('close', 'traslados', "Traslado #{$id} finalizado");
        $_SESSION['success'] = 'Traslado cerrado. Ya no será visible para las asesoras.';
        $this->redirect('/traslados');
    }

    public function reopen($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $id = (int)$id;
        $transfer = $this->getTransfer($id);
        if (!$transfer) {
            http_response_code(404);
            die('Traslado no encontrado.');
        }

        if ($transfer['status'] === self::STATUS_ACTIVE) {
            $_SESSION['error'] = 'El traslado ya está activo.';
            $this->redirect('/traslados/ver/' . $id);
        }

        $stmt = $this->db->prepare("
            UPDATE travel_transfers
            SET status='ACTIVO', reopened_by=?, reopened_at=NOW(), updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$_SESSION['user_id'], $id]);

        logAudit('reopen', 'traslados', "Traslado #{$id} reabierto");
        $_SESSION['success'] = 'Traslado reabierto. Las asesoras pueden consultarlo nuevamente.';
        $this->redirect('/traslados/ver/' . $id);
    }

    public function delete($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/traslados');
        }

        $id = (int)$id;
        $transfer = $this->getTransfer($id);
        if (!$transfer) {
            $_SESSION['error'] = 'El traslado ya no existe.';
            $this->redirect('/traslados');
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM travel_transfer_clients WHERE transfer_id=?");
        $stmt->execute([$id]);
        $clientCount = (int)$stmt->fetchColumn();

        try {
            $stmt = $this->db->prepare("DELETE FROM travel_transfers WHERE id=?");
            $stmt->execute([$id]);
            logAudit('delete', 'traslados', "Traslado #{$id} eliminado; fecha {$transfer['transfer_date']}; clientes {$clientCount}");
            $_SESSION['success'] = 'Traslado eliminado definitivamente.';
        } catch (Throwable $e) {
            error_log('Error al eliminar traslado: ' . $e->getMessage());
            $_SESSION['error'] = 'No fue posible eliminar el traslado.';
        }

        $this->redirect('/traslados');
    }

    private function getTransfers(string $status): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   u.full_name AS creator_name,
                   c.full_name AS closer_name,
                   COUNT(tc.id) AS client_count,
                   COALESCE(SUM(CASE WHEN tc.is_ready=1 THEN 1 ELSE 0 END),0) AS ready_count
            FROM travel_transfers t
            LEFT JOIN users u ON u.id=t.created_by
            LEFT JOIN users c ON c.id=t.closed_by
            LEFT JOIN travel_transfer_clients tc ON tc.transfer_id=t.id
            WHERE t.status=?
            GROUP BY t.id
            ORDER BY t.transfer_date " . ($status === self::STATUS_ACTIVE ? 'ASC' : 'DESC')
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    private function getTransfer(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name AS creator_name, c.full_name AS closer_name
            FROM travel_transfers t
            LEFT JOIN users u ON u.id=t.created_by
            LEFT JOIN users c ON c.id=t.closed_by
            WHERE t.id=? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function getTransferClients(int $transferId, string $role): array
    {
        $sql = "
            SELECT tc.*, u.full_name AS advisor_name, a.folio AS application_folio
            FROM travel_transfer_clients tc
            LEFT JOIN users u ON u.id=tc.advisor_id
            LEFT JOIN applications a ON a.id=tc.application_id
            WHERE tc.transfer_id=?
        ";
        $params = [$transferId];
        if ($role === ROLE_ASESOR) {
            $sql .= " AND tc.advisor_id=?";
            $params[] = (int)$_SESSION['user_id'];
        }
        $sql .= " ORDER BY tc.seat_number ASC, tc.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function getTransferStats(int $transferId, int $capacity): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN is_ready=1 THEN 1 ELSE 0 END),0) AS ready
            FROM travel_transfer_clients
            WHERE transfer_id=?
        ");
        $stmt->execute([$transferId]);
        $row = $stmt->fetch() ?: ['total' => 0, 'ready' => 0];
        $total = (int)$row['total'];
        $ready = (int)$row['ready'];
        return [
            'total' => $total,
            'ready' => $ready,
            'pending' => max(0, $total - $ready),
            'available' => max(0, $capacity - $total),
            'occupancy_percent' => $capacity > 0 ? min(100, round(($total / $capacity) * 100, 1)) : 0,
            'ready_percent' => $total > 0 ? min(100, round(($ready / $total) * 100, 1)) : 0,
        ];
    }

    private function getOccupiedSeats(int $transferId): array
    {
        $stmt = $this->db->prepare("SELECT seat_number FROM travel_transfer_clients WHERE transfer_id=? ORDER BY seat_number");
        $stmt->execute([$transferId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'seat_number'));
    }

    private function getActiveAdvisors(): array
    {
        $stmt = $this->db->prepare("SELECT id, full_name FROM users WHERE role=? AND is_active=1 ORDER BY full_name");
        $stmt->execute([ROLE_ASESOR]);
        return $stmt->fetchAll();
    }

    private function getApplicationsForSelection(string $role): array
    {
        try {
            if ($role === ROLE_ASESOR) {
                $sql = "
                    SELECT DISTINCT a.id, a.folio, a.client_name, a.data_json, a.created_by
                    FROM applications a
                    LEFT JOIN income_sales s ON s.application_id=a.id AND s.advisor_id=?
                    LEFT JOIN bot_application_links bal ON bal.application_id=a.id AND bal.assigned_agent_id=?
                    WHERE (a.created_by=? OR s.id IS NOT NULL OR bal.id IS NOT NULL)
                      AND a.status<>?
                    ORDER BY a.created_at DESC
                    LIMIT 600
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], STATUS_TRAMITE_CERRADO]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT id, folio, client_name, data_json, created_by
                    FROM applications
                    WHERE status<>?
                    ORDER BY created_at DESC
                    LIMIT 1000
                ");
                $stmt->execute([STATUS_TRAMITE_CERRADO]);
            }
        } catch (PDOException $e) {
            // Compatibilidad si alguna instalación aún no tiene las tablas de vínculos.
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT id, folio, client_name, data_json, created_by
                    FROM applications
                    WHERE created_by=? AND status<>?
                    ORDER BY created_at DESC LIMIT 600
                ");
                $stmt->execute([$_SESSION['user_id'], STATUS_TRAMITE_CERRADO]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT id, folio, client_name, data_json, created_by
                    FROM applications
                    WHERE status<>?
                    ORDER BY created_at DESC LIMIT 1000
                ");
                $stmt->execute([STATUS_TRAMITE_CERRADO]);
            }
        }

        $apps = $stmt->fetchAll();
        foreach ($apps as &$app) {
            $app['display_name'] = $this->extractClientName($app);
            $app['phone'] = $this->extractPhone($app['data_json'] ?? '');
        }
        unset($app);
        return $apps;
    }

    private function getApplicationForUser(int $applicationId, string $role)
    {
        if ($role !== ROLE_ASESOR) {
            $stmt = $this->db->prepare("SELECT id, folio, client_name, data_json, created_by FROM applications WHERE id=? LIMIT 1");
            $stmt->execute([$applicationId]);
            return $stmt->fetch();
        }

        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT a.id, a.folio, a.client_name, a.data_json, a.created_by
                FROM applications a
                LEFT JOIN income_sales s ON s.application_id=a.id AND s.advisor_id=?
                LEFT JOIN bot_application_links bal ON bal.application_id=a.id AND bal.assigned_agent_id=?
                WHERE a.id=? AND (a.created_by=? OR s.id IS NOT NULL OR bal.id IS NOT NULL)
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $applicationId, $_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $stmt = $this->db->prepare("SELECT id, folio, client_name, data_json, created_by FROM applications WHERE id=? AND created_by=? LIMIT 1");
            $stmt->execute([$applicationId, $_SESSION['user_id']]);
            return $stmt->fetch();
        }
    }

    private function getClientRow(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM travel_transfer_clients WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function validateAdvisor(int $advisorId): bool
    {
        if ($advisorId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id=? AND role=? AND is_active=1 LIMIT 1");
        $stmt->execute([$advisorId, ROLE_ASESOR]);
        return (bool)$stmt->fetch();
    }

    private function validateClientData(array $transfer, int $seatNumber, string $clientName, string $phone, ?string $appointmentAt): ?string
    {
        if ($seatNumber < 1 || $seatNumber > (int)$transfer['capacity']) {
            return 'El número de lugar debe estar entre 1 y ' . (int)$transfer['capacity'] . '.';
        }
        if ($clientName === '') {
            return 'El nombre del cliente es obligatorio.';
        }
        if (mb_strlen($clientName) > 200) {
            return 'El nombre del cliente es demasiado largo.';
        }
        if ($phone === '') {
            return 'El teléfono del cliente es obligatorio.';
        }
        if (mb_strlen($phone) > 30) {
            return 'El teléfono es demasiado largo.';
        }
        if ($appointmentAt === false || $appointmentAt === null) {
            return 'La fecha y hora de REC Y REV es obligatoria.';
        }
        return null;
    }

    private function seatIsOccupied(int $transferId, int $seatNumber, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM travel_transfer_clients WHERE transfer_id=? AND seat_number=?";
        $params = [$transferId, $seatNumber];
        if ($excludeId > 0) {
            $sql .= " AND id<>?";
            $params[] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    private function applicationAlreadyInTransfer(int $transferId, int $applicationId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM travel_transfer_clients WHERE transfer_id=? AND application_id=? LIMIT 1");
        $stmt->execute([$transferId, $applicationId]);
        return (bool)$stmt->fetch();
    }

    private function normalizeDateTime(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return false;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function validDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function extractClientName(array $application): string
    {
        $name = trim((string)($application['client_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $data = json_decode($application['data_json'] ?? '{}', true) ?: [];
        $name = trim((string)($data['nombre_cliente'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $name = trim(((string)($data['nombre'] ?? '')) . ' ' . ((string)($data['apellidos'] ?? '')));
        return $name !== '' ? $name : 'Cliente sin nombre';
    }

    private function extractPhone(string $dataJson): string
    {
        $data = json_decode($dataJson, true) ?: [];
        foreach (['telefono', 'teléfono', 'phone', 'celular', 'whatsapp', 'telefono_cliente'] as $key) {
            if (!empty($data[$key])) {
                return trim((string)$data[$key]);
            }
        }
        return '';
    }

    private function capacityOptions(): array
    {
        return [
            69 => 'Autobús · 69 lugares',
            49 => 'Autobús · 49 lugares',
            45 => 'Autobús · 45 lugares',
            20 => 'VAN · 20 lugares',
        ];
    }
}
