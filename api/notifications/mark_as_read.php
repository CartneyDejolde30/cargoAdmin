<?php
session_start();
header('Content-Type: application/json');
require_once '../../include/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification_id = $_POST['notification_id'] ?? null;
$mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';

if ($mark_all) {
    // Mark all as read
    $query = "UPDATE admin_notifications SET read_status = 'read', read_at = NOW() WHERE read_status = 'unread'";
    $result = $conn->query($query);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'affected_rows' => $conn->affected_rows
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
} elseif ($notification_id) {
    // Mark single notification as read
    $stmt = $conn->prepare("UPDATE admin_notifications SET read_status = 'read', read_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $notification_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
}

$conn->close();
?>