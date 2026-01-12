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
$paymentIntentId = $input['payment_intent_id'] ?? null;
$paymentMethodId = $input['payment_method_id'] ?? null;

if (!$paymentIntentId || !$paymentMethodId) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Attach payment method to payment intent
$attachData = [
    'data' => [
        'attributes' => [
            'payment_method' => $paymentMethodId
        ]
    ]
];

$result = paymongoRequest("/payment_intents/{$paymentIntentId}/attach", 'POST', $attachData);

if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode($result);
    exit;
}

// Check if requires 3D Secure
$status = $result['data']['attributes']['status'];
$nextAction = $result['data']['attributes']['next_action'] ?? null;

if ($status === 'awaiting_next_action' && $nextAction) {
    // Return redirect URL for 3D Secure
    echo json_encode([
        'success' => true,
        'requires_action' => true,
        'redirect_url' => $nextAction['redirect']['url'],
        'status' => $status
    ]);
} else if ($status === 'succeeded') {
    // Payment completed immediately
    echo json_encode([
        'success' => true,
        'requires_action' => false,
        'status' => $status
    ]);
} else {
    echo json_encode([
        'success' => false,
        'status' => $status,
        'message' => 'Payment processing failed'
    ]);
}