<?php
// api/get_my_bookings.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../include/db.php';

// =========================
// VALIDATE INPUT
// =========================
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$userId = mysqli_real_escape_string($conn, $_GET['user_id']);

// =========================
// FETCH USER BOOKINGS
// =========================
$sql = "
SELECT 
    b.id AS bookingId,
    b.pickup_date AS pickupDate,
    b.pickup_time AS pickupTime,
    b.return_date AS returnDate,
    b.return_time AS returnTime,
    b.total_amount AS totalPrice,
    b.status,
    b.payment_status,
    b.created_at,
    
    -- Car Details (from cars table)
    c.brand,
    c.model,
    c.image AS carImage,
    c.location,
    
    -- Owner Details
    u.fullname AS ownerName,
    u.id AS ownerId
    
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u ON b.owner_id = u.id
WHERE b.user_id = '$userId'
ORDER BY b.created_at DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit;
}

// =========================
// BUILD RESPONSE
// =========================
$bookings = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Build car name from brand + model
    $carName = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));
    if (empty($carName)) {
        $carName = 'Car Details Not Available';
    }
    
    // Format car image URL
    $carImage = $row['carImage'] ?? '';
    if (!empty($carImage) && strpos($carImage, 'http') !== 0) {
        $carImage = 'http://10.244.29.49/carGOAdmin/' . $carImage;
    }
    
    // Format dates
    $pickupDate = date('M d, Y', strtotime($row['pickupDate']));
    $returnDate = date('M d, Y', strtotime($row['returnDate']));
    
    // Format time (remove seconds if present)
    $pickupTime = date('h:i A', strtotime($row['pickupTime']));
    $returnTime = date('h:i A', strtotime($row['returnTime']));
    
    // Format price (remove decimals, add comma separator)
    $totalPrice = number_format($row['totalPrice'], 0);
    
    $bookings[] = [
        'bookingId' => (int)$row['bookingId'],
        'carName' => $carName,
        'carImage' => $carImage,
        'location' => $row['location'] ?? 'Location not set',
        'pickupDate' => $pickupDate,
        'pickupTime' => $pickupTime,
        'returnDate' => $returnDate,
        'returnTime' => $returnTime,
        'totalPrice' => $totalPrice,
        'status' => $row['status'],
        'paymentStatus' => $row['payment_status'] ?? 'unpaid',
        'createdAt' => $row['created_at'],
        'ownerName' => $row['ownerName'] ?? 'Unknown',
        'ownerId' => $row['ownerId'] ?? 0
    ];
}

// =========================
// RETURN SUCCESS
// =========================
echo json_encode($bookings);

mysqli_close($conn);
?>