<?php
// ✅ CRITICAL: Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . "/../include/db.php";

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

if (!$check) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
    exit;
}

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
  
  if (!$stmt) {
    throw new Exception("Failed to prepare payment statement: " . $conn->error);
  }
  
  $stmt->bind_param(
    "iidss",
    $bookingId,
    $userId,
    $amount,
    $method,
    $refNo
  );

  if (!$stmt->execute()) {
    throw new Exception("Failed to record payment: " . $stmt->error);
  }

  $paymentId = $stmt->insert_id;

  /* =========================================================
     UPDATE BOOKING WITH PAYMENT INFO
     ✅ FIXED: Handle varbinary columns properly
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
  
  if (!$stmt) {
    throw new Exception("Failed to prepare booking update: " . $conn->error);
  }
  
  // ✅ FIXED: Use 'ssssi' (all strings) instead of 'isssi'
  // payment_id is varchar(255) in bookings table
  $paymentIdStr = (string)$paymentId;
  
  $stmt->bind_param(
    "ssssi",
    $paymentIdStr,
    $method,
    $gcashNo,
    $refNo,
    $bookingId
  );

  if (!$stmt->execute()) {
    throw new Exception("Failed to update booking: " . $stmt->error);
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
    "message" => $e->getMessage()
  ]);
}

$conn->close();
?>