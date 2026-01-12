<?php
require_once 'config.php';
require_once 'include/db.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;
$reason = $input['reason'] ?? 'Booking cancelled';

if (!$bookingId) {
    echo json_encode(['error' => 'Missing booking_id']);
    exit;
}

try {
    // Get payment
    $stmt = $conn->prepare("
        SELECT payment_reference, amount 
        FROM payments 
        WHERE booking_id = ? AND payment_status = 'verified'
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        echo json_encode(['error' => 'No verified payment found']);
        exit;
    }
    
    $paymentIntentId = $payment['payment_reference'];
    $amount = (int)($payment['amount'] * 100);
    
    // Create refund
    $refundData = [
        'data' => [
            'attributes' => [
                'amount' => $amount,
                'payment_id' => $paymentIntentId,
                'reason' => 'requested_by_customer',
                'notes' => $reason
            ]
        ]
    ];
    
    $result = paymongoRequest('/refunds', 'POST', $refundData);
    
    if (isset($result['error'])) {
        http_response_code(400);
        echo json_encode($result);
        exit;
    }
    
    // Update database
    $stmt = $conn->prepare("
        UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'refunded', escrow_status = 'refunded'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    // Log
    $stmt = $conn->prepare("
        INSERT INTO payment_transactions 
        (booking_id, transaction_type, amount, description, created_by)
        VALUES (?, 'refund', ?, ?, ?)
    ");
    $stmt->bind_param("idsi", $bookingId, $payment['amount'], $reason, $_SESSION['admin_id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'refund_id' => $result['data']['id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}