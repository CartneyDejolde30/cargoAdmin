<?php
header("Content-Type: application/json");
require_once "../include/db.php";

// ---------------------------
// ALLOW POST ONLY
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$reportedId = intval($_POST['reported_id'] ?? 0);
$reason     = trim($_POST['reason'] ?? "");
$details   = trim($_POST['details'] ?? "");

// ---------------------------
// VALIDATION
// ---------------------------
if ($reporterId <= 0 || !$reportType || $reportedId <= 0 || !$reason || !$details) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

$allowedTypes = ['car', 'motorcycle', 'user', 'booking', 'chat'];
if (!in_array($reportType, $allowedTypes)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid report type"
    ]);
    exit;
}

// ---------------------------
// VERIFY USER EXISTS
// ---------------------------
$checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
$checkUser->bind_param("i", $reporterId);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Reporter not found"
    ]);
    exit;
}
$checkUser->close();

// ---------------------------
// INSERT REPORT
// ---------------------------
$stmt = $conn->prepare("
    INSERT INTO reports 
    (reporter_id, report_type, reported_id, reason, details, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
");

$stmt->bind_param(
    "isiss",
    $reporterId,
    $reportType,
    $reportedId,
    $reason,
    $details
);

// ---------------------------
// RESPONSE
// ---------------------------
if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Report submitted"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
