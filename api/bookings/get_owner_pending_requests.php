<?php
// ========================================
// api/bookings/get_owner_pending_requests.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php'; // FIXED: Go up TWO levels to reach carGOAdmin/include/db.php

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

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
    b.full_name AS renter_name,
    b.email AS renter_email,
    b.contact AS renter_contact,
    
    -- Refund fields
    b.refund_status,
    b.refund_requested,
    b.refund_amount,
    
    -- Car Details
    c.brand,
    c.model,
    c.car_year,
    c.image AS car_image,
    c.location,
    c.price_per_day,
    c.seat
    
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
WHERE b.owner_id = ?
AND b.status = 'pending'
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $carName = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));
    
    $carImage = $row['car_image'] ?? '';
    if (!empty($carImage) && strpos($carImage, 'http') !== 0) {
        $carImage = 'http://10.77.127.2/carGOAdmin/' . $carImage;
    }
    
    // Calculate rental days
    $days = max(1, (strtotime($row['return_date']) - strtotime($row['pickup_date'])) / 86400);
    
    $requests[] = [
        'booking_id' => $row['booking_id'],
        'car_name' => $carName,
        'car_full_name' => $carName . ' ' . $row['car_year'],
        'car_image' => $carImage,
        'location' => $row['location'] ?? '',
        'seats' => $row['seat'] . '-seater',
        'price_per_day' => $row['price_per_day'] ?? 0,
        'renter_name' => $row['renter_name'] ?? 'Unknown',
        'renter_email' => $row['renter_email'] ?? '',
        'renter_contact' => $row['renter_contact'] ?? '',
        'pickup_date' => date('M d, Y', strtotime($row['pickup_date'])),
        'return_date' => date('M d, Y', strtotime($row['return_date'])),
        'pickup_time' => date('h:i A', strtotime($row['pickup_time'])),
        'return_time' => date('h:i A', strtotime($row['return_time'])),
        'total_amount' => number_format($row['total_amount'], 0),
        'rental_period' => $days . ' day' . ($days > 1 ? 's' : ''),
        'status' => 'pending',
        'created_at' => date('M d, Y - h:i A', strtotime($row['created_at'])),
        
        // Refund status fields
        'refund_status' => $row['refund_status'] ?? 'not_requested',
        'refund_requested' => (int)($row['refund_requested'] ?? 0),
        'refund_amount' => (float)($row['refund_amount'] ?? 0),
    ];
}

echo json_encode([
    'success' => true,
    'requests' => $requests
]);

$stmt->close();
$conn->close();
?>