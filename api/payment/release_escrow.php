<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../../include/db.php";

/* =========================================================
   AUTHORIZATION
   ========================================================= */

if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access"
    ]);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];

/* =========================================================
   VALIDATION
   ========================================================= */

if (!isset($_POST['booking_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing booking_id"
    ]);
    exit;
}

$bookingId = (int) $_POST['booking_id'];

/* =========================================================
   TRANSACTION
   ========================================================= */

mysqli_begin_transaction($conn);

try {

    /* 🔹 Get booking & escrow info */
    $sql = "
        SELECT 
            id,
            escrow_status,
            payout_status,
            owner_payout
        FROM bookings
        WHERE id = ? 
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Booking not found");
    }

    $booking = $result->fetch_assoc();

    if ($booking['escrow_status'] !== 'held') {
        throw new Exception("Escrow is not held or already released");
    }

    if ($booking['payout_status'] === 'completed') {
        throw new Exception("Payout already completed");
    }

    /* 🔹 Release escrow */
    $updateBooking = "
        UPDATE bookings SET
            escrow_status = 'released',
            escrow_released_at = NOW(),
            payout_status = 'processing'
        WHERE id = ?
    ";

    $stmt = $conn->prepare($updateBooking);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();

    /* 🔹 Log transaction */
    $logSql = "
        INSERT INTO payment_transactions
        (booking_id, transaction_type, amount, description, created_by)
        VALUES (?, 'escrow_release', ?, 'Escrow released by admin', ?)
    ";

    $stmt = $conn->prepare($logSql);
    $stmt->bind_param(
        "idi",
        $bookingId,
        $booking['owner_payout'],
        $adminId
    );
    $stmt->execute();

    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "message" => "Escrow released successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
