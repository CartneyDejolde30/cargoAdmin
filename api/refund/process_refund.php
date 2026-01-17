<?php
/**
 * =====================================================
 * PROCESS REFUND HANDLER
 * Handles refund processing for cancelled bookings
 * =====================================================
 */

session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../include/db.php";
require_once __DIR__ . "/../payment/transaction_logger.php";

// Check authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Admin login required."
    ]);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

$refundId = isset($input['refund_id']) ? intval($input['refund_id']) : null;
$action = isset($input['action']) ? trim($input['action']) : null; // 'approve' or 'reject'
$transferReference = isset($input['transfer_reference']) ? trim($input['transfer_reference']) : null;
$rejectionReason = isset($input['rejection_reason']) ? trim($input['rejection_reason']) : null;

if (!$refundId || !$action) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: refund_id and action"
    ]);
    exit;
}

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid action. Must be 'approve' or 'reject'"
    ]);
    exit;
}

if ($action === 'approve' && !$transferReference) {
    echo json_encode([
        "success" => false,
        "message" => "Transfer reference is required for approval"
    ]);
    exit;
}

if ($action === 'reject' && !$rejectionReason) {
    echo json_encode([
        "success" => false,
        "message" => "Rejection reason is required"
    ]);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    $logger = new TransactionLogger($conn);
    
    // Step 1: Get refund request details
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            b.total_amount,
            b.escrow_status,
            b.payment_status,
            b.status AS booking_status,
            u.fullname AS renter_name,
            u.email AS renter_email,
            u.gcash_number AS renter_gcash,
            p.payment_method,
            p.payment_reference
        FROM refunds r
        INNER JOIN bookings b ON r.booking_id = b.id
        INNER JOIN users u ON r.user_id = u.id
        LEFT JOIN payments p ON r.payment_id = p.id
        WHERE r.id = ?
        FOR UPDATE
    ");
    
    $stmt->bind_param("i", $refundId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Refund request not found");
    }
    
    $refund = $result->fetch_assoc();
    
    // Step 2: Validate refund state
    if ($refund['status'] !== 'pending') {
        throw new Exception("Refund request already processed. Current status: " . $refund['status']);
    }
    
    if (!in_array($refund['booking_status'], ['cancelled', 'rejected'])) {
        throw new Exception("Refunds can only be processed for cancelled or rejected bookings");
    }
    
    // === APPROVE REFUND ===
    if ($action === 'approve') {
        
        // Step 3: Update refund record
        $stmt = $conn->prepare("
            UPDATE refunds SET
                status = 'completed',
                processed_by = ?,
                processed_at = NOW(),
                completion_reference = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $adminId, $transferReference, $refundId);
        $stmt->execute();
        
        // Step 4: Update booking payment status
        $stmt = $conn->prepare("
            UPDATE bookings SET
                payment_status = 'refunded',
                refund_status = 'completed',
                refund_completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $refund['booking_id']);
        $stmt->execute();
        
        // Step 5: Update payment record
        $stmt = $conn->prepare("
            UPDATE payments SET
                payment_status = 'refunded'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $refund['payment_id']);
        $stmt->execute();
        
        // Step 6: Update or release escrow if held
        if ($refund['escrow_status'] === 'held') {
            $stmt = $conn->prepare("
                UPDATE escrow SET
                    status = 'refunded',
                    refunded_at = NOW(),
                    refund_reason = ?,
                    processed_by = ?
                WHERE booking_id = ? AND status = 'held'
            ");
            
            $reason = "Refund processed: " . $refund['refund_reason'];
            $stmt->bind_param("sii", $reason, $adminId, $refund['booking_id']);
            $stmt->execute();
        }
        
        // Step 7: Log transaction
        $logger->log(
            $refund['booking_id'],
            'refund',
            $refund['refund_amount'],
            "Refund completed to {$refund['renter_name']}. Reason: {$refund['refund_reason']}. GCash ref: {$transferReference}",
            $adminId,
            [
                'refund_id' => $refundId,
                'transfer_reference' => $transferReference,
                'refund_method' => $refund['refund_method'],
                'account_number' => $refund['account_number']
            ]
        );
        
        // Step 8: Notify renter
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, created_at)
            VALUES (?, 'Refund Processed 💵', ?, NOW())
        ");
        
        $message = sprintf(
            'Your refund of ₱%s has been processed and sent to your %s account. Reference: %s',
            number_format($refund['refund_amount'], 2),
            ucfirst($refund['refund_method']),
            $transferReference
        );
        
        $stmt->bind_param("is", $refund['user_id'], $message);
        $stmt->execute();
        
        $responseMessage = sprintf(
            "Refund approved! ₱%s transferred to %s",
            number_format($refund['refund_amount'], 2),
            $refund['renter_name']
        );
        
    } 
    // === REJECT REFUND ===
    else {
        
        // Step 3: Update refund record
        $stmt = $conn->prepare("
            UPDATE refunds SET
                status = 'rejected',
                processed_by = ?,
                processed_at = NOW(),
                rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $adminId, $rejectionReason, $refundId);
        $stmt->execute();
        
        // Step 4: Update booking refund status
        $stmt = $conn->prepare("
            UPDATE bookings SET
                refund_status = 'rejected'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $refund['booking_id']);
        $stmt->execute();
        
        // Step 5: Log transaction
        $logger->log(
            $refund['booking_id'],
            'refund',
            $refund['refund_amount'],
            "Refund request rejected. Reason: {$rejectionReason}",
            $adminId,
            [
                'refund_id' => $refundId,
                'rejection_reason' => $rejectionReason
            ]
        );
        
        // Step 6: Notify renter
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, created_at)
            VALUES (?, 'Refund Request Rejected ❌', ?, NOW())
        ");
        
        $message = sprintf(
            'Your refund request for booking #BK-%04d has been rejected. Reason: %s. Please contact support if you have questions.',
            $refund['booking_id'],
            $rejectionReason
        );
        
        $stmt->bind_param("is", $refund['user_id'], $message);
        $stmt->execute();
        
        $responseMessage = "Refund request rejected";
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Success response
    echo json_encode([
        "success" => true,
        "message" => $responseMessage,
        "data" => [
            "refund_id" => $refundId,
            "action" => $action,
            "amount" => $refund['refund_amount'],
            "booking_id" => $refund['booking_id']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    // Log error
    error_log("Refund Processing Error (Refund #{$refundId}): " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();