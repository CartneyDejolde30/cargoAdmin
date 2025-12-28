<?php
require_once 'config.php';
require_once 'include/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;
$amount = $input['amount'] ?? null;

if (!$bookingId || !$amount) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Convert to centavos
$amountInCentavos = (int)($amount * 100);

// Create Payment Intent
$paymentIntentData = [
    'data' => [
        'attributes' => [
            'amount' => $amountInCentavos,
            'payment_method_allowed' => ['gcash', 'paymaya', 'card'],
            'payment_method_options' => [
                'card' => ['request_three_d_secure' => 'any']
            ],
            'currency' => 'PHP',
            'description' => "CarGo Booking #$bookingId",
            'statement_descriptor' => 'CarGo Rental',
            'metadata' => ['booking_id' => (string)$bookingId]
        ]
    ]
];

$result = paymongoRequest('/payment_intents', 'POST', $paymentIntentData);

if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode($result);
    exit;
}

// Store in database
try {
    $paymentIntentId = $result['data']['id'];
    $clientKey = $result['data']['attributes']['client_key'];
    
    // Get user_id from booking
    $stmt = $conn->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $userId = $stmt->get_result()->fetch_assoc()['user_id'];
    
    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_reference, payment_status)
        VALUES (?, ?, ?, 'paymongo', ?, 'pending')
    ");
    $stmt->bind_param("iids", $bookingId, $userId, $amount, $paymentIntentId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'payment_intent_id' => $paymentIntentId,
        'client_key' => $clientKey,
        'status' => $result['data']['attributes']['status']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}