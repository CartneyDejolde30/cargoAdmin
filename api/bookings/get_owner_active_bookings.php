<?php
// ========================================
// OPTION 1: Show ALL approved bookings (active + upcoming)
// Replace api/bookings/get_owner_active_bookings.php
// ========================================
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

// Show ALL approved bookings (both in-progress AND upcoming)
$sql = "
SELECT 
    b.id AS booking_id,
    b.pickup_date,
    b.return_date,
    b.pickup_time,
    b.return_time,
    b.total_amount,
    b.status,
    b.rental_period,
    b.created_at,
    
    -- Refund fields
    b.refund_status,
    b.refund_requested,
    b.refund_amount,
    
    -- Car Details
    COALESCE(c.brand, m.brand) AS brand,
    COALESCE(c.model, m.model) AS model,
    COALESCE(c.car_year, m.motorcycle_year) AS car_year,
    COALESCE(c.image, m.image) AS car_image,
    COALESCE(c.location, m.location) AS location,
    COALESCE(c.price_per_day, m.price_per_day) AS price_per_day,
    
    -- Renter Details
    u.fullname AS renter_name,
    u.email AS renter_email,
    u.phone AS renter_contact,
    
    -- Calculate days remaining
    DATEDIFF(b.return_date, CURDATE()) AS days_remaining,
    DATEDIFF(CURDATE(), b.pickup_date) AS days_elapsed,
    
    -- Determine if currently active or upcoming
    CASE 
        WHEN b.pickup_date <= CURDATE() AND b.return_date >= CURDATE() THEN 'in_progress'
        WHEN b.pickup_date > CURDATE() THEN 'upcoming'
        ELSE 'past'
    END AS trip_status
    
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id AND b.vehicle_type = 'car'
LEFT JOIN motorcycles m ON b.car_id = m.id AND b.vehicle_type = 'motorcycle'
LEFT JOIN users u ON b.user_id = u.id
WHERE b.owner_id = ?
AND b.status = 'approved'
AND (
    b.return_date >= CURDATE() 
    OR DATEDIFF(CURDATE(), b.return_date) <= 7  -- Show expired bookings up to 7 days past due
)
ORDER BY 
    CASE 
        WHEN b.pickup_date <= CURDATE() THEN 0
        ELSE 1
    END,
    b.pickup_date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $carName = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));
    
    $carImage = $row['car_image'] ?? '';
    if (!empty($carImage) && strpos($carImage, 'http') !== 0) {
        $carImage = 'http://10.77.127.2/carGOAdmin/' . $carImage;
    }
    
    $totalDays = max(1, (strtotime($row['return_date']) - strtotime($row['pickup_date'])) / 86400);
    $daysElapsed = max(0, intval($row['days_elapsed']));
    $progress = min(100, ($daysElapsed / $totalDays) * 100);
    
    $bookings[] = [
        'booking_id' => $row['booking_id'],
        'car_name' => $carName,
        'car_full_name' => $carName . ' ' . $row['car_year'],
        'car_image' => $carImage,
        'location' => $row['location'] ?? '',
        'price_per_day' => $row['price_per_day'] ?? 0,
        'renter_name' => $row['renter_name'] ?? 'Unknown',
        'renter_email' => $row['renter_email'] ?? '',
        'renter_contact' => $row['renter_contact'] ?? '',
        'pickup_date' => date('M d, Y', strtotime($row['pickup_date'])),
        'return_date' => date('M d, Y', strtotime($row['return_date'])),
        'pickup_time' => date('h:i A', strtotime($row['pickup_time'])),
        'return_time' => date('h:i A', strtotime($row['return_time'])),
        'total_amount' => number_format($row['total_amount'], 0),
        'rental_period' => $row['rental_period'],
        'days_remaining' => max(0, intval($row['days_remaining'])),
        'days_elapsed' => $daysElapsed,
        'trip_progress' => round($progress, 1),
        'trip_status' => $row['trip_status'],
        'status' => 'active',
        
        // Refund status fields
        'refund_status' => $row['refund_status'] ?? 'not_requested',
        'refund_requested' => (int)($row['refund_requested'] ?? 0),
        'refund_amount' => (float)($row['refund_amount'] ?? 0),
    ];
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);

$stmt->close();
$conn->close();