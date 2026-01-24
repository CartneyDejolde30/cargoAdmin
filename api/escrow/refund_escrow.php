<?php
/**
 * ============================================================================
 * REFUND ESCROW API - FIXED
 * Refund escrow funds back to renter
 * ============================================================================
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../include/db.php';

// Validate input
if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

if (!isset($_POST['reason']) || empty(trim($_POST['reason']))) {
    echo json_encode(['success' => false, 'message' => 'Refund reason is required']);
    exit;
}

if (!isset($_POST['details']) || empty(trim($_POST['details']))) {
    echo json_encode(['success' => false, 'message' => 'Refund details are required']);
    exit;
}

$bookingId = intval($_POST['booking_id']);
$reason = mysqli_real_escape_string($conn, trim($_POST['reason']));
$details = mysqli_real_escape_string($conn, trim($_POST['details']));
$adminId = $_SESSION['admin_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get booking and payment details
    $bookingQuery = "
        SELECT 
            b.*,
            p.id AS payment_id,
            p.payment_method,
            u_renter.fullname AS renter_name,
            u_renter.email AS renter_email,
            u_renter.gcash_number AS renter_gcash,
            u_owner.fullname AS owner_name,
            u_owner.email AS owner_email
        FROM bookings b
        LEFT JOIN payments p ON b.id = p.booking_id
        LEFT JOIN users u_renter ON b.user_id = u_renter.id
        LEFT JOIN users u_owner ON b.owner_id = u_owner.id
        WHERE b.id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $bookingQuery);
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Validate escrow can be refunded - check for held status (regardless of hold reason)
    if ($booking['escrow_status'] !== 'held') {
        throw new Exception('Can only refund escrow from "held" status. Current: ' . $booking['escrow_status']);
    }
    
    // Calculate refund amount (full amount back to renter)
    $refundAmount = floatval($booking['total_amount']);
    
    // Update escrow status
    $updateEscrow = "
        UPDATE bookings 
        SET 
            escrow_status = 'refunded',
            status = 'cancelled',
            escrow_refunded_at = NOW(),
            escrow_hold_reason = NULL,
            escrow_hold_details = NULL
        WHERE id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $updateEscrow);
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update escrow status: ' . mysqli_error($conn));
    }
    
    // Generate unique refund ID
    $refundId = 'REF-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
    
    // Create refund record
    $createRefund = "
        INSERT INTO refunds (
            refund_id,
            user_id,
            booking_id,
            payment_id,
            refund_amount,
            refund_method,
            account_number,
            account_name,
            refund_reason,
            reason_details,
            original_payment_method,
            original_payment_reference,
            status,
            processed_by,
            processed_at,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, NOW(), NOW())
    ";
    
    $refundMethod = $booking['payment_method'] ?? 'gcash';
    $accountNumber = $booking['renter_gcash'] ?? 'N/A';
    $accountName = $booking['renter_name'];
    $originalPaymentRef = $booking['payment_id'] ?? null;
    
    $stmt = mysqli_prepare($conn, $createRefund);
    mysqli_stmt_bind_param($stmt, "siiidssssssi",
        $refundId,
        $booking['user_id'],
        $bookingId,
        $booking['payment_id'],
        $refundAmount,
        $refundMethod,
        $accountNumber,
        $accountName,
        $reason,
        $details,
        $refundMethod,
        $originalPaymentRef,
        $adminId
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create refund record: ' . mysqli_error($conn));
    }
    
    $newRefundId = mysqli_insert_id($conn);
    
    // Update payment status
    if ($booking['payment_id']) {
        $updatePayment = "
            UPDATE payments 
            SET payment_status = 'refunded'
            WHERE id = ?
        ";
        
        $stmt = mysqli_prepare($conn, $updatePayment);
        mysqli_stmt_bind_param($stmt, "i", $booking['payment_id']);
        mysqli_stmt_execute($stmt);
    }
    
    // Log escrow refund if escrow_logs table exists
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'escrow_logs'");
    if (mysqli_num_rows($checkTable) > 0) {
        $logQuery = "
            INSERT INTO escrow_logs (
                booking_id,
                action,
                previous_status,
                new_status,
                admin_id,
                notes,
                created_at
            ) VALUES (?, 'refund', ?, 'refunded', ?, ?, NOW())
        ";
        
        $logNotes = "Refunded to renter - Reason: $reason - $details";
        $prevStatus = !empty($booking['escrow_hold_reason']) ? 'on_hold' : 'held';
        $stmt = mysqli_prepare($conn, $logQuery);
        mysqli_stmt_bind_param($stmt, "isis", 
            $bookingId, 
            $prevStatus, 
            $adminId, 
            $logNotes
        );
        mysqli_stmt_execute($stmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // TODO: Send notification to renter about refund
    // TODO: Send notification to owner about cancellation
    
    echo json_encode([
        'success' => true,
        'message' => 'Escrow refunded successfully! Refund record created.',
        'booking_id' => $bookingId,
        'refund_id' => $newRefundId,
        'refund_reference' => $refundId,
        'refund_amount' => $refundAmount
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>