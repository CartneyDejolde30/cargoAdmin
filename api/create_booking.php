<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "../include/db.php";

$response = ["success" => false, "message" => ""];

// Required fields
$required_fields = [
    "car_id", "owner_id", "user_id", "full_name", "email", "contact",
    "pickup_date", "return_date", "pickup_time", "return_time",
    "rental_period", "needs_delivery", "total_amount"
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        $response["message"] = "Missing field: $field";
        echo json_encode($response);
        exit;
    }
}

// Sanitize input
$carId         = intval($_POST["car_id"]);
$ownerId       = intval($_POST["owner_id"]);
$userId        = intval($_POST["user_id"]);

$fullName      = mysqli_real_escape_string($conn, $_POST["full_name"]);
$email         = mysqli_real_escape_string($conn, $_POST["email"]);
$contact       = mysqli_real_escape_string($conn, $_POST["contact"]);

$pickupDate    = $_POST["pickup_date"];
$returnDate    = $_POST["return_date"];
$pickupTime    = $_POST["pickup_time"];
$returnTime    = $_POST["return_time"];

$rentalPeriod  = mysqli_real_escape_string($conn, $_POST["rental_period"]);
$needsDelivery = intval($_POST["needs_delivery"]);
$totalAmount   = floatval($_POST["total_amount"]);

// SQL Insert
$sql = "INSERT INTO bookings (
            car_id, owner_id, user_id, full_name, email, contact,
            pickup_date, return_date, pickup_time, return_time,
            rental_period, needs_delivery, status, total_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiissssssssid",
    $carId,
    $ownerId,
    $userId,
    $fullName,
    $email,
    $contact,
    $pickupDate,
    $returnDate,
    $pickupTime,
    $returnTime,
    $rentalPeriod,
    $needsDelivery,
    $totalAmount
);



if ($stmt->execute()) {
    $response["success"] = true;
    $response["message"] = "Booking created successfully!";
    $response["booking_id"] = $stmt->insert_id;
} else {
    $response["message"] = "Database error: " . $stmt->error;
}

echo json_encode($response);
$stmt->close();
$conn->close();
?>
