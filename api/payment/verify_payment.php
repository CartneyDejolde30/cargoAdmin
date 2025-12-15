<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../include/db.php";

$response = ["success" => false, "message" => ""];

// ================================
// AUTH CHECK
// ================================
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// ================================
// INPUT VALIDATION
// ================================
if (empty($_POST['payment_id']) || empty($_POST['action'])) {
    $response["message"] = "Missing required fields";
    echo json_encode($response);
    exit;
}

$paymentId = intval($_POST['payment_id']);
$action = $_POST['action']; // verify | reject

mysqli_begin_transaction($conn);

try {

    // ================================
    // GET PAYMENT + BOOKING
    // ================================
    $sql = "
        SELECT 
            p.id AS payment_id,
            p.payment_status,
            b.id AS booking_id
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

    // ================================
    // VERIFY PAYMENT
    // ================================
    if ($action === 'verify') {

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

        // Update booking
        $sql = "
            UPDATE bookings SET
                payment_status = 'paid',
                status = 'approved',
                escrow_status = 'held',
                payment_verified_at = NOW(),
                payment_verified_by = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $adminId, $bookingId);
        $stmt->execute();

        $response["success"] = true;
        $response["message"] = "Payment verified successfully";

    }

    // ================================
    // REJECT PAYMENT
    // ================================
    elseif ($action === 'reject') {

    // Update payment
    $sql = "
        UPDATE payments SET
            payment_status = 'rejected',
            verified_by = ?,
            verified_at = NOW()
        WHERE id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $adminId, $paymentId);
    $stmt->execute();

    // Update booking
    $sql = "
        UPDATE bookings SET
            payment_status = 'rejected',
            status = 'rejected',
            rejected_at = NOW()
        WHERE id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    $response["success"] = true;
    $response["message"] = "Payment rejected";
}
 else {
        throw new Exception("Invalid action");
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
