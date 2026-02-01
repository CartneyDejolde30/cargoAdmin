<?php
/**
 * ============================================================================
 * ARCHIVE NOTIFICATION - Move notification to archive
 * ============================================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get notification ID
$notification_id = $_POST['id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Missing notification ID']);
    exit;
}

// Check if archived_notifications table exists, if not create it
$checkTable = "SHOW TABLES LIKE 'archived_notifications'";
$result = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($result) == 0) {
    // Create archived_notifications table
    $createTable = "CREATE TABLE IF NOT EXISTS `archived_notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `original_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) DEFAULT 'info',
        `read_status` ENUM('read', 'unread') DEFAULT 'unread',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `archived_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_original_id` (`original_id`),
        KEY `idx_archived_at` (`archived_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    mysqli_query($conn, $createTable);
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Get notification details
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Notification not found');
    }
    
    $notification = $result->fetch_assoc();
    
    // Insert into archived_notifications
    $stmt = $conn->prepare("
        INSERT INTO archived_notifications 
        (original_id, user_id, title, message, type, read_status, created_at, archived_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param(
        "iisssss",
        $notification['id'],
        $notification['user_id'],
        $notification['title'],
        $notification['message'],
        $notification['type'] ?? 'info',
        $notification['read_status'],
        $notification['created_at']
    );
    
    $stmt->execute();
    
    // Delete from notifications
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification archived successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Error archiving notification: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
