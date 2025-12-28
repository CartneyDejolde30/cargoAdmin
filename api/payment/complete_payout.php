<?php
/**
 * COMPLETE PAYOUT TO OWNER
 * Called after manual GCash transfer or bank transfer completed
 */

session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../include/db.php";
require_once __DIR__ . "/transaction_logger.php";

$response = ["success" => false, "message" => ""];

// Auth check
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// Input validation
if (empty($_POST['payout_id']) || empty($_POST['reference'])) {
    $response["message"] = "Missing payout ID or transfer reference";
    echo json_encode($response);
    exit;
}

$payoutId = intval($_POST['payout_id']);
$transferReference = trim($_POST['reference']);
$transferProof = $_FILES['proof'] ?? null;

mysqli_begin_transaction($conn);

try {
    $logger = new TransactionLogger($conn);
    
    // Lock payout
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.owner_id,
            p.net_amount,
            p.status,
            b.escrow_status
        FROM payouts p
        INNER JOIN bookings b ON p.booking_id = b.id
        WHERE p.id = ?
        FOR UPDATE
    ");
    
    $stmt->bind_param("i", $payoutId);
    $stmt->execute();
    $payout = $stmt->get_result()->fetch_assoc();
    
    if (!$payout) {
        throw new Exception("Payout not found");
    }
    
    if ($payout['status'] === 'completed') {
        throw new Exception("Payout already completed");
    }
    
    if ($payout['escrow_status'] !== 'released_to_owner') {
        throw new Exception("Escrow not released yet");
    }
    
    // Handle proof upload
    $proofPath = null;
    if ($transferProof && $transferProof['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/payout_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = 'payout_' . $payoutId . '_' . time() . '.' . pathinfo($transferProof['name'], PATHINFO_EXTENSION);
        $proofPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($transferProof['tmp_name'], $proofPath)) {
            throw new Exception("Failed to upload proof");
        }
        
        $proofPath = 'uploads/payout_proofs/' . $filename;
    }
    
    // Complete payout
    $stmt = $conn->prepare("
        UPDATE payouts SET
            status = 'completed',
            completion_reference = ?,
            transfer_proof = ?,
            processed_at = NOW(),
            processed_by = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $transferReference, $proofPath, $adminId, $payoutId);
    $stmt->execute();
    
    // Update booking
    $stmt = $conn->prepare("
        UPDATE bookings SET
            payout_status = 'completed',
            payout_completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payout['booking_id']);
    $stmt->execute();
    
    // Log transaction
    $logger->log(
        $payout['booking_id'],
        'payout',
        $payout['net_amount'],
        "Payout completed. Transfer ref: $transferReference",
        $adminId,
        [
            'payout_id' => $payoutId,
            'transfer_reference' => $transferReference,
            'proof_path' => $proofPath
        ]
    );
    
    // Notify owner
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message)
        VALUES (?, 'Payout Completed 💸', CONCAT('Your payout of ₱', FORMAT(?, 2), ' has been transferred. Ref: ', ?))
    ");
    $stmt->bind_param("ids", $payout['owner_id'], $payout['net_amount'], $transferReference);
    $stmt->execute();
    
    mysqli_commit($conn);
    
    $response["success"] = true;
    $response["message"] = "Payout completed successfully";
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();