<?php
/**
 * ============================================================================
 * GET BLOCKED DATES - Vehicle Availability Calendar
 * Get all blocked dates for a specific vehicle
 * ============================================================================
 */

// Disable error display to prevent HTML in JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../include/db.php';

// Check if table exists, if not create it
$checkTable = "SHOW TABLES LIKE 'vehicle_availability'";
$result = mysqli_query($conn, $checkTable);
if (mysqli_num_rows($result) == 0) {
    // Create table
    $createTable = "CREATE TABLE IF NOT EXISTS `vehicle_availability` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `owner_id` INT(11) NOT NULL,
        `vehicle_id` INT(11) NOT NULL,
        `vehicle_type` VARCHAR(20) NOT NULL DEFAULT 'car',
        `blocked_date` DATE NOT NULL,
        `reason` VARCHAR(255) DEFAULT 'Blocked by owner',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_block` (`vehicle_id`, `vehicle_type`, `blocked_date`),
        KEY `idx_owner_id` (`owner_id`),
        KEY `idx_vehicle` (`vehicle_id`, `vehicle_type`),
        KEY `idx_blocked_date` (`blocked_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    mysqli_query($conn, $createTable);
}

$vehicle_id = $_GET['vehicle_id'] ?? null;
$vehicle_type = $_GET['vehicle_type'] ?? 'car';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('+6 months'));

if (!$vehicle_id) {
    echo json_encode(['success' => false, 'message' => 'Vehicle ID required']);
    exit;
}

// Get blocked dates
$query = "SELECT blocked_date, reason, created_at 
          FROM vehicle_availability 
          WHERE vehicle_id = ? AND vehicle_type = ? 
          AND blocked_date BETWEEN ? AND ?
          ORDER BY blocked_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("isss", $vehicle_id, $vehicle_type, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$blocked_dates = [];
while ($row = $result->fetch_assoc()) {
    $blocked_dates[] = $row['blocked_date'];
}

// Also get dates with existing bookings
$booking_query = "SELECT DISTINCT DATE(pickup_date) as booked_date
                  FROM bookings 
                  WHERE car_id = ? 
                  AND vehicle_type = ? 
                  AND status IN ('pending', 'approved', 'ongoing')
                  AND pickup_date BETWEEN ? AND ?
                  UNION
                  SELECT DISTINCT DATE(return_date) as booked_date
                  FROM bookings 
                  WHERE car_id = ? 
                  AND vehicle_type = ? 
                  AND status IN ('pending', 'approved', 'ongoing')
                  AND return_date BETWEEN ? AND ?";

$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("isssisss", 
    $vehicle_id, $vehicle_type, $start_date, $end_date,
    $vehicle_id, $vehicle_type, $start_date, $end_date
);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

$booked_dates = [];
while ($row = $booking_result->fetch_assoc()) {
    $booked_dates[] = $row['booked_date'];
}

echo json_encode([
    'success' => true,
    'blocked_dates' => $blocked_dates,
    'booked_dates' => $booked_dates,
    'vehicle_id' => $vehicle_id,
    'vehicle_type' => $vehicle_type
]);

$conn->close();
?>
