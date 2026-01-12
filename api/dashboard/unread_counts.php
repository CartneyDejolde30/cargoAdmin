<?php
header('Content-Type: application/json');
require_once '../config.php';

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    // Unread Notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_status = 'unread'");
    $stmt->execute([$user_id]);
    $unread_notifications = $stmt->fetch()['count'];

    // Unread Messages (implement based on your messaging system)
    $unread_messages = 0;

    echo json_encode([
        'success' => true,
        'unread_notifications' => $unread_notifications,
        'unread_messages' => $unread_messages
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>