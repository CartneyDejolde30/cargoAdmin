<?php
session_start();
header('Content-Type: application/json');
require_once '../../include/db.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification_id = $_POST['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM admin_notifications WHERE id = ?");
$stmt->bind_param('i', $notification_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification deleted'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}

$stmt->close();
$conn->close();
?>