<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "include/db.php";

$response = ["success" => false, "message" => ""];

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// Required fields
if (!isset($_POST["booking_id"])) {
    $response["message"] = "Missing booking ID";
    echo json_encode($response);
    exit;
}

$bookingId = intval($_POST["booking_id"]);
$releaseReason = isset($_POST["reason"]) ? mysqli_real_escape_string($conn, $_POST["reason"]) : "Rental completed successfully";

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get escrow details
    $escrowQuery = "SELECT e.*, b.owner_payout, b.owner_id, p.id as payout_id
                    FROM escrow e
                    INNER JOIN bookings b ON e.booking_id = b.id
                    LEFT JOIN payouts p ON p.escrow_id = e.id
                    WHERE e.booking_id = ? AND e.status = 'held'";
    
    $stmt = $conn->prepare($escrowQuery);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No escrow found for this booking or already released");
    }

    $escrow = $result->fetch_assoc();
    $escrowId = $escrow['id'];
    $payoutId = $escrow['payout_id'];
    $ownerPayout = $escrow['owner_payout'];

    // Release escrow
    $releaseEscrowSql = "UPDATE escrow SET 
                        status = 'released',
                        released_at = NOW(),
                        release_reason = ?,
                        processed_by = ?
                        WHERE id = ?";
    
    $releaseStmt = $conn->prepare($releaseEscrowSql);
    $releaseStmt->bind_param("sii", $releaseReason, $adminId, $escrowId);
    $releaseStmt->execute();

    // Update booking escrow status
    $updateBookingSql = "UPDATE bookings SET 
                        escrow_status = 'released',
                        escrow_released_at = NOW()
                        WHERE id = ?";
    
    $bookingStmt = $conn->prepare($updateBookingSql);
    $bookingStmt->bind_param("i", $bookingId);
    $bookingStmt->execute();

    // Update payout to processing
    $updatePayoutSql = "UPDATE payouts SET 
                        status = 'processing',
                        processed_at = NOW(),
                        processed_by = ?
                        WHERE id = ?";
    
    $payoutStmt = $conn->prepare($updatePayoutSql);
    $payoutStmt->bind_param("ii", $adminId, $payoutId);
    $payoutStmt->execute();

    // Log transaction
    $logSql = "INSERT INTO payment_transactions (
                booking_id, transaction_type, amount, description, reference_id, created_by
            ) VALUES (?, 'escrow_release', ?, ?, ?, ?)";
    
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param("idsii", $bookingId, $ownerPayout, $releaseReason, $escrowId, $adminId);
    $logStmt->execute();

    // Check if auto payout is enabled
    $autoPayoutQuery = mysqli_query($conn, "SELECT setting_value FROM platform_settings WHERE setting_key = 'auto_payout_enabled'");
    $autoPayoutEnabled = false;
    
    if ($autoPayoutQuery && mysqli_num_rows($autoPayoutQuery) > 0) {
        $setting = mysqli_fetch_assoc($autoPayoutQuery);
        $autoPayoutEnabled = ($setting['setting_value'] === 'true' || $setting['setting_value'] === '1');
    }

    if ($autoPayoutEnabled) {
        // Automatically complete payout (in real scenario, integrate with payment gateway)
        $completePayoutSql = "UPDATE payouts SET 
                            status = 'completed',
                            processed_at = NOW(),
                            completion_reference = ?
                            WHERE id = ?";
        
        $reference = 'AUTO-' . date('YmdHis') . '-' . $payoutId;
        $completeStmt = $conn->prepare($completePayoutSql);
        $completeStmt->bind_param("si", $reference, $payoutId);
        $completeStmt->execute();

        // Update booking payout status
        $updateBookingPayoutSql = "UPDATE bookings SET 
                                    payout_status = 'completed',
                                    payout_completed_at = NOW()
                                    WHERE id = ?";
        
        $bookingPayoutStmt = $conn->prepare($updateBookingPayoutSql);
        $bookingPayoutStmt->bind_param("i", $bookingId);
        $bookingPayoutStmt->execute();

        // Log payout transaction
        $payoutLogSql = "INSERT INTO payment_transactions (
                        booking_id, transaction_type, amount, description, reference_id, created_by
                    ) VALUES (?, 'payout', ?, 'Automatic payout to owner', ?, ?)";
        
        $payoutLogStmt = $conn->prepare($payoutLogSql);
        $payoutLogStmt->bind_param("idii", $bookingId, $ownerPayout, $payoutId, $adminId);
        $payoutLogStmt->execute();

        $response["auto_payout"] = true;
        $response["payout_reference"] = $reference;
    }

    // Commit transaction
    mysqli_commit($conn);

    $response["success"] = true;
    $response["message"] = $autoPayoutEnabled ? 
                          "Escrow released and payout completed automatically" : 
                          "Escrow released successfully. Payout is being processed.";
    $response["escrow_id"] = $escrowId;
    $response["payout_id"] = $payoutId;
    $response["amount"] = $ownerPayout;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = "Error: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>