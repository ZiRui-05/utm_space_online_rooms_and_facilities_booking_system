<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';

$userId = (int)$_SESSION['user']['user_id'];
ensure_user_notifications_table_pdo($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = (string)($input['action'] ?? $_POST['action'] ?? '');
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'mark_read') {
        $notificationId = (int)($input['notification_id'] ?? $_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $stmt = $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE notification_id = :notification_id AND user_id = :user_id');
            $stmt->execute(['notification_id' => $notificationId, 'user_id' => $userId]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$stmt = $pdo->prepare('SELECT notification_id, booking_id, title, message, notification_type, is_read, created_at FROM user_notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 8');
$stmt->execute(['user_id' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id = :user_id AND is_read = 0');
$countStmt->execute(['user_id' => $userId]);
$unreadCount = (int)$countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications,
]);
