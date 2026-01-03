<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../include/db.php";
require_once __DIR__ . "/payment/paymongo/config.php";

$response = ["success" => false, "message" => ""];

// ✅ FIXED: Accept user_id from POST data (for mobile apps) OR session (for web)
$userId = null;

// Check POST data first (mobile app)
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    
    // ✅ FIXED: Query the user_verifications table instead
    $stmt = $conn->prepare("
        SELECT u.id as user_id, 
               CASE WHEN uv.status = 'approved' THEN 1 ELSE 0 END as is_verified,
               CASE WHEN uv.status = 'approved' THEN 1 ELSE 0 END as can_add_car
        FROM users u
        LEFT JOIN user_verifications uv ON u.id = uv.user_id
        WHERE u.id = ?
    ");
    
    if (!$stmt) {
        $response["message"] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows === 0) {
        $response["message"] = "User not found";
        echo json_encode($response);
        exit;
    }
    
    $user = $userResult->fetch_assoc();
    
    // Check if user is verified
    if ($user['is_verified'] != 1 || $user['can_add_car'] != 1) {
        $response["message"] = "Unauthorized: Account not verified";
        echo json_encode($response);
        exit;
    }
    
} elseif (isset($_SESSION['user_id'])) {
    // Fallback to session (web app)
    $userId = intval($_SESSION['user_id']);
} else {
    $response["message"] = "Unauthorized: No user ID provided";
    echo json_encode($response);
    exit;
}

// Validate input
if (empty($_POST['car_id']) || empty($_POST['pickup_date']) || empty($_POST['return_date'])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$carId = intval($_POST['car_id']);
$pickupDate = $_POST['pickup_date'];
$returnDate = $_POST['return_date'];

// ✅ FIXED: Convert 12-hour format to 24-hour format
$pickupTime = $_POST['pickup_time'] ?? '09:00';
$returnTime = $_POST['return_time'] ?? '18:00';

// Convert "9:00 AM" to "09:00:00" format if needed
if (stripos($pickupTime, 'AM') !== false || stripos($pickupTime, 'PM') !== false) {
    $pickupTime = date("H:i:s", strtotime($pickupTime));
}
if (stripos($returnTime, 'AM') !== false || stripos($returnTime, 'PM') !== false) {
    $returnTime = date("H:i:s", strtotime($returnTime));
}
$rentalPeriod = $_POST['rental_period'] ?? 'Day';
$needsDelivery = isset($_POST['needs_delivery']) ? intval($_POST['needs_delivery']) : 0;
$fullName = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$contact = $_POST['contact'] ?? '';

mysqli_begin_transaction($conn);

try {
    // Get car details
    $stmt = $conn->prepare("
        SELECT price_per_day, owner_id, status 
        FROM cars 
        WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();
    
    if (!$car) {
        throw new Exception("Car not found");
    }
    
    if ($car['status'] !== 'approved') {
        throw new Exception("Car not available");
    }
    
    // Calculate days and total
    $pickup = strtotime($pickupDate);
    $return = strtotime($returnDate);
    $days = max(1, ceil(($return - $pickup) / 86400));
    
    // Use total_amount from POST if provided (includes delivery, discounts, etc.)
    $totalAmount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : ($days * $car['price_per_day']);
    
    // Create booking with additional fields
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, car_id, owner_id, pickup_date, return_date, pickup_time, return_time, 
         total_amount, price_per_day, rental_period, needs_delivery, 
         full_name, email, contact, status, payment_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param(
        "iiissssddsisss", 
        $userId, $carId, $car['owner_id'], 
        $pickupDate, $returnDate, $pickupTime, $returnTime,
        $totalAmount, $car['price_per_day'], $rentalPeriod, $needsDelivery,
        $fullName, $email, $contact
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create booking: " . $stmt->error);
    }
    
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
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
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
    error_log("Booking Error: " . $e->getMessage());
}

echo json_encode($response);
$conn->close();
?>