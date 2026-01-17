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

<<<<<<< HEAD
// Input validation
if (empty($_POST['payout_id']) || empty($_POST['reference'])) {
    $response["message"] = "Missing payout ID or transfer reference";
=======
// Input validation - Accept EITHER booking_id OR payout_id
$bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
$payoutId = !empty($_POST['payout_id']) ? intval($_POST['payout_id']) : null;
$transferReference = !empty($_POST['reference']) ? trim($_POST['reference']) : null;

if ((!$bookingId && !$payoutId) || !$transferReference) {
    $response["message"] = "Missing booking/payout ID or transfer reference";
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    echo json_encode($response);
    exit;
}

<<<<<<< HEAD
$payoutId = intval($_POST['payout_id']);
$transferReference = trim($_POST['reference']);
=======
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
$transferProof = $_FILES['proof'] ?? null;

mysqli_begin_transaction($conn);

try {
    $logger = new TransactionLogger($conn);
    
<<<<<<< HEAD
    // Lock payout
=======
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
                throw new Exception("No escrow found for this booking");
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
                $booking['owner_payout'],
                $booking['platform_fee'],
                $booking['owner_payout']
            );
            $stmt->execute();
            $payoutId = $conn->insert_id;
        }
    }
    
    // Now lock and process the payout
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.owner_id,
            p.net_amount,
            p.status,
<<<<<<< HEAD
            b.escrow_status
        FROM payouts p
        INNER JOIN bookings b ON p.booking_id = b.id
=======
            b.escrow_status,
            u.fullname as owner_name
        FROM payouts p
        INNER JOIN bookings b ON p.booking_id = b.id
        INNER JOIN users u ON p.owner_id = u.id
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
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
    
<<<<<<< HEAD
    if ($payout['escrow_status'] !== 'released_to_owner') {
        throw new Exception("Escrow not released yet");
    }
    
=======
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    // Handle proof upload
    $proofPath = null;
    if ($transferProof && $transferProof['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/payout_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = 'payout_' . $payoutId . '_' . time() . '.' . pathinfo($transferProof['name'], PATHINFO_EXTENSION);
<<<<<<< HEAD
        $proofPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($transferProof['tmp_name'], $proofPath)) {
=======
        $fullPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($transferProof['tmp_name'], $fullPath)) {
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
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
<<<<<<< HEAD
            payout_completed_at = NOW()
        WHERE id = ?
    ");
=======
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
            released_at = NOW()
        WHERE booking_id = ? AND status = 'held'
    ");
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    $stmt->bind_param("i", $payout['booking_id']);
    $stmt->execute();
    
    // Log transaction
    $logger->log(
        $payout['booking_id'],
        'payout',
        $payout['net_amount'],
<<<<<<< HEAD
        "Payout completed. Transfer ref: $transferReference",
=======
        "Payout completed to {$payout['owner_name']}. Transfer ref: $transferReference",
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
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
<<<<<<< HEAD
    $response["message"] = "Payout completed successfully";
=======
    $response["message"] = "Payout completed successfully. ₱" . number_format($payout['net_amount'], 2) . " transferred to " . $payout['owner_name'];
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
<<<<<<< HEAD
=======
    error_log("Complete Payout Error: " . $e->getMessage());
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
}

echo json_encode($response);
$conn->close();