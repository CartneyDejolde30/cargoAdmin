<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../include/db.php";

$response = ["success" => false, "message" => ""];

// =============================
// REQUIRED FIELDS
// =============================
$required = [
  "car_id",
  "owner_id",
  "user_id",
  "total_amount",
  "payment_method",
  "gcash_number",
  "payment_reference"
];

foreach ($required as $field) {
  if (empty($_POST[$field])) {
    $response["message"] = "Missing field: $field";
    echo json_encode($response);
    exit;
  }
}

// =============================
// SANITIZE INPUT
// =============================
$carId   = intval($_POST["car_id"]);
$ownerId = intval($_POST["owner_id"]);
$userId  = intval($_POST["user_id"]);
$amount  = floatval($_POST["total_amount"]);

$method  = mysqli_real_escape_string($conn, $_POST["payment_method"]);
$gcashNo = mysqli_real_escape_string($conn, $_POST["gcash_number"]);
$refNo   = mysqli_real_escape_string($conn, $_POST["payment_reference"]);

mysqli_begin_transaction($conn);

try {

  // =============================
  // CREATE BOOKING (PENDING)
  // =============================
  $bookingSql = "
    INSERT INTO bookings (
      user_id,
      owner_id,
      car_id,
      total_amount,
      status,
      payment_status,
      escrow_status,
      created_at
    ) VALUES (
      ?, ?, ?, ?, 'pending', 'pending', 'pending', NOW()
    )
  ";

  $stmt = $conn->prepare($bookingSql);
  $stmt->bind_param("iiid", $userId, $ownerId, $carId, $amount);
  $stmt->execute();

  $bookingId = $stmt->insert_id;

  // =============================
  // INSERT PAYMENT
  // =============================
  $paymentSql = "
    INSERT INTO payments (
      booking_id,
      user_id,
      amount,
      payment_method,
      payment_reference,
      payment_status,
      created_at
    ) VALUES (
      ?, ?, ?, ?, ?, 'pending', NOW()
    )
  ";

  $stmt = $conn->prepare($paymentSql);
  $stmt->bind_param(
    "iidss",
    $bookingId,
    $userId,
    $amount,
    $method,
    $refNo
  );
  $stmt->execute();

  // =============================
  // LINK PAYMENT TO BOOKING
  // =============================
  $paymentId = $stmt->insert_id;

  $updateBooking = "
    UPDATE bookings
    SET payment_id = ?, payment_method = ?, payment_date = NOW()
    WHERE id = ?
  ";

  $stmt = $conn->prepare($updateBooking);
  $stmt->bind_param("isi", $paymentId, $method, $bookingId);
  $stmt->execute();

  mysqli_commit($conn);

  echo json_encode([
    "success" => true,
    "message" => "Payment submitted successfully",
    "booking_id" => $bookingId,
    "payment_id" => $paymentId
  ]);

} catch (Exception $e) {
  mysqli_rollback($conn);
  echo json_encode([
    "success" => false,
    "message" => "Server error: " . $e->getMessage()
  ]);
}

$conn->close();
