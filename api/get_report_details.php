<?php
session_start();
require_once '../include/db.php';

header("Content-Type: application/json");

// Admin check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Missing report ID"]);
    exit;
}

$reportId = intval($_GET['id']);

$sql = "
SELECT 
    r.*,
    reporter.fullname AS reporter_name,
    reporter.email AS reporter_email,
    admin.fullname AS reviewer_name,

    CASE 
        WHEN r.report_type = 'car' THEN 
            (SELECT CONCAT(brand, ' ', model) FROM cars WHERE id = r.reported_id)
        WHEN r.report_type = 'motorcycle' THEN 
            (SELECT CONCAT(brand, ' ', model) FROM motorcycles WHERE id = r.reported_id)
        WHEN r.report_type = 'user' THEN 
            (SELECT fullname FROM users WHERE id = r.reported_id)
        WHEN r.report_type = 'booking' THEN 
            CONCAT('Booking #', r.reported_id)
        WHEN r.report_type = 'chat' THEN 
            CONCAT('Chat #', r.reported_id)
        ELSE 'Unknown'
    END AS reported_item_name

FROM reports r
LEFT JOIN users reporter ON r.reporter_id = reporter.id
LEFT JOIN admin ON r.reviewed_by = admin.id
WHERE r.id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reportId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Report not found"]);
    exit;
}

$report = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "report" => $report
]);
