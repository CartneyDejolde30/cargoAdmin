<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../include/db.php";

$response = ["success" => false, "message" => ""];

// =====================================
// AUTH CHECK
// =====================================
if (!isset($_SESSION['admin_id'])) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$adminId = intval($_SESSION['admin_id']);

// =====================================
// INPUT VALIDATION
// =====================================
if (empty($_POST['payout_id']) || empty($_POST['reference'])) {
    $response["message"] = "Missing payout ID or reference";
    echo json_encode($response);
    exit;
}

$payoutId  = intval($_POST['payout_id']);
$reference = trim($_POST['reference']);

mysqli_begin_transaction($conn);

try {

    // =====================================
    // LOCK PAYOUT + BOOKING
    // =====================================
    $sql = "
        SELECT
            p.id AS payout_id,
            p.booking_id,
            p.amount,
            p.status AS payout_status,
            b.payout_status,
            b.escrow_status
        FROM payouts p
        INNER JOIN bookings b ON p.booking_id = b.id
        WHERE p.id = ?
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $payoutId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        throw new Exception("Payout not found");
    }

    $row = $res->fetch_assoc();

    // =====================================
    // STATE VALIDATION
    // =====================================
    if ($row['payout_status'] !== 'processing') {
        throw new Exception("Payout already completed or invalid");
    }

    if ($row['escrow_status'] !== 'released') {
        throw new Exception("Escrow not released");
    }

    if ($row['payout_status'] !== 'processing') {
        throw new Exception("Booking payout state invalid");
    }

    // =====================================
    // COMPLETE PAYOUT
    // =====================================
    $sql = "
        UPDATE payouts SET
            status = 'completed',
            completion_reference = ?,
            processed_at = NOW(),
            processed_by = ?
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $reference, $adminId, $payoutId);
    $stmt->execute();

    // =====================================
    // UPDATE BOOKING
    // =====================================
    $sql = "
        UPDATE bookings SET
            payout_status = 'completed',
            payout_completed_at = NOW()
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $row['booking_id']);
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
