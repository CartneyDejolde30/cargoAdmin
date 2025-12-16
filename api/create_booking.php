<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once "../include/db.php";

$response = ["success" => false, "message" => ""];

/* =========================================================
   REQUIRED FIELDS
   ========================================================= */
$required = [
  "car_id",
  "owner_id",
  "user_id",
  "full_name",
  "email",
  "contact",
  "pickup_date",
  "return_date",
  "pickup_time",
  "return_time",
  "rental_period",
  "needs_delivery",
  "total_amount"
];

foreach ($required as $field) {
  if (!isset($_POST[$field]) || $_POST[$field] === "") {
    echo json_encode([
      "success" => false,
      "message" => "Missing field: $field"
    ]);
    exit;
  }
}

/* =========================================================
   SANITIZE INPUT
   ========================================================= */
$carId          = intval($_POST["car_id"]);
$ownerId        = intval($_POST["owner_id"]);
$userId         = intval($_POST["user_id"]);

$fullName       = mysqli_real_escape_string($conn, $_POST["full_name"]);
$email          = mysqli_real_escape_string($conn, $_POST["email"]);
$contact        = mysqli_real_escape_string($conn, $_POST["contact"]);

$pickupDate     = $_POST["pickup_date"];   // yyyy-mm-dd
$returnDate     = $_POST["return_date"];
$pickupTime     = $_POST["pickup_time"];   // HH:mm
$returnTime     = $_POST["return_time"];

$rentalPeriod   = mysqli_real_escape_string($conn, $_POST["rental_period"]);
$needsDelivery  = intval($_POST["needs_delivery"]);
$totalAmount    = floatval($_POST["total_amount"]);

/* =========================================================
   START TRANSACTION
   ========================================================= */
mysqli_begin_transaction($conn);

try {

  /* =========================================================
     INSERT BOOKING (PENDING PAYMENT)
     ========================================================= */
  $sql = "
    INSERT INTO bookings (
      car_id,
      owner_id,
      user_id,
      full_name,
      email,
      contact,
      pickup_date,
      return_date,
      pickup_time,
      return_time,
      rental_period,
      needs_delivery,
      total_amount,
      status,
      payment_status,
      escrow_status,
      created_at
    ) VALUES (
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
      'pending',
      'pending',
      'pending',
      NOW()
    )
  ";

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

  if (!$stmt->execute()) {
    throw new Exception("Failed to create booking");
  }

  $bookingId = $stmt->insert_id;

  /* =========================================================
     COMMIT
     ========================================================= */
  mysqli_commit($conn);

  echo json_encode([
    "success"    => true,
    "message"    => "Booking created successfully",
    "booking_id" => $bookingId
  ]);

} catch (Exception $e) {

  mysqli_rollback($conn);

  echo json_encode([
    "success" => false,
    "message" => "Server error: " . $e->getMessage()
  ]);
}

$conn->close();


?>