<?php
/**
 * ============================================================================
 * BLOCK DATES - Vehicle Availability Calendar
 * Owner can block dates when vehicle is unavailable
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

// Validate required fields
$owner_id = $data['owner_id'] ?? null;
$vehicle_id = $data['vehicle_id'] ?? null;
$vehicle_type = $data['vehicle_type'] ?? 'car';
$dates = $data['dates'] ?? []; // Array of dates to block
$reason = $data['reason'] ?? 'Unavailable';

if (!$owner_id || !$vehicle_id || empty($dates)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify ownership
$verify_query = "SELECT id FROM " . ($vehicle_type === 'car' ? 'cars' : 'motorcycles') . " 
                 WHERE id = ? AND owner_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $vehicle_id, $owner_id);
$verify_stmt->execute();
if ($verify_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Vehicle not found or unauthorized']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    $blocked_count = 0;
    $already_blocked = 0;
    
    foreach ($dates as $date) {
        // Check if already blocked
        $check_query = "SELECT id FROM vehicle_availability 
                        WHERE vehicle_id = ? AND vehicle_type = ? AND blocked_date = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iss", $vehicle_id, $vehicle_type, $date);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $already_blocked++;
            continue;
        }
        
        // Check for existing bookings on this date
        $booking_check = "SELECT id FROM bookings 
                         WHERE car_id = ? 
                         AND vehicle_type = ? 
                         AND status IN ('pending', 'approved')
                         AND ? BETWEEN pickup_date AND return_date";
        $booking_stmt = $conn->prepare($booking_check);
        $booking_stmt->bind_param("iss", $vehicle_id, $vehicle_type, $date);
        $booking_stmt->execute();
        
        if ($booking_stmt->get_result()->num_rows > 0) {
            // Skip dates with existing bookings
            continue;
        }
        
        // Insert blocked date
        $insert_query = "INSERT INTO vehicle_availability 
                        (owner_id, vehicle_id, vehicle_type, blocked_date, reason) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iisss", $owner_id, $vehicle_id, $vehicle_type, $date, $reason);
        
        if ($insert_stmt->execute()) {
            $blocked_count++;
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$blocked_count date(s) blocked successfully",
        'blocked_count' => $blocked_count,
        'already_blocked' => $already_blocked
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error blocking dates: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
