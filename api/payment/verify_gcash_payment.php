<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once "include/db.php";

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['booking_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$bookingId = intval($input['booking_id']);
$action = $input['action']; // 'approve' or 'reject'
$adminId = 1; // TODO: Get from session

try {
    mysqli_begin_transaction($conn);
    
    // Get booking details
    $query = "SELECT b.*, u.fullname as owner_name, u.gcash_number as owner_gcash 
              FROM bookings b 
              LEFT JOIN users u ON b.owner_id = u.id 
              WHERE b.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if (!$booking) {
        throw new Exception("Booking not found");
    }
    
    if ($action === 'approve') {
        // Update booking status
        $updateSql = "UPDATE bookings SET 
            payment_status = 'paid',
            escrow_status = 'held',
            status = 'approved',
            verified_at = NOW(),
            verified_by = ?,
            approved_at = NOW(),
            approved_by = ?
            WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($stmt, "iii", $adminId, $adminId, $bookingId);
        mysqli_stmt_execute($stmt);
        
        // Log escrow transaction
        $escrowSql = "INSERT INTO escrow_transactions 
            (booking_id, transaction_type, amount, description, processed_by) 
            VALUES (?, 'payment_verified', ?, 'Payment verified by admin - funds held in escrow', ?)";
        $escrowStmt = mysqli_prepare($conn, $escrowSql);
        mysqli_stmt_bind_param($escrowStmt, "idi", $bookingId, $booking['total_amount'], $adminId);
        mysqli_stmt_execute($escrowStmt);
        
        // Notify renter
        $notifRenter = "INSERT INTO notifications (user_id, title, message, read_status) 
                        VALUES (?, 'Payment Verified ✅', ?, 'unread')";
        $stmtRenter = mysqli_prepare($conn, $notifRenter);
        $msgRenter = "Your payment for booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT) . " has been verified and the booking is approved!";
        mysqli_stmt_bind_param($stmtRenter, "is", $booking['user_id'], $msgRenter);
        mysqli_stmt_execute($stmtRenter);
        
        // Notify owner
        $notifOwner = "INSERT INTO notifications (user_id, title, message, read_status) 
                       VALUES (?, 'Booking Confirmed ✅', ?, 'unread')";
        $stmtOwner = mysqli_prepare($conn, $notifOwner);
        $msgOwner = "Booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT) . " has been confirmed. Payment is being held in escrow.";
        mysqli_stmt_bind_param($stmtOwner, "is", $booking['owner_id'], $msgOwner);
        mysqli_stmt_execute($stmtOwner);
        
        mysqli_commit($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Payment verified and booking approved. Funds held in escrow.'
        ]);
        
    } else if ($action === 'reject') {
        // Reject payment
        $updateSql = "UPDATE bookings SET 
            payment_status = 'unpaid',
            status = 'rejected',
            rejection_reason = 'Payment verification failed',
            rejected_at = NOW()
            WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($stmt, "i", $bookingId);
        mysqli_stmt_execute($stmt);
        
        // Notify renter
        $notifRenter = "INSERT INTO notifications (user_id, title, message, read_status) 
                        VALUES (?, 'Payment Rejected ❌', ?, 'unread')";
        $stmtRenter = mysqli_prepare($conn, $notifRenter);
        $msgRenter = "Your payment for booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT) . " could not be verified. Please contact support.";
        mysqli_stmt_bind_param($stmtRenter, "is", $booking['user_id'], $msgRenter);
        mysqli_stmt_execute($stmtRenter);
        
        mysqli_commit($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Payment rejected and booking cancelled.'
        ]);
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>