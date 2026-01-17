<?php
/**
 * COMPLETE PAYOUT TO OWNER
 * Called after manual GCash transfer completed
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

// Input validation - Accept booking_id (primary) or payout_id (fallback)
$bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
$payoutId = !empty($_POST['payout_id']) ? intval($_POST['payout_id']) : null;
$transferReference = !empty($_POST['reference']) ? trim($_POST['reference']) : null;

if ((!$bookingId && !$payoutId) || !$transferReference) {
    $response["message"] = "Missing booking/payout ID or transfer reference";
    echo json_encode($response);
    exit;
}

$transferProof = $_FILES['proof'] ?? null;

mysqli_begin_transaction($conn);

try {
    $logger = new TransactionLogger($conn);
    
    // If only booking_id provided, find or create payout record
    if ($bookingId && !$payoutId) {
        // Check if payout already exists
        $stmt = $conn->prepare("SELECT id FROM payouts WHERE booking_id = ? LIMIT 1");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $payoutId = $result->fetch_assoc()['id'];
        } else {
            // Create payout record if it doesn't exist
            $stmt = $conn->prepare("
                SELECT 
                    b.id as booking_id,
                    b.owner_id,
                    b.owner_payout,
                    b.platform_fee,
                    b.total_amount,
                    e.id as escrow_id
                FROM bookings b
                LEFT JOIN escrow e ON b.id = e.booking_id AND e.status = 'held'
                WHERE b.id = ?
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if (!$booking['escrow_id']) {
                throw new Exception("No escrow found for this booking. Payment may not be verified yet.");
            }
            
            // Create payout record
            $stmt = $conn->prepare("
                INSERT INTO payouts (
                    booking_id,
                    owner_id,
                    escrow_id,
                    amount,
                    platform_fee,
                    net_amount,
                    status,
                    scheduled_at,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            $stmt->bind_param(
                "iiiddd",
                $booking['booking_id'],
                $booking['owner_id'],
                $booking['escrow_id'],
                $booking['total_amount'],
                $booking['platform_fee'],
                $booking['owner_payout']
            );
            $stmt->execute();
            $payoutId = $conn->insert_id;
        }
    }
    
    // Now lock and process the payout
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.owner_id,
            p.net_amount,
            p.status,
            b.escrow_status,
            u.fullname as owner_name
        FROM payouts p
        INNER JOIN bookings b ON p.booking_id = b.id
        INNER JOIN users u ON p.owner_id = u.id
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
    
    // Handle proof upload
    $proofPath = null;
    if ($transferProof && $transferProof['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/payout_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = 'payout_' . $payoutId . '_' . time() . '.' . pathinfo($transferProof['name'], PATHINFO_EXTENSION);
        $fullPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($transferProof['tmp_name'], $fullPath)) {
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
            payout_completed_at = NOW(),
            payout_reference = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $transferReference, $payout['booking_id']);
    $stmt->execute();
    
    // Update escrow status
    $stmt = $conn->prepare("
        UPDATE escrow SET
            status = 'released',
            released_at = NOW(),
            release_reason = 'Payout completed to owner',
            processed_by = ?
        WHERE booking_id = ? AND status = 'held'
    ");
    $stmt->bind_param("ii", $adminId, $payout['booking_id']);
    $stmt->execute();
    
    // Log transaction
    $logger->log(
        $payout['booking_id'],
        'payout',
        $payout['net_amount'],
        "Payout completed to {$payout['owner_name']}. Transfer ref: $transferReference",
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
    $response["message"] = "Payout completed successfully. ₱" . number_format($payout['net_amount'], 2) . " transferred to " . $payout['owner_name'];
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
    error_log("Complete Payout Error: " . $e->getMessage());
}

echo json_encode($response);
$conn->close();