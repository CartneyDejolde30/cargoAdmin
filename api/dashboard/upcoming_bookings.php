<?php
header('Content-Type: application/json');
require_once '../config.php';

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
    $stmt->execute([$owner_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>