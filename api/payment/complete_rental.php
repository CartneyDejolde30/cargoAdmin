<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once "include/db.php";

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id']);
    exit;
}

$bookingId = intval($input['booking_id']);
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
    
    // Verify booking can be completed
    if ($booking['status'] !== 'approved' && $booking['status'] !== 'ongoing') {
        throw new Exception("Booking cannot be completed. Current status: " . $booking['status']);
    }
    
    if ($booking['escrow_status'] !== 'held') {
        throw new Exception("Funds not held in escrow");
    }
    
    if (empty($booking['owner_gcash'])) {
        throw new Exception("Owner GCash number not set. Cannot process payout.");
    }
    
    // Generate payout reference
    $payoutRef = 'PAYOUT-' . $bookingId . '-' . time();
    
    // Update booking status
    $updateSql = "UPDATE bookings SET 
        status = 'completed',
        escrow_status = 'released_to_owner',
        payout_reference = ?,
        payout_date = NOW()
        WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "si", $payoutRef, $bookingId);
    mysqli_stmt_execute($stmt);
    
    // Log escrow transaction
    $escrowSql = "INSERT INTO escrow_transactions 
        (booking_id, transaction_type, amount, description, gcash_reference, processed_by) 
        VALUES (?, 'payout_to_owner', ?, 'Payout released to owner after rental completion', ?, ?)";
    $escrowStmt = mysqli_prepare($conn, $escrowSql);
    mysqli_stmt_bind_param($escrowStmt, "idsi", 
        $bookingId, 
        $booking['owner_payout'], 
        $payoutRef, 
        $adminId
    );
    mysqli_stmt_execute($escrowStmt);
    
    // Create payout request
    $payoutSql = "INSERT INTO payout_requests 
        (booking_id, owner_id, amount, gcash_number, status, payout_reference, processed_by, processed_at) 
        VALUES (?, ?, ?, ?, 'completed', ?, ?, NOW())";
    $payoutStmt = mysqli_prepare($conn, $payoutSql);
    mysqli_stmt_bind_param($payoutStmt, "iidssi", 
        $bookingId,
        $booking['owner_id'],
        $booking['owner_payout'],
        $booking['owner_gcash'],
        $payoutRef,
        $adminId
    );
    mysqli_stmt_execute($payoutStmt);
    
    // Notify owner
    $notifOwner = "INSERT INTO notifications (user_id, title, message, read_status) 
                   VALUES (?, 'Payout Released 💰', ?, 'unread')";
    $stmtOwner = mysqli_prepare($conn, $notifOwner);
    $msgOwner = "Your payout of ₱" . number_format($booking['owner_payout'], 2) . 
                " for booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT) . 
                " has been released to your GCash (" . $booking['owner_gcash'] . ").";
    mysqli_stmt_bind_param($stmtOwner, "is", $booking['owner_id'], $msgOwner);
    mysqli_stmt_execute($stmtOwner);
    
    // Notify renter
    $notifRenter = "INSERT INTO notifications (user_id, title, message, read_status) 
                    VALUES (?, 'Rental Completed ✅', ?, 'unread')";
    $stmtRenter = mysqli_prepare($conn, $notifRenter);
    $msgRenter = "Your rental for booking #BK-" . str_pad($bookingId, 4, "0", STR_PAD_LEFT) . " has been completed. Thank you!";
    mysqli_stmt_bind_param($stmtRenter, "is", $booking['user_id'], $msgRenter);
    mysqli_stmt_execute($stmtRenter);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Rental completed and payout released to owner',
        'data' => [
            'payout_reference' => $payoutRef,
            'payout_amount' => $booking['owner_payout'],
            'owner_gcash' => $booking['owner_gcash']
        ]
    ]);
    
    // TODO: In production, integrate with GCash Disburse API here
    // This would send the actual payout to the owner's GCash account
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>