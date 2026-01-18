<?php
require_once __DIR__ . "/../include/db.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Validate user ID
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing or invalid user_id",
        "unread_count" => 0
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    // Fetch unread notifications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS unread 
        FROM notifications 
        WHERE user_id = ? AND read_status = 'unread'
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Send success response
    echo json_encode([
        "status" => "success",
        "unread_count" => intval($result["unread"])
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    // Send error response
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch unread count: " . $e->getMessage(),
        "unread_count" => 0
    ]);
}

$conn->close();
?>