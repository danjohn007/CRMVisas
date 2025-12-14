<?php
/**
 * Notification Controller
 * Manages notifications for users
 */

class NotificationController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }
    
    /**
     * Get notifications for current user
     * Returns JSON response
     */
    public function getNotifications() {
        $userId = $_SESSION['user_id'];
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        // Validate limit bounds
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;
        
        $stmt = $this->db->prepare("
            SELECT id, title, message, type, link, is_read, created_at 
            FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        exit;
    }
    
    /**
     * Get unread notification count
     * Returns JSON response
     */
    public function getUnreadCount() {
        $userId = $_SESSION['user_id'];
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count' => (int)$result['count']
        ]);
        exit;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $notificationId = $_POST['id'] ?? null;
        
        if (!$notificationId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit;
        }
        
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
