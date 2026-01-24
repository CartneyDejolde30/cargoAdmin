<?php
/**
 * ============================================================================
 * RELEASE ESCROW API
 * Release funds from escrow to owner (schedule payout)
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

$bookingId = intval($_POST['booking_id']);
$adminId = $_SESSION['admin_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get booking details
    $bookingQuery = "
        SELECT 
            b.*,
            p.payment_status,
            u.fullname AS owner_name,
            u.email AS owner_email
        FROM bookings b
        LEFT JOIN payments p ON b.id = p.booking_id
        LEFT JOIN users u ON b.owner_id = u.id
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
    
    // Validate escrow can be released
    if ($booking['escrow_status'] !== 'held') {
        throw new Exception('Escrow is not in held status. Current status: ' . $booking['escrow_status']);
    }
    
    // Check if payment is verified
    if ($booking['payment_status'] !== 'verified') {
        throw new Exception('Payment must be verified before releasing escrow');
    }
    
    // Ideally, booking should be completed before releasing
    // But we'll allow release for approved/active bookings with admin discretion
    if (!in_array($booking['booking_status'], ['completed', 'active', 'approved'])) {
        throw new Exception('Booking status must be completed, active, or approved. Current: ' . $booking['booking_status']);
    }
    
    // Update escrow status
    $updateEscrow = "
        UPDATE bookings 
        SET 
            escrow_status = 'released_to_owner',
            escrow_released_at = NOW(),
            payout_status = 'pending'
        WHERE id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $updateEscrow);
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update escrow status: ' . mysqli_error($conn));
    }
    
    // Create payout record
    $createPayout = "
        INSERT INTO payouts (
            booking_id,
            owner_id,
            amount,
            platform_fee,
            net_amount,
            payout_method,
            payout_account,
            status,
            scheduled_at,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'gcash', ?, 'pending', NOW(), NOW())
    ";
    
    $stmt = mysqli_prepare($conn, $createPayout);
    $gcashAccount = $booking['owner_id']; // This will be used to fetch owner's gcash from users table
    
    mysqli_stmt_bind_param($stmt, "iiddds", 
        $bookingId,
        $booking['owner_id'],
        $booking['total_amount'],
        $booking['platform_fee'],
        $booking['owner_payout'],
        $gcashAccount
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create payout record: ' . mysqli_error($conn));
    }
    
    // Log escrow release
    $logQuery = "
        INSERT INTO escrow_logs (
            booking_id,
            action,
            previous_status,
            new_status,
            admin_id,
            notes,
            created_at
        ) VALUES (?, 'release', 'held', 'released_to_owner', ?, 'Escrow released to owner', NOW())
    ";
    
    $stmt = mysqli_prepare($conn, $logQuery);
    mysqli_stmt_bind_param($stmt, "ii", $bookingId, $adminId);
    mysqli_stmt_execute($stmt); // Non-critical, so we don't throw on failure
    
    // Commit transaction
    mysqli_commit($conn);
    
    // TODO: Send notification to owner about payout
    
    echo json_encode([
        'success' => true,
        'message' => 'Escrow released successfully! Payout scheduled for owner.',
        'booking_id' => $bookingId,
        'owner_payout' => $booking['owner_payout']
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>