<?php
/**
 * CHECK PAYMONGO PAYMENT INTENT STATUS
 * Checks the current status of a PayMongo payment intent
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/paymongo/config.php';

// Validate input
if (!isset($_GET['payment_intent_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment intent ID is required'
    ]);
    exit;
}

$paymentIntentId = trim($_GET['payment_intent_id']);

try {
    // Check payment status in database first
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.payment_status,
            p.amount,
            p.payment_reference,
            p.created_at,
            b.user_id
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        WHERE p.payment_reference = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment intent not found in database'
        ]);
        exit;
    }
    
    $payment = $result->fetch_assoc();
    
    // If already verified, return cached status
    if (in_array($payment['payment_status'], ['verified', 'completed', 'paid'])) {
        echo json_encode([
            'success' => true,
            'status' => 'succeeded',
            'payment_status' => $payment['payment_status'],
            'transaction_id' => $payment['id'],
            'cached' => true
        ]);
        exit;
    }
    
    // Query PayMongo API for latest status
    $paymongoResponse = paymongoRequest("/payment_intents/{$paymentIntentId}", 'GET');
    
    if (isset($paymongoResponse['error'])) {
        // Return database status if API call fails
        echo json_encode([
            'success' => true,
            'status' => $payment['payment_status'],
            'transaction_id' => $payment['id'],
            'api_error' => true,
            'message' => 'Using cached status due to API error'
        ]);
        exit;
    }
    
    $intentStatus = $paymongoResponse['data']['attributes']['status'];
    $intentAmount = $paymongoResponse['data']['attributes']['amount'];
    
    // Update payment status if changed
    if ($intentStatus === 'succeeded' && $payment['payment_status'] === 'pending') {
        // Auto-verify the payment
        $updateStmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'verified', 
                verified_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->bind_param("i", $payment['id']);
        $updateStmt->execute();
        
        // Update booking
        $bookingStmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'paid',
                status = 'approved',
                payment_verified_at = NOW()
            WHERE id = ?
        ");
        $bookingStmt->bind_param("i", $payment['booking_id']);
        $bookingStmt->execute();
        
        // Send notification
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (?, 'Payment Successful ✓', 'Your payment has been confirmed automatically!')
        ");
        $notifStmt->bind_param("i", $payment['user_id']);
        $notifStmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'status' => $intentStatus,
        'payment_status' => $intentStatus === 'succeeded' ? 'verified' : $payment['payment_status'],
        'transaction_id' => $payment['id'],
        'amount' => $intentAmount / 100, // Convert centavos to pesos
        'cached' => false
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>