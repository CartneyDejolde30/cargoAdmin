<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../include/db.php';

$userId = intval($_GET['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$sql = "
SELECT 
    b.id AS bookingId,
    b.car_id AS carId,
    b.owner_id AS ownerId,
    b.pickup_date,
    b.pickup_time,
    b.return_date,
    b.return_time,
    b.total_amount,
    b.status,
    b.created_at,

    c.brand,
    c.model,
    c.image AS carImage,
    c.location,

    u.fullname AS ownerName
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u ON b.owner_id = u.id
WHERE b.user_id = ?
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {

    $bookings[] = [
        'bookingId'   => (int)$row['bookingId'],
        'carId'       => (int)$row['carId'],
        'ownerId'     => (int)$row['ownerId'],

        'carName'     => trim($row['brand'].' '.$row['model']),
        'carImage'    => $row['carImage']
            ? 'http://10.139.150.2/carGOAdmin/'.$row['carImage']
            : '',

        'location'    => $row['location'] ?? 'Location not set',

        // ⚠️ SEND RAW VALUES (FORMAT IN FLUTTER)
        'pickupDate'  => $row['pickup_date'],
        'pickupTime'  => $row['pickup_time'],
        'returnDate'  => $row['return_date'],
        'returnTime'  => $row['return_time'],

        'totalPrice'  => (float)$row['total_amount'],
        'status'      => $row['status'],
        'ownerName'   => $row['ownerName'] ?? 'Unknown',
    ];
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);

$conn->close();
