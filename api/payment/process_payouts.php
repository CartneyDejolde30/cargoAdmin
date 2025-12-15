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
if (!isset($_POST["payout_id"]) || !isset($_POST["action"])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$payoutId = intval($_POST["payout_id"]);
$action = $_POST["action"]; // 'complete' or 'fail'
$reference = isset($_POST["reference"]) ? mysqli_real_escape_string($conn, $_POST["reference"]) : null;
$failureReason = isset($_POST["failure_reason"]) ? mysqli_real_escape_string($conn, $_POST["failure_reason"]) : null;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get payout details
    $payoutQuery = "SELECT p.*, b.id as booking_id 
                    FROM payouts p
                    INNER JOIN bookings b ON p.booking_id = b.id
                    WHERE p.id = ? AND p.status = 'processing'";
    
    $stmt = $conn->prepare($payoutQuery);
    $stmt->bind_param("i", $payoutId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Payout not found or not in processing status");
    }

    $payout = $result->fetch_assoc();
    $bookingId = $payout['booking_id'];
    $amount = $payout['net_amount'];

    if ($action === 'complete') {
        if (empty($reference)) {
            throw new Exception("Completion reference is required");
        }

        // Complete payout
        $updatePayoutSql = "UPDATE payouts SET 
                            status = 'completed',
                            processed_at = NOW(),
                            completion_reference = ?,
                            processed_by = ?
                            WHERE id = ?";
        
        $updateStmt = $conn->prepare($updatePayoutSql);
        $updateStmt->bind_param("sii", $reference, $adminId, $payoutId);
        $updateStmt->execute();

        // Update booking payout status
        $updateBookingSql = "UPDATE bookings SET 
                            payout_status = 'completed',
                            payout_completed_at = NOW()
                            WHERE id = ?";
        
        $bookingStmt = $conn->prepare($updateBookingSql);
        $bookingStmt->bind_param("i", $bookingId);
        $bookingStmt->execute();

        // Log transaction
        $logSql = "INSERT INTO payment_transactions (
                    booking_id, transaction_type, amount, description, reference_id, created_by
                ) VALUES (?, 'payout', ?, 'Manual payout completed', ?, ?)";
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("idii", $bookingId, $amount, $payoutId, $adminId);
        $logStmt->execute();

        $response["success"] = true;
        $response["message"] = "Payout completed successfully";

    } elseif ($action === 'fail') {
        if (empty($failureReason)) {
            throw new Exception("Failure reason is required");
        }

        // Mark payout as failed
        $updatePayoutSql = "UPDATE payouts SET 
                            status = 'failed',
                            processed_at = NOW(),
                            failure_reason = ?,
                            processed_by = ?
                            WHERE id = ?";
        
        $updateStmt = $conn->prepare($updatePayoutSql);
        $updateStmt->bind_param("sii", $failureReason, $adminId, $payoutId);
        $updateStmt->execute();

        // Update booking payout status
        $updateBookingSql = "UPDATE bookings SET 
                            payout_status = 'failed'
                            WHERE id = ?";
        
        $bookingStmt = $conn->prepare($updateBookingSql);
        $bookingStmt->bind_param("i", $bookingId);
        $bookingStmt->execute();

        // Log transaction
        $logSql = "INSERT INTO payment_transactions (
                    booking_id, transaction_type, amount, description, reference_id, created_by
                ) VALUES (?, 'payout', ?, ?, ?, ?)";
        
        $description = "Payout failed: " . $failureReason;
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("idsii", $bookingId, $amount, $description, $payoutId, $adminId);
        $logStmt->execute();

        $response["success"] = true;
        $response["message"] = "Payout marked as failed";

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