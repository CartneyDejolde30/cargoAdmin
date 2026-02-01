<?php
require_once __DIR__ . "/include/db.php";

header("Content-Type: application/json; charset=utf-8");

// Validate user ID
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing or invalid user_id"
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);

// Fetch notifications
$stmt = $conn->prepare("
    SELECT id, title, message, read_status, created_at 
    FROM notifications 
    WHERE user_id=? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch unread count
$stmt2 = $conn->prepare("
    SELECT COUNT(*) AS unread 
    FROM notifications 
    WHERE user_id=? AND read_status='unread'
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result()->fetch_assoc();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Send response
echo json_encode([
    "status" => "success",
    "unread_count" => $result2["unread"],
    "notifications" => $notifications
]);
?>
