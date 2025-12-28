<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../include/db.php";
require_once __DIR__ . "/payment/paymongo/config.php";

$response = ["success" => false, "message" => ""];

// Auth check
if (!isset($_SESSION['user_id'])) {
    $response["message"] = "Unauthorized";
    echo json_encode($response);
    exit;
}

$userId = intval($_SESSION['user_id']);

// Validate input
if (empty($_POST['car_id']) || empty($_POST['pickup_date']) || empty($_POST['return_date'])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$carId = intval($_POST['car_id']);
$pickupDate = $_POST['pickup_date'];
$returnDate = $_POST['return_date'];
$pickupTime = $_POST['pickup_time'] ?? '09:00';
$returnTime = $_POST['return_time'] ?? '18:00';

mysqli_begin_transaction($conn);

try {
    // Get car details
    $stmt = $conn->prepare("
        SELECT price_per_day, owner_id, status 
        FROM cars 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();
    
    if (!$car) {
        throw new Exception("Car not found");
    }
    
    if ($car['status'] !== 'available') {
        throw new Exception("Car not available");
    }
    
    // Calculate days and total
    $pickup = strtotime($pickupDate);
    $return = strtotime($returnDate);
    $days = max(1, ceil(($return - $pickup) / 86400));
    $totalAmount = $days * $car['price_per_day'];
    
    // Create booking
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, car_id, owner_id, pickup_date, return_date, pickup_time, return_time, 
         total_amount, price_per_day, status, payment_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    
    $stmt->bind_param(
        "iiissssd", 
        $userId, $carId, $car['owner_id'], 
        $pickupDate, $returnDate, $pickupTime, $returnTime,
        $totalAmount, $car['price_per_day']
    );
    
    $stmt->execute();
    $bookingId = $stmt->insert_id;
    
    // Create PayMongo Payment Intent
    $amountInCentavos = (int)($totalAmount * 100);
    
    $paymentIntentData = [
        'data' => [
            'attributes' => [
                'amount' => $amountInCentavos,
                'payment_method_allowed' => ['gcash', 'paymaya', 'card'],
                'payment_method_options' => [
                    'card' => ['request_three_d_secure' => 'any']
                ],
                'currency' => 'PHP',
                'description' => "CarGo Booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT),
                'statement_descriptor' => 'CarGo Rental',
                'metadata' => [
                    'booking_id' => (string)$bookingId,
                    'user_id' => (string)$userId
                ]
            ]
        ]
    ];
    
    $paymentIntent = paymongoRequest('/payment_intents', 'POST', $paymentIntentData);
    
    if (isset($paymentIntent['error'])) {
        throw new Exception("Payment gateway error: " . $paymentIntent['message']);
    }
    
    $paymentIntentId = $paymentIntent['data']['id'];
    $clientKey = $paymentIntent['data']['attributes']['client_key'];
    
    // Store payment record
    $stmt = $conn->prepare("
        INSERT INTO payments 
        (booking_id, user_id, amount, payment_method, payment_reference, payment_status, created_at)
        VALUES (?, ?, ?, 'paymongo', ?, 'pending', NOW())
    ");
    $stmt->bind_param("iids", $bookingId, $userId, $totalAmount, $paymentIntentId);
    $stmt->execute();
    
    mysqli_commit($conn);
    
    $response["success"] = true;
    $response["message"] = "Booking created successfully";
    $response["data"] = [
        'booking_id' => $bookingId,
        'total_amount' => $totalAmount,
        'payment_intent_id' => $paymentIntentId,
        'client_key' => $clientKey,
        'payment_methods' => ['gcash', 'paymaya', 'card']
    ];
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();