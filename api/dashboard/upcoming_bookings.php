<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ✅ FIXED: Changed from '../config.php' to '../../include/db.php'
require_once '../../include/db.php';

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            b.id,
            b.car_name,
            c.image as car_image,
            c.brand,
            c.model,
            b.full_name as renter_name,
            b.pickup_date as start_date,
            b.return_date as end_date,
            b.status,
            b.total_amount,
            b.rental_period
        FROM bookings b
        LEFT JOIN cars c ON b.car_id = c.id
        WHERE b.owner_id = ? 
        AND b.status = 'approved'
        AND b.pickup_date >= CURDATE()
        ORDER BY b.pickup_date ASC
        LIMIT 10
    ");
    $stmt->bind_param("s", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>