<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "../include/db.php";

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$reason     = isset($_POST['reason']) ? trim($_POST['reason']) : "";

if ($booking_id <= 0 || empty($reason)) {
    echo json_encode(["success" => false, "message" => "Missing booking_id or reason"]);
    exit;
}

// Update booking
$update = $conn->prepare("
    UPDATE bookings 
    SET status = 'rejected', reject_reason=? 
    WHERE id = ? AND status='pending' LIMIT 1
");
$update->bind_param("si", $reason, $booking_id);
$update->execute();

if ($update->affected_rows <= 0) {
    echo json_encode(["success" => false, "message" => "Booking not found or not pending"]);
    exit;
}
$update->close();

// Fetch booking info
$q = $conn->prepare("
    SELECT b.*, c.owner_id, c.brand AS car_name
    FROM bookings b
    JOIN cars c ON c.id = b.car_id
    WHERE b.id = ? LIMIT 1
");
$q->bind_param("i", $booking_id);
$q->execute();
$res = $q->get_result();
$booking = $res->fetch_assoc();
$q->close();

if (!$booking) {
    echo json_encode(["success" => false, "message" => "Booking not found"]);
    exit;
}

$renter_id = $booking['user_id'];
$owner_id = $booking['owner_id'];
$car_name = $booking['car_name'];

// Notification for renter
$title_r = "Booking Rejected";
$msg_r   = "Your booking for {$car_name} was rejected. Reason: {$reason}";

$notifR = $conn->prepare("
    INSERT INTO notifications (user_id, title, message)
    VALUES (?, ?, ?)
");
$notifR->bind_param("iss", $renter_id, $title_r, $msg_r);
$notifR->execute();
$notifR->close();

// Notification for owner (log)
$title_o = "Booking Rejected Successfully";
$msg_o   = "You rejected booking #{$booking_id} for {$car_name}.";

$notifO = $conn->prepare("
    INSERT INTO notifications (user_id, title, message)
    VALUES (?, ?, ?)
");
$notifO->bind_param("iss", $owner_id, $title_o, $msg_o);
$notifO->execute();
$notifO->close();

echo json_encode([
    "success" => true,
    "message" => "Booking rejected and notification saved.",
    "booking_id" => $booking_id
]);

$conn->close();
?>
