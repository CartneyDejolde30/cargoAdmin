<?php
require_once 'config.php';
require_once 'include/db.php';

// Get webhook payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

// Verify signature
function verifyWebhookSignature($payload, $signature) {
    $expected = hash_hmac('sha256', $payload, PAYMONGO_WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}

if (!verifyWebhookSignature($payload, $sigHeader)) {
    http_response_code(400);
    error_log("Invalid webhook signature");
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$eventType = $event['data']['attributes']['type'] ?? '';

error_log("PayMongo Webhook: " . $eventType);

try {
    switch ($eventType) {
        case 'payment.paid':
            handlePaymentSuccess($event, $conn);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($event, $conn);
            break;
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
}

function handlePaymentSuccess($event, $conn) {
    $paymentIntentId = $event['data']['attributes']['data']['id'];
    $metadata = $event['data']['attributes']['data']['attributes']['metadata'] ?? [];
    $bookingId = $metadata['booking_id'] ?? null;
    
    if (!$bookingId) {
        error_log("No booking_id in metadata");
        return;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update payment
        $stmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'verified', verified_at = NOW()
            WHERE payment_reference = ? AND booking_id = ?
        ");
        $stmt->bind_param("si", $paymentIntentId, $bookingId);
        $stmt->execute();
        
        // Get payment details for escrow
        $stmt = $conn->prepare("
            SELECT p.id, b.total_amount, b.owner_id 
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE p.payment_reference = ? AND p.booking_id = ?
        ");
        $stmt->bind_param("si", $paymentIntentId, $bookingId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        
        if ($payment) {
            // Calculate fees (10% platform fee)
            $totalAmount = $payment['total_amount'];
            $platformFee = $totalAmount * 0.10;
            $ownerPayout = $totalAmount - $platformFee;
            
            // Insert into escrow
            $stmt = $conn->prepare("
                INSERT INTO escrow (booking_id, payment_id, amount, status, held_at)
                VALUES (?, ?, ?, 'held', NOW())
            ");
            $stmt->bind_param("iid", $bookingId, $payment['id'], $totalAmount);
            $stmt->execute();
            
            // Update booking
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET payment_status = 'paid',
                    escrow_status = 'held',
                    platform_fee = ?,
                    owner_payout = ?,
                    escrow_held_at = NOW(),
                    payment_verified_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ddi", $platformFee, $ownerPayout, $bookingId);
            $stmt->execute();
            
            // Log transaction
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions 
                (booking_id, transaction_type, amount, description)
                VALUES 
                (?, 'payment', ?, 'Payment received via PayMongo'),
                (?, 'escrow_hold', ?, 'Funds held in escrow')
            ");
            $stmt->bind_param("idid", $bookingId, $totalAmount, $bookingId, $totalAmount);
            $stmt->execute();
            
            // Notify renter
            $stmt = $conn->prepare("
                SELECT user_id FROM bookings WHERE id = ?
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $userId = $stmt->get_result()->fetch_assoc()['user_id'];
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message)
                VALUES (?, 'Payment Confirmed ✓', 'Your payment for booking #? has been confirmed.')
            ");
            $stmt->bind_param("ii", $userId, $bookingId);
            $stmt->execute();
        }
        
        mysqli_commit($conn);
        error_log("Payment successful for booking #$bookingId");
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Payment processing failed: " . $e->getMessage());
        throw $e;
    }
}

function handlePaymentFailed($event, $conn) {
    $paymentIntentId = $event['data']['attributes']['data']['id'];
    
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'failed'
        WHERE payment_reference = ?
    ");
    $stmt->bind_param("s", $paymentIntentId);
    $stmt->execute();
    
    error_log("Payment failed: $paymentIntentId");
}