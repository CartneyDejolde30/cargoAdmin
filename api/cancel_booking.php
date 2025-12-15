<?php
include "../include/db.php";

if (!isset($_POST['booking_id']) || !isset($_POST['user_id'])) {
  echo json_encode([
    "success" => false,
    "message" => "Missing parameters"
  ]);
  exit;
}

$booking_id = intval($_POST['booking_id']);
$user_id = $_POST['user_id'];

$sql = "
UPDATE bookings
SET status = 'cancelled'
WHERE id = ? 
  AND user_id = ?
  AND status IN ('pending', 'approved')
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $booking_id, $user_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "Failed to cancel booking"
  ]);
}
