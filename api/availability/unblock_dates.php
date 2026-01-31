<?php
/**
 * ============================================================================
 * UNBLOCK DATES - Vehicle Availability Calendar
 * Owner can unblock previously blocked dates
 * ============================================================================
 */

// Disable error display to prevent HTML in JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$owner_id = $data['owner_id'] ?? null;
$vehicle_id = $data['vehicle_id'] ?? null;
$vehicle_type = $data['vehicle_type'] ?? 'car';
$dates = $data['dates'] ?? [];

if (!$owner_id || !$vehicle_id || empty($dates)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Delete blocked dates
$placeholders = implode(',', array_fill(0, count($dates), '?'));
$query = "DELETE FROM vehicle_availability 
          WHERE owner_id = ? AND vehicle_id = ? AND vehicle_type = ? 
          AND blocked_date IN ($placeholders)";

$stmt = $conn->prepare($query);

// Bind parameters dynamically
$types = 'iis' . str_repeat('s', count($dates));
$params = array_merge([$owner_id, $vehicle_id, $vehicle_type], $dates);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => $stmt->affected_rows . ' date(s) unblocked successfully',
        'unblocked_count' => $stmt->affected_rows
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error unblocking dates']);
}

$conn->close();
?>
