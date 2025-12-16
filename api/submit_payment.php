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
  "booking_id",
  "user_id",
  "total_amount",
  "payment_method",
  "gcash_number",
  "payment_reference"
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
$bookingId   = intval($_POST["booking_id"]);
$userId      = intval($_POST["user_id"]);
$amount      = floatval($_POST["total_amount"]);

$method      = mysqli_real_escape_string($conn, $_POST["payment_method"]);
$gcashNo     = mysqli_real_escape_string($conn, $_POST["gcash_number"]);
$refNo       = mysqli_real_escape_string($conn, $_POST["payment_reference"]);

/* =========================================================
   VERIFY BOOKING EXISTS & UNPAID
   ========================================================= */
$check = $conn->prepare("
  SELECT id, payment_status 
  FROM bookings 
  WHERE id = ? AND user_id = ?
");
$check->bind_param("ii", $bookingId, $userId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
  echo json_encode([
    "success" => false,
    "message" => "Invalid booking"
  ]);
  exit;
}

$booking = $result->fetch_assoc();

if ($booking["payment_status"] !== "pending") {
  echo json_encode([
    "success" => false,
    "message" => "Payment already submitted"
  ]);
  exit;
}

/* =========================================================
   START TRANSACTION
   ========================================================= */
mysqli_begin_transaction($conn);

try {

  /* =========================================================
     INSERT PAYMENT
     ========================================================= */
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

  if (!$stmt->execute()) {
    throw new Exception("Failed to record payment");
  }

  $paymentId = $stmt->insert_id;

  /* =========================================================
     UPDATE BOOKING WITH PAYMENT INFO
     ========================================================= */
  $update = "
    UPDATE bookings
    SET 
      payment_id     = ?,
      payment_method = ?,
      payment_status = 'pending',
      gcash_number   = ?,
      gcash_reference= ?,
      payment_date   = NOW()
    WHERE id = ?
  ";

  $stmt = $conn->prepare($update);
  $stmt->bind_param(
    "isssi",
    $paymentId,
    $method,
    $gcashNo,
    $refNo,
    $bookingId
  );

  if (!$stmt->execute()) {
    throw new Exception("Failed to update booking");
  }

  /* =========================================================
     COMMIT
     ========================================================= */
  mysqli_commit($conn);

  echo json_encode([
    "success"     => true,
    "message"     => "Payment submitted successfully. Awaiting verification.",
    "payment_id"  => $paymentId
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