<?php
include "../include/db.php";

$user_id = $_GET['user_id'];

$sql = "
SELECT
  b.id AS bookingId,
  c.car_name AS carName,
  c.image AS carImage,
  c.location,

  DATE_FORMAT(b.pickup_date, '%b %d, %Y') AS pickupDate,
  DATE_FORMAT(b.return_date, '%b %d, %Y') AS returnDate,
  b.pickup_time AS pickupTime,
  b.return_time AS returnTime,

  FORMAT(b.total_amount, 0) AS totalPrice,
  b.status
FROM bookings b
JOIN cars c ON c.id = b.car_id
WHERE b.user_id = ?
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}

echo json_encode($bookings);
