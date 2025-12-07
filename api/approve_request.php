<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "../include/db.php";

$response = ["success" => false, "message" => ""];

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(["success" => false, "message" => "Missing booking_id"]);
    exit;
}

// Update status
$update = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ? AND status = 'pending' LIMIT 1");
$update->bind_param("i", $booking_id);
$update->execute();

if ($update->affected_rows <= 0) {
    echo json_encode(["success" => false, "message" => "Booking not found or not pending"]);
    exit;
}
$update->close();

// Fetch booking + car data
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

$renter_id = $booking['user_id'];
$owner_id_from_car = $booking['owner_id'];
$car_name = $booking['car_name'];

// Renter notification
$title_renter = "Booking Approved";
$body_renter  = "Your booking for {$car_name} has been approved by the owner.";

$notif_r = $conn->prepare("
    INSERT INTO notifications (user_id, title, message)
    VALUES (?, ?, ?)
");
$notif_r->bind_param("iss", $renter_id, $title_renter, $body_renter);
$notif_r->execute();
$notif_r->close();

// Owner notification
$title_owner = "You Approved a Booking";
$body_owner  = "You approved booking #{$booking_id} for {$car_name}.";

$notif_o = $conn->prepare("
    INSERT INTO notifications (user_id, title, message)
    VALUES (?, ?, ?)
");
$notif_o->bind_param("iss", $owner_id_from_car, $title_owner, $body_owner);
$notif_o->execute();
$notif_o->close();

echo json_encode([
    "success" => true,
    "message" => "Booking approved and notification saved.",
    "booking_id" => $booking_id
]);

$conn->close();
?>
