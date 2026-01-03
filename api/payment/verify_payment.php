<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../include/db.php";  // ✅ FIXED PATH
require_once __DIR__ . "/transaction_logger.php";

$response = ["success" => false, "message" => ""];

// AUTH CHECK
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// INPUT VALIDATION
if (empty($_POST['payment_id']) || empty($_POST['action'])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$paymentId = intval($_POST['payment_id']);
$action = $_POST['action']; // verify | reject

mysqli_begin_transaction($conn);

try {
    $logger = new TransactionLogger($conn);
    
    // GET PAYMENT + BOOKING
    $sql = "
        SELECT 
            p.id AS payment_id,
            p.payment_status,
            p.amount,
            p.payment_method,
            p.payment_reference,
            b.id AS booking_id,
            b.total_amount,
            b.user_id,
            b.owner_id
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        WHERE p.id = ? FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        throw new Exception("Payment not found");
    }

    $row = $res->fetch_assoc();

    if ($row['payment_status'] !== 'pending') {
        throw new Exception("Payment already processed");
    }

    $bookingId = $row['booking_id'];
    $amount = $row['amount'];

    // VERIFY PAYMENT
    if ($action === 'verify') {
        
        // Calculate fees
        $platformFee = $amount * 0.10; // 10% commission
        $ownerPayout = $amount - $platformFee;

        // Update payment
        $sql = "
            UPDATE payments SET
                payment_status = 'verified',
                verified_by = ?,
                verified_at = NOW()
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $adminId, $paymentId);
        $stmt->execute();
        
        // Log payment verification
        $logger->log(
            $bookingId,
            'payment',
            $amount,
            "Payment verified via " . $row['payment_method'],
            $adminId,
            [
                'payment_id' => $paymentId,
                'payment_reference' => $row['payment_reference'],
                'payment_method' => $row['payment_method']
            ]
        );

        // Insert into escrow
        $stmt = $conn->prepare("
            INSERT INTO escrow (booking_id, payment_id, amount, status, held_at, processed_by)
            VALUES (?, ?, ?, 'held', NOW(), ?)
        ");
        $stmt->bind_param("iidi", $bookingId, $paymentId, $amount, $adminId);
        $stmt->execute();
        
        $escrowId = $stmt->insert_id;
        
        // Log escrow hold
        $logger->log(
            $bookingId,
            'escrow_hold',
            $amount,
            "Funds held in escrow (ID: $escrowId)",
            $adminId,
            [
                'escrow_id' => $escrowId,
                'platform_fee' => $platformFee,
                'owner_payout' => $ownerPayout
            ]
        );

        // Update booking
        $sql = "
            UPDATE bookings SET
                payment_status = 'paid',
                status = 'approved',
                escrow_status = 'held',
                platform_fee = ?,
                owner_payout = ?,
                escrow_held_at = NOW(),
                payment_verified_at = NOW(),
                payment_verified_by = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddii", $platformFee, $ownerPayout, $adminId, $bookingId);
        $stmt->execute();
        
        // Notify renter
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (?, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!')
        ");
        $stmt->bind_param("i", $row['user_id']);
        $stmt->execute();
        
        // Notify owner
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (?, 'New Booking 🚗', CONCAT('Booking #', ?, ' has been confirmed. Payment received.'))
        ");
        $stmt->bind_param("ii", $row['owner_id'], $bookingId);
        $stmt->execute();

        $response["success"] = true;
        $response["message"] = "Payment verified successfully";
        $response["data"] = [
            'platform_fee' => $platformFee,
            'owner_payout' => $ownerPayout
        ];
    }

    // REJECT PAYMENT
    elseif ($action === 'reject') {
        
        $rejectionReason = $_POST['reason'] ?? 'Payment verification failed';

        // Update payment
        $sql = "
            UPDATE payments SET
                payment_status = 'rejected',
                verified_by = ?,
                verified_at = NOW(),
                verification_notes = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $adminId, $rejectionReason, $paymentId);
        $stmt->execute();
        
        // Log rejection
        $logger->log(
            $bookingId,
            'payment',
            $amount,
            "Payment rejected: $rejectionReason",
            $adminId,
            [
                'payment_id' => $paymentId,
                'reason' => $rejectionReason
            ]
        );

        // Update booking
        $sql = "
            UPDATE bookings SET
                payment_status = 'rejected',
                status = 'rejected',
                rejected_at = NOW(),
                rejection_reason = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $rejectionReason, $bookingId);
        $stmt->execute();
        
        // Notify renter
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (?, 'Payment Rejected ✗', CONCAT('Your payment was rejected. Reason: ', ?))
        ");
        $stmt->bind_param("is", $row['user_id'], $rejectionReason);
        $stmt->execute();

        $response["success"] = true;
        $response["message"] = "Payment rejected";
    } else {
        throw new Exception("Invalid action");
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();