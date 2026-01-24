<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../include/db.php";


if (!isset($_POST['notification_id']) || !isset($_POST['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing notification_id or user_id"
    ]);
    exit;
}

$notification_id = intval($_POST['notification_id']);
$user_id = intval($_POST['user_id']);

$stmt = $conn->prepare("
    DELETE FROM notifications
    WHERE id = ? AND user_id = ?
");

$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Notification deleted"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to delete notification"
    ]);
}

$stmt->close();
$conn->close();
