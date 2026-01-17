<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../include/db.php";
require_once __DIR__ . "/payment/paymongo/config.php";

$response = ["success" => false, "message" => ""];

/* =========================================================
   1️⃣ AUTHENTICATION (MOBILE + WEB)
========================================================= */
$userId = null;

if (!empty($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);

    $stmt = $conn->prepare("
        SELECT u.id,
               CASE WHEN uv.status = 'approved' THEN 1 ELSE 0 END AS is_verified
        FROM users u
        LEFT JOIN user_verifications uv ON u.id = uv.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["success"=>false,"message"=>"User not found"]);
        exit;
    }

    if ($user['is_verified'] != 1) {
        echo json_encode(["success"=>false,"message"=>"Account not verified"]);
        exit;
    }

} elseif (!empty($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
} else {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

/* =========================================================
   2️⃣ BASIC VALIDATION
========================================================= */
if (
    empty($_POST['vehicle_type']) ||
    empty($_POST['vehicle_id']) ||
    empty($_POST['pickup_date']) ||
    empty($_POST['return_date'])
) {
    echo json_encode(["success"=>false,"message"=>"Missing required fields"]);
    exit;
}

$vehicleType = $_POST['vehicle_type'];
$vehicleId   = intval($_POST['vehicle_id']);

if (!in_array($vehicleType, ['car','motorcycle'])) {
    echo json_encode(["success"=>false,"message"=>"Invalid vehicle type"]);
    exit;
}

$table = ($vehicleType === 'motorcycle') ? 'motorcycles' : 'cars';


/* =========================================================
   3️⃣ TIME + OTHER INPUTS
========================================================= */
$pickupDate = $_POST['pickup_date'];
$returnDate = $_POST['return_date'];

$pickupTime = $_POST['pickup_time'] ?? '09:00';
$returnTime = $_POST['return_time'] ?? '18:00';

$pickupTime = date("H:i:s", strtotime($pickupTime));
$returnTime = date("H:i:s", strtotime($returnTime));

$rentalPeriod = $_POST['rental_period'] ?? 'Day';
$needsDelivery = intval($_POST['needs_delivery'] ?? 0);
$fullName = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$contact = $_POST['contact'] ?? '';

mysqli_begin_transaction($conn);

try {

    /* =========================================================
       4️⃣ FETCH VEHICLE (CAR OR MOTORCYCLE)
    ========================================================= */
    $stmt = $conn->prepare("
        SELECT price_per_day, owner_id, status
        FROM {$table}
        WHERE id = ?
    ");
    $stmt->bind_param("i", $vehicleId);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        throw new Exception(ucfirst($vehicleType) . " not found");
    }

    if ($vehicle['status'] !== 'approved') {
        throw new Exception(ucfirst($vehicleType) . " not available");
    }

    /* =========================================================
       5️⃣ CALCULATE TOTAL
    ========================================================= */
    $pickup = strtotime($pickupDate);
    $return = strtotime($returnDate);
    $days = max(1, ceil(($return - $pickup) / 86400));

    $totalAmount = isset($_POST['total_amount'])
        ? floatval($_POST['total_amount'])
        : ($days * $vehicle['price_per_day']);

    /* =========================================================
       6️⃣ CREATE BOOKING
    ========================================================= */
    $stmt = $conn->prepare("
    INSERT INTO bookings
    (user_id, vehicle_type, car_id, owner_id, pickup_date, return_date, pickup_time, return_time,
     total_amount, price_per_day, rental_period, needs_delivery,
     full_name, email, contact, status, payment_status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
");


    $stmt->bind_param(
    "isiissssddsisss",
    $userId,
    $vehicleType,     // <-- THIS is what was missing
    $vehicleId,
    $vehicle['owner_id'],
    $pickupDate,
    $returnDate,
    $pickupTime,
    $returnTime,
    $totalAmount,
    $vehicle['price_per_day'],
    $rentalPeriod,
    $needsDelivery,
    $fullName,
    $email,
    $contact
);


    if (!$stmt->execute()) {
        throw new Exception("Failed to create booking");
    }

    $bookingId = $stmt->insert_id;

    /* =========================================================
       7️⃣ PAYMONGO PAYMENT INTENT
    ========================================================= */
    $paymentIntent = paymongoRequest('/payment_intents', 'POST', [
        'data' => [
            'attributes' => [
                'amount' => (int)($totalAmount * 100),
                'currency' => 'PHP',
                'payment_method_allowed' => ['gcash','paymaya','card'],
                'description' => "CarGo Booking #{$bookingId}",
                'metadata' => [
                    'booking_id' => (string)$bookingId,
                    'user_id' => (string)$userId,
                    'vehicle_type' => $vehicleType
                ]
            ]
        ]
    ]);

    if (isset($paymentIntent['error'])) {
        throw new Exception("Payment error");
    }

    $paymentIntentId = $paymentIntent['data']['id'];
    $clientKey = $paymentIntent['data']['attributes']['client_key'];

    /* =========================================================
       8️⃣ STORE PAYMENT
    ========================================================= */
    $stmt = $conn->prepare("
        INSERT INTO payments
        (booking_id, user_id, amount, payment_method, payment_reference, payment_status, created_at)
        VALUES (?, ?, ?, 'paymongo', ?, 'pending', NOW())
    ");
    $stmt->bind_param("iids", $bookingId, $userId, $totalAmount, $paymentIntentId);
    $stmt->execute();

    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "message" => "Booking created",
        "data" => [
            "booking_id" => $bookingId,
            "total_amount" => $totalAmount,
            "payment_intent_id" => $paymentIntentId,
            "client_key" => $clientKey
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}

$conn->close();