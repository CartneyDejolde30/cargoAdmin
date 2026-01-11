<?php
/**
 * REQUEST REFUND
 * Submit a refund request for a cancelled booking
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../include/db.php';

// Validate required fields
$required = [
    'booking_id',
    'refund_amount',
    'refund_method',
    'account_number',
    'account_name',
    'refund_reason'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Sanitize input
$bookingId = intval($_POST['booking_id']);
$refundAmount = floatval($_POST['refund_amount']);
$refundMethod = mysqli_real_escape_string($conn, $_POST['refund_method']);
$accountNumber = mysqli_real_escape_string($conn, $_POST['account_number']);
$accountName = mysqli_real_escape_string($conn, $_POST['account_name']);
$refundReason = mysqli_real_escape_string($conn, $_POST['refund_reason']);
$reasonDetails = mysqli_real_escape_string($conn, $_POST['reason_details'] ?? '');
$originalPaymentMethod = mysqli_real_escape_string($conn, $_POST['original_payment_method'] ?? 'gcash');
$originalPaymentReference = mysqli_real_escape_string($conn, $_POST['original_payment_reference'] ?? '');

mysqli_begin_transaction($conn);

try {
    // Verify booking exists and is eligible for refund
    $stmt = $conn->prepare("
        SELECT 
            b.id,
            b.user_id,
            b.owner_id,
            b.total_amount,
            b.status AS booking_status,
            p.payment_status,
            p.id AS payment_id,
            p.amount AS paid_amount
        FROM bookings b
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found");
    }
    
    $booking = $result->fetch_assoc();
    
    // Check if booking is cancelled or rejected
    if (!in_array($booking['booking_status'], ['cancelled', 'rejected'])) {
        throw new Exception("Only cancelled or rejected bookings can be refunded");
    }
    
    // Check if payment was verified
    if ($booking['payment_status'] !== 'verified' && $booking['payment_status'] !== 'paid') {
        throw new Exception("No verified payment found for this booking");
    }
    
    // Check if refund already exists
    $checkStmt = $conn->prepare("
        SELECT id FROM refunds WHERE booking_id = ? AND status != 'rejected'
    ");
    $checkStmt->bind_param("i", $bookingId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("A refund request already exists for this booking");
    }
    
    // Calculate refund amount (could apply cancellation fees here)
    $calculatedRefundAmount = min($refundAmount, $booking['paid_amount']);
    
    // Insert refund request
    $insertStmt = $conn->prepare("
        INSERT INTO refunds (
            booking_id,
            payment_id,
            user_id,
            refund_amount,
            refund_method,
            account_number,
            account_name,
            refund_reason,
            reason_details,
            original_payment_method,
            original_payment_reference,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $insertStmt->bind_param(
        "iiidssssss",
        $bookingId,
        $booking['payment_id'],
        $booking['user_id'],
        $calculatedRefundAmount,
        $refundMethod,
        $accountNumber,
        $accountName,
        $refundReason,
        $reasonDetails,
        $originalPaymentMethod,
        $originalPaymentReference
    );
    
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to create refund request");
    }
    
    $refundId = $insertStmt->insert_id;
    
    // Update booking refund status
    $updateBookingStmt = $conn->prepare("
        UPDATE bookings 
        SET refund_status = 'requested',
            refund_requested_at = NOW()
        WHERE id = ?
    ");
    $updateBookingStmt->bind_param("i", $bookingId);
    $updateBookingStmt->execute();
    
    // Log transaction
    $logStmt = $conn->prepare("
        INSERT INTO payment_transactions 
        (booking_id, transaction_type, amount, description, metadata)
        VALUES (?, 'refund_request', ?, 'Refund requested', ?)
    ");
    
    $metadata = json_encode([
        'refund_id' => $refundId,
        'refund_method' => $refundMethod,
        'reason' => $refundReason
    ]);
    
    $logStmt->bind_param("ids", $bookingId, $calculatedRefundAmount, $metadata);
    $logStmt->execute();
    
    // Notify user
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message)
        VALUES (?, 'Refund Request Submitted', CONCAT('Your refund request of ₱', FORMAT(?, 2), ' is being processed.'))
    ");
    $notifStmt->bind_param("id", $booking['user_id'], $calculatedRefundAmount);
    $notifStmt->execute();
    
    // Notify admin (assuming admin user_id = 1)
    $adminNotifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message)
        VALUES (1, 'New Refund Request', CONCAT('Booking #', ?, ' - Refund amount: ₱', FORMAT(?, 2)))
    ");
    $adminNotifStmt->bind_param("id", $bookingId, $calculatedRefundAmount);
    $adminNotifStmt->execute();
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Refund request submitted successfully',
        'refund_id' => $refundId,
        'refund_amount' => $calculatedRefundAmount,
        'estimated_days' => '3-5 business days'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>