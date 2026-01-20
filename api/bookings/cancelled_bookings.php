<?php
// ============================================
// FILE 2: api/bookings/cancelled_bookings.php
// ============================================
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../include/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID is required']);
    exit;
}

$query = "SELECT 
            b.id as booking_id,
            b.total_amount,
            b.pickup_date,
            b.return_date,
            b.status,
            b.cancellation_reason,
            b.cancelled_at,
            u.full_name as renter_name,
            u.contact_number as renter_contact,
            c.brand,
            c.model,
            c.images as car_image,
            CONCAT(c.brand, ' ', c.model) as car_full_name
          FROM bookings b
          INNER JOIN users u ON b.user_id = u.id
          INNER JOIN cars c ON b.car_id = c.id
          WHERE c.owner_id = ? 
          AND b.status = 'cancelled'
          ORDER BY b.cancelled_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    // Parse car images
    $images = json_decode($row['car_image'], true);
    $carImage = !empty($images) && isset($images[0]) ? $images[0] : 'default_car.png';

    $bookings[] = [
        'booking_id' => $row['booking_id'],
        'total_amount' => $row['total_amount'],
        'pickup_date' => $row['pickup_date'],
        'return_date' => $row['return_date'],
        'status' => $row['status'],
        'cancellation_reason' => $row['cancellation_reason'],
        'cancelled_at' => $row['cancelled_at'],
        'renter_name' => $row['renter_name'],
        'renter_contact' => $row['renter_contact'],
        'car_image' => $carImage,
        'car_full_name' => $row['car_full_name']
    ];
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings,
    'count' => count($bookings)
]);

$stmt->close();
$conn->close();
?>