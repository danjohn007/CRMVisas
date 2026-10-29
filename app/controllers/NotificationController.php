<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class NotificationController extends BaseController {

    /**
     * Mark a notification as read for the current user.
     * Expects POST parameters: application_id, notification_type (appointment|biometric)
     */
    public function markRead() {
        $this->requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        $applicationId     = intval($_POST['application_id'] ?? 0);
        $notificationType  = trim($_POST['notification_type'] ?? '');

        if ($applicationId <= 0 || !in_array($notificationType, ['appointment', 'biometric'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        try {
            // Verify the application exists and the user is allowed to see it
            $stmt = $this->db->prepare("SELECT id, created_by FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch();

            if (!$app) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
                exit;
            }

            $role = $this->getUserRole();
            if ($role === ROLE_ASESOR && (int)$app['created_by'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }

            // Insert or ignore (UNIQUE KEY prevents duplicates)
            $this->db->prepare("
                INSERT IGNORE INTO notification_reads (user_id, application_id, notification_type)
                VALUES (?, ?, ?)
            ")->execute([$userId, $applicationId, $notificationType]);

            // Return updated unread count for this user
            $unread = $this->getUnreadCount($userId, $role);
            echo json_encode(['success' => true, 'unread_count' => $unread]);

        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
        exit;
    }

    /**
     * Mark a notification as unread (uncheck) for the current user.
     * Expects POST parameters: application_id, notification_type
     */
    public function markUnread() {
        $this->requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        $applicationId    = intval($_POST['application_id'] ?? 0);
        $notificationType = trim($_POST['notification_type'] ?? '');

        if ($applicationId <= 0 || !in_array($notificationType, ['appointment', 'biometric'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        try {
            $stmt = $this->db->prepare("SELECT id, created_by FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch();

            if (!$app) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
                exit;
            }

            $role = $this->getUserRole();
            if ($role === ROLE_ASESOR && (int)$app['created_by'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }

            $this->db->prepare("
                DELETE FROM notification_reads
                WHERE user_id = ? AND application_id = ? AND notification_type = ?
            ")->execute([$userId, $applicationId, $notificationType]);

            $unread = $this->getUnreadCount($userId, $role);
            echo json_encode(['success' => true, 'unread_count' => $unread]);

        } catch (PDOException $e) {
            error_log("Error unmarking notification: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
        exit;
    }

    /**
     * Count unread notifications for a given user.
     */
    private function getUnreadCount($userId, $role) {
        $today    = date('Y-m-d');
        $deadline = date('Y-m-d', strtotime('+2 days'));

        $advisorFilter = $role === ROLE_ASESOR ? ' AND a.created_by = ?' : '';

        $sql = "
            SELECT COUNT(*) FROM (
                SELECT a.id
                FROM applications a
                LEFT JOIN notification_reads nr
                       ON nr.application_id = a.id
                      AND nr.notification_type = 'appointment'
                      AND nr.user_id = ?
                WHERE a.appointment_date IS NOT NULL
                  AND DATE(a.appointment_date) >= ?
                  AND DATE(a.appointment_date) <= ?
                  AND (a.client_attended IS NULL OR a.client_attended = 0)
                  AND nr.id IS NULL
                  $advisorFilter

                UNION ALL

                SELECT a.id
                FROM applications a
                LEFT JOIN notification_reads nr
                       ON nr.application_id = a.id
                      AND nr.notification_type = 'biometric'
                      AND nr.user_id = ?
                WHERE a.canadian_biometric_date IS NOT NULL
                  AND DATE(a.canadian_biometric_date) >= ?
                  AND DATE(a.canadian_biometric_date) <= ?
                  AND (a.canadian_client_attended_biometrics IS NULL OR a.canadian_client_attended_biometrics = 0)
                  AND nr.id IS NULL
                  $advisorFilter
            ) t
        ";

        $params = [$userId, $today, $deadline];
        if ($role === ROLE_ASESOR) {
            $params[] = $userId;
        }
        $params[] = $userId;
        $params[] = $today;
        $params[] = $deadline;
        if ($role === ROLE_ASESOR) {
            $params[] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
