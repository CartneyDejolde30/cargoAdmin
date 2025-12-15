<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "include/db.php";

$response = ["success" => false, "message" => ""];

// Required fields
$required_fields = [
    "car_id", "owner_id", "user_id", "full_name", "email", "contact",
    "pickup_date", "return_date", "pickup_time", "return_time",
    "rental_period", "needs_delivery", "total_amount", "payment_method"
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

$paymentMethod = mysqli_real_escape_string($conn, $_POST["payment_method"]);
$gcashNumber   = isset($_POST["gcash_number"]) ? mysqli_real_escape_string($conn, $_POST["gcash_number"]) : null;
$gcashReference = isset($_POST["gcash_reference"]) ? mysqli_real_escape_string($conn, $_POST["gcash_reference"]) : null;

// Calculate platform fee and owner payout
$platformFeeQuery = mysqli_query($conn, "SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_commission_rate'");
$platformFeeRate = 10; // Default 10%
if ($platformFeeQuery && mysqli_num_rows($platformFeeQuery) > 0) {
    $row = mysqli_fetch_assoc($platformFeeQuery);
    $platformFeeRate = floatval($row['setting_value']);
}

$platformFee = round(($totalAmount * $platformFeeRate) / 100, 2);
$ownerPayout = $totalAmount - $platformFee;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert booking
    $sql = "INSERT INTO bookings (
                car_id, owner_id, user_id, full_name, email, contact,
                pickup_date, return_date, pickup_time, return_time,
                rental_period, needs_delivery, status, total_amount,
                payment_method, gcash_number, gcash_reference,
                payment_status, platform_fee, owner_payout
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, 'pending', ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiissssssssiidssddd",
        $carId, $ownerId, $userId, $fullName, $email, $contact,
        $pickupDate, $returnDate, $pickupTime, $returnTime,
        $rentalPeriod, $needsDelivery, $totalAmount,
        $paymentMethod, $gcashNumber, $gcashReference,
        $platformFee, $ownerPayout
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create booking: " . $stmt->error);
    }

    $bookingId = $stmt->insert_id;

    // Insert payment record
    $paymentSql = "INSERT INTO payments (
                    booking_id, user_id, amount, payment_method,
                    payment_reference, payment_status
                ) VALUES (?, ?, ?, ?, ?, 'pending')";

    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->bind_param("iidss", $bookingId, $userId, $totalAmount, $paymentMethod, $gcashReference);

    if (!$paymentStmt->execute()) {
        throw new Exception("Failed to create payment record: " . $paymentStmt->error);
    }

    $paymentId = $paymentStmt->insert_id;

    // Insert transaction log
    $transactionSql = "INSERT INTO payment_transactions (
                        booking_id, transaction_type, amount, description, reference_id
                    ) VALUES (?, 'payment', ?, 'Payment submitted by customer', ?)";

    $tranStmt = $conn->prepare($transactionSql);
    $tranStmt->bind_param("idi", $bookingId, $totalAmount, $paymentId);
    $tranStmt->execute();

    // Commit transaction
    mysqli_commit($conn);

    $response["success"] = true;
    $response["message"] = "Booking created successfully! Payment is pending verification.";
    $response["booking_id"] = $bookingId;
    $response["payment_id"] = $paymentId;
    $response["platform_fee"] = $platformFee;
    $response["owner_payout"] = $ownerPayout;

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $response["message"] = "Transaction failed: " . $e->getMessage();
}

echo json_encode($response);

$conn->close();
?>