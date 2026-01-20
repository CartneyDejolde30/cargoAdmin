<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../include/db.php";

// ---------------------------
// ALLOW POST ONLY
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

// ---------------------------
// GET INPUTS
// ---------------------------
$reporterId = intval($_POST['reporter_id'] ?? 0);
$reportType = strtolower(trim($_POST['report_type'] ?? ""));
$reportedId = trim($_POST['reported_id'] ?? ""); // Keep as string for flexibility
$reason     = trim($_POST['reason'] ?? "");
$details    = trim($_POST['details'] ?? "");

// ---------------------------
// VALIDATION
// ---------------------------
$errors = [];

if ($reporterId <= 0) {
    $errors[] = "Invalid reporter ID";
}

if (empty($reportType)) {
    $errors[] = "Report type is required";
}

if (empty($reportedId)) {
    $errors[] = "Reported item ID is required";
}

if (empty($reason)) {
    $errors[] = "Reason is required";
}

if (empty($details)) {
    $errors[] = "Details are required";
} elseif (strlen($details) < 20) {
    $errors[] = "Details must be at least 20 characters";
} elseif (strlen($details) > 500) {
    $errors[] = "Details must not exceed 500 characters";
}

// Validate report type
$allowedTypes = ['car', 'motorcycle', 'user', 'booking', 'chat'];
if (!in_array($reportType, $allowedTypes)) {
    $errors[] = "Invalid report type";
}

// Validate reason based on type
$validReasons = [
    'car' => ['Misleading information', 'Fake photos', 'Vehicle not as described', 'Safety concerns', 'Suspicious pricing', 'Unavailable vehicle', 'Other'],
    'motorcycle' => ['Misleading information', 'Fake photos', 'Vehicle not as described', 'Safety concerns', 'Suspicious pricing', 'Unavailable vehicle', 'Other'],
    'user' => ['Inappropriate behavior', 'Harassment', 'Fraud/Scam', 'Fake profile', 'Suspicious activity', 'Spam', 'Other'],
    'booking' => ['No-show', 'Late pickup/return', 'Vehicle damage', 'Cleanliness issues', 'Payment dispute', 'Cancellation issues', 'Other'],
    'chat' => ['Harassment', 'Spam messages', 'Inappropriate content', 'Scam attempt', 'Threatening behavior', 'Other']
];

if (isset($validReasons[$reportType]) && !in_array($reason, $validReasons[$reportType])) {
    $errors[] = "Invalid reason for this report type";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => implode(", ", $errors)
    ]);
    exit;
}

// ---------------------------
// VERIFY USER EXISTS
// ---------------------------
$checkUser = $conn->prepare("SELECT id, fullname, email FROM users WHERE id = ?");
$checkUser->bind_param("i", $reporterId);
$checkUser->execute();
$userResult = $checkUser->get_result();

if ($userResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Reporter not found"
    ]);
    exit;
}

$reporter = $userResult->fetch_assoc();
$checkUser->close();

// ---------------------------
// RATE LIMITING CHECK
// ---------------------------
function checkRateLimit($conn, $reporterId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM reports 
        WHERE reporter_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->bind_param("i", $reporterId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] < 5; // Max 5 reports per hour
}

if (!checkRateLimit($conn, $reporterId)) {
    http_response_code(429);
    echo json_encode([
        "status" => "error",
        "message" => "Too many reports. You can submit up to 5 reports per hour. Please try again later."
    ]);
    exit;
}

// ---------------------------
// DUPLICATE REPORT CHECK
// ---------------------------
function isDuplicateReport($conn, $reporterId, $reportedId, $reportType) {
    $stmt = $conn->prepare("
        SELECT id, created_at 
        FROM reports 
        WHERE reporter_id = ? 
        AND reported_id = ? 
        AND report_type = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND status != 'dismissed'
    ");
    $stmt->bind_param("iss", $reporterId, $reportedId, $reportType);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

if (isDuplicateReport($conn, $reporterId, $reportedId, $reportType)) {
    http_response_code(409);
    echo json_encode([
        "status" => "error",
        "message" => "You have already reported this item in the last 24 hours. Please wait before submitting another report."
    ]);
    exit;
}

// ---------------------------
// VERIFY REPORTED ITEM EXISTS
// ---------------------------
function verifyReportedItemExists($conn, $reportType, $reportedId) {
    $table = null;
    
    switch ($reportType) {
        case 'car':
            $table = 'cars';
            break;
        case 'motorcycle':
            $table = 'motorcycles';
            break;
        case 'user':
            $table = 'users';
            break;
        case 'booking':
            $table = 'bookings';
            break;
        case 'chat':
            // For chat, we might need different validation
            return true;
        default:
            return false;
    }
    
    if ($table === null) return false;
    
    $stmt = $conn->prepare("SELECT id FROM $table WHERE id = ?");
    $stmt->bind_param("s", $reportedId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

if (!verifyReportedItemExists($conn, $reportType, $reportedId)) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "The reported item does not exist or has been removed"
    ]);
    exit;
}

// ---------------------------
// SANITIZE INPUTS
// ---------------------------
$details = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
$reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');

// ---------------------------
// START TRANSACTION
// ---------------------------
$conn->begin_transaction();

try {
    // ---------------------------
    // INSERT REPORT
    // ---------------------------
    $stmt = $conn->prepare("
        INSERT INTO reports 
        (reporter_id, report_type, reported_id, reason, details, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");

    $stmt->bind_param(
        "issss",
        $reporterId,
        $reportType,
        $reportedId,
        $reason,
        $details
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert report: " . $stmt->error);
    }

    $reportId = $conn->insert_id;
    $stmt->close();

    // ---------------------------
    // LOG AUDIT TRAIL
    // ---------------------------
    $logStmt = $conn->prepare("
        INSERT INTO report_logs 
        (report_id, action, performed_by, notes, created_at)
        VALUES (?, 'created', ?, 'Report submitted by user', NOW())
    ");
    $logStmt->bind_param("ii", $reportId, $reporterId);
    $logStmt->execute();
    $logStmt->close();

    // ---------------------------
    // INCREMENT REPORT COUNTER FOR REPORTED ITEM (optional)
    // ---------------------------
    if ($reportType === 'user') {
        $counterStmt = $conn->prepare("
            UPDATE users 
            SET report_count = COALESCE(report_count, 0) + 1 
            WHERE id = ?
        ");
        $counterStmt->bind_param("s", $reportedId);
        $counterStmt->execute();
        $counterStmt->close();
    } elseif ($reportType === 'car') {
        $counterStmt = $conn->prepare("
            UPDATE cars 
            SET report_count = COALESCE(report_count, 0) + 1 
            WHERE id = ?
        ");
        $counterStmt->bind_param("s", $reportedId);
        $counterStmt->execute();
        $counterStmt->close();
    } elseif ($reportType === 'motorcycle') {
        $counterStmt = $conn->prepare("
            UPDATE motorcycles 
            SET report_count = COALESCE(report_count, 0) + 1 
            WHERE id = ?
        ");
        $counterStmt->bind_param("s", $reportedId);
        $counterStmt->execute();
        $counterStmt->close();
    }

    // Commit transaction
    $conn->commit();

    // ---------------------------
    // SEND EMAIL NOTIFICATION TO ADMIN (optional)
    // ---------------------------
    // Uncomment if you want email notifications
    /*
    $adminEmail = "admin@cargo.com";
    $subject = "New Report Submitted - CarGo";
    $message = "
        A new report has been submitted.
        
        Report ID: $reportId
        Type: $reportType
        Reason: $reason
        Reporter: {$reporter['fullname']} ({$reporter['email']})
        
        Please review at: http://yourdomain.com/admin/manage_reports.php?id=$reportId
    ";
    mail($adminEmail, $subject, $message);
    */

    // ---------------------------
    // SUCCESS RESPONSE
    // ---------------------------
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Report submitted successfully. Our team will review it within 24-48 hours.",
        "report_id" => $reportId,
        "data" => [
            "report_id" => $reportId,
            "status" => "pending",
            "created_at" => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to submit report. Please try again later.",
        "debug" => $e->getMessage() // Remove in production
    ]);
}

$conn->close();
?>