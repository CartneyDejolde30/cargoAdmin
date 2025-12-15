<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "include/db.php";

$response = ["success" => false, "message" => ""];

// Check if admin is logged in (adjust based on your session structure)
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// Required fields
if (!isset($_POST["payment_id"]) || !isset($_POST["action"])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$paymentId = intval($_POST["payment_id"]);
$action = $_POST["action"]; // 'verify' or 'reject'
$notes = isset($_POST["notes"]) ? mysqli_real_escape_string($conn, $_POST["notes"]) : null;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get payment details
    $paymentQuery = "SELECT p.*, b.owner_id, b.total_amount, b.platform_fee, b.owner_payout 
                     FROM payments p 
                     INNER JOIN bookings b ON p.booking_id = b.id 
                     WHERE p.id = ? AND p.payment_status = 'pending'";
    
    $stmt = $conn->prepare($paymentQuery);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Payment not found or already processed");
    }

    $payment = $result->fetch_assoc();
    $bookingId = $payment['booking_id'];

    if ($action === 'verify') {
        // Update payment status to verified
        $updatePaymentSql = "UPDATE payments SET 
                            payment_status = 'verified',
                            verification_notes = ?,
                            verified_by = ?,
                            verified_at = NOW()
                            WHERE id = ?";
        
        $updateStmt = $conn->prepare($updatePaymentSql);
        $updateStmt->bind_param("sii", $notes, $adminId, $paymentId);
        $updateStmt->execute();

        // Update booking payment status
        $updateBookingSql = "UPDATE bookings SET 
                            payment_status = 'paid',
                            payment_verified_at = NOW(),
                            payment_verified_by = ?,
                            status = 'approved'
                            WHERE id = ?";
        
        $updateBookingStmt = $conn->prepare($updateBookingSql);
        $updateBookingStmt->bind_param("ii", $adminId, $bookingId);
        $updateBookingStmt->execute();

        // Create escrow record
        $escrowSql = "INSERT INTO escrow (
                        booking_id, payment_id, amount, status, held_at
                    ) VALUES (?, ?, ?, 'held', NOW())";
        
        $escrowStmt = $conn->prepare($escrowSql);
        $totalAmount = $payment['total_amount'];
        $escrowStmt->bind_param("iid", $bookingId, $paymentId, $totalAmount);
        $escrowStmt->execute();
        $escrowId = $escrowStmt->insert_id;

        // Update booking escrow status
        $updateEscrowSql = "UPDATE bookings SET 
                            escrow_status = 'held',
                            escrow_held_at = NOW()
                            WHERE id = ?";
        
        $escrowUpdateStmt = $conn->prepare($updateEscrowSql);
        $escrowUpdateStmt->bind_param("i", $bookingId);
        $escrowUpdateStmt->execute();

        // Get escrow release days from settings
        $settingsQuery = mysqli_query($conn, "SELECT setting_value FROM platform_settings WHERE setting_key = 'escrow_release_days'");
        $releaseDays = 3; // Default
        if ($settingsQuery && mysqli_num_rows($settingsQuery) > 0) {
            $settingRow = mysqli_fetch_assoc($settingsQuery);
            $releaseDays = intval($settingRow['setting_value']);
        }

        // Schedule payout (to be released after rental completion + escrow days)
        $scheduledDate = date('Y-m-d H:i:s', strtotime($payment['return_date'] ?? 'now') + ($releaseDays * 24 * 60 * 60));
        
        $payoutSql = "INSERT INTO payouts (
                        booking_id, owner_id, escrow_id, amount, platform_fee, 
                        net_amount, status, scheduled_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)";
        
        $payoutStmt = $conn->prepare($payoutSql);
        $ownerId = $payment['owner_id'];
        $totalAmount = $payment['total_amount'];
        $platformFee = $payment['platform_fee'];
        $netAmount = $payment['owner_payout'];
        
        $payoutStmt->bind_param("iiiddds", $bookingId, $ownerId, $escrowId, 
                                $totalAmount, $platformFee, $netAmount, $scheduledDate);
        $payoutStmt->execute();

        // Log transaction
        $logSql = "INSERT INTO payment_transactions (
                    booking_id, transaction_type, amount, description, reference_id, created_by
                ) VALUES (?, 'escrow_hold', ?, 'Payment verified and held in escrow', ?, ?)";
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("idii", $bookingId, $totalAmount, $escrowId, $adminId);
        $logStmt->execute();

        $response["success"] = true;
        $response["message"] = "Payment verified successfully. Funds held in escrow.";
        $response["escrow_id"] = $escrowId;

    } elseif ($action === 'reject') {
        // Update payment status to failed
        $updatePaymentSql = "UPDATE payments SET 
                            payment_status = 'failed',
                            verification_notes = ?,
                            verified_by = ?,
                            verified_at = NOW()
                            WHERE id = ?";
        
        $updateStmt = $conn->prepare($updatePaymentSql);
        $updateStmt->bind_param("sii", $notes, $adminId, $paymentId);
        $updateStmt->execute();

        // Update booking status
        $updateBookingSql = "UPDATE bookings SET 
                            payment_status = 'pending',
                            status = 'rejected'
                            WHERE id = ?";
        
        $updateBookingStmt = $conn->prepare($updateBookingSql);
        $updateBookingStmt->bind_param("i", $bookingId);
        $updateBookingStmt->execute();

        // Log transaction
        $logSql = "INSERT INTO payment_transactions (
                    booking_id, transaction_type, amount, description, reference_id, created_by
                ) VALUES (?, 'payment', ?, 'Payment verification failed', ?, ?)";
        
        $logStmt = $conn->prepare($logSql);
        $amount = $payment['amount'];
        $logStmt->bind_param("idii", $bookingId, $amount, $paymentId, $adminId);
        $logStmt->execute();

        $response["success"] = true;
        $response["message"] = "Payment rejected. Booking status updated.";

    } else {
        throw new Exception("Invalid action");
    }

    // Commit transaction
    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = "Error: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>