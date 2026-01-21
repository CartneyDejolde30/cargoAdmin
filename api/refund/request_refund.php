<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../../include/db.php';

/* =========================================================
   HELPER
========================================================= */
function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/* =========================================================
   AUTH - TOKEN
========================================================= */
$token = null;

if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
} elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
} elseif (!empty($_POST['token'])) {
    $token = $_POST['token'];
}

if (!$token) {
    jsonError('Missing token', 401);
}

$stmt = $conn->prepare("SELECT id FROM users WHERE api_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    jsonError('Invalid token', 401);
}

$user = $res->fetch_assoc();
$userId = (int)$user['id'];

if ($userId <= 0) {
    jsonError('Unauthorized', 401);
}

/* =========================================================
   METHOD CHECK
========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Use POST method', 405);
}

/* =========================================================
   INPUT
========================================================= */
$bookingId               = (int)($_POST['booking_id'] ?? 0);
$refundMethod           = trim($_POST['refund_method'] ?? '');
$accountNumber         = trim($_POST['account_number'] ?? '');
$accountName           = trim($_POST['account_name'] ?? '');
$bankName              = trim($_POST['bank_name'] ?? '');
$refundReason          = trim($_POST['refund_reason'] ?? '');
$reasonDetails         = trim($_POST['reason_details'] ?? '');
$originalPayMethod    = trim($_POST['original_payment_method'] ?? '');
$originalPayReference = trim($_POST['original_payment_reference'] ?? '');

/* =========================================================
   VALIDATION
========================================================= */
if ($bookingId <= 0) jsonError('Invalid booking ID');
if (!in_array($refundMethod, ['gcash', 'bank', 'original_method'], true)) {
    jsonError('Invalid refund method');
}
if ($accountNumber === '') jsonError('Account number is required');
if ($accountName === '') jsonError('Account name is required');
if ($refundMethod === 'gcash' && !preg_match('/^09\d{9}$/', $accountNumber)) {
    jsonError('Invalid GCash number');
}
if ($refundReason === '') jsonError('Refund reason is required');

/* =========================================================
   GET BOOKING + LATEST PAYMENT
========================================================= */
$sql = "
SELECT 
    b.*,
    p.id     AS payment_id,
    p.amount AS payment_amount,
    p.payment_status
FROM bookings b
LEFT JOIN payments p 
  ON p.id = (
    SELECT id 
    FROM payments 
    WHERE booking_id = b.id 
    ORDER BY created_at DESC 
    LIMIT 1
  )
WHERE b.id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    jsonError('Booking not found', 404);
}

$booking = $res->fetch_assoc();

/* =========================================================
   SECURITY CHECK
========================================================= */
if ((int)$booking['user_id'] !== $userId) {
    jsonError('Not your booking', 403);
}

/* =========================================================
   REFUND ELIGIBILITY
========================================================= */
if ($booking['refund_status'] === 'completed') {
    jsonError('This booking has already been refunded');
}
if ((int)$booking['refund_requested'] === 1) {
    jsonError('Refund already requested');
}
if (!in_array($booking['status'], ['cancelled', 'rejected'], true)) {
    jsonError('Only cancelled or rejected bookings can be refunded');
}
if ($booking['payment_status'] !== 'verified') {
    jsonError('Payment not verified');
}

/* =========================================================
   TRANSACTION
========================================================= */
$conn->begin_transaction();

try {
    // Generate Refund ID
    $refundId = 'REF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $stmt = $conn->prepare("SELECT id FROM refunds WHERE refund_id = ?");
    $stmt->bind_param("s", $refundId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $refundId .= rand(10, 99);
    }

    // Normalize nullable values
    $ownerId        = $booking['owner_id'] ?? null;
    $refundAmount  = (float)$booking['payment_amount'];
    $originalAmt   = $booking['payment_amount'] ?? null;
    $bankName      = $bankName ?: null;
    $reasonDetails = $reasonDetails ?: null;
    $origMethod   = $originalPayMethod ?: null;
    $origRef      = $originalPayReference ?: null;

    /* =====================================================
       INSERT REFUND
    ===================================================== */
    $insert = "
    INSERT INTO refunds (
        refund_id,
        booking_id,
        payment_id,
        user_id,
        owner_id,
        refund_amount,
        original_amount,
        deduction_amount,
        refund_method,
        account_number,
        account_name,
        bank_name,
        refund_reason,
        reason_details,
        status,
        original_payment_method,
        original_payment_reference,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
    ";

    $stmt = $conn->prepare($insert);
    $stmt->bind_param(
        "siiiiddssssssss",
        $refundId,
        $bookingId,
        $booking['payment_id'],
        $booking['user_id'],
        $ownerId,
        $refundAmount,
        $originalAmt,
        $refundMethod,
        $accountNumber,
        $accountName,
        $bankName,
        $refundReason,
        $reasonDetails,
        $origMethod,
        $origRef
    );
    $stmt->execute();

    /* =====================================================
       UPDATE BOOKING
    ===================================================== */
    $update = "
    UPDATE bookings
    SET refund_requested = 1,
        refund_status = 'requested',
        refund_amount = ?
    WHERE id = ?
    ";

    $stmt = $conn->prepare($update);
    $stmt->bind_param("di", $refundAmount, $bookingId);
    $stmt->execute();

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    jsonError("SQL ERROR: " . $e->getMessage(), 500);
}

/* =========================================================
   SUCCESS
========================================================= */
echo json_encode([
    'success'   => true,
    'refund_id'=> $refundId,
    'amount'   => $refundAmount,
    'status'   => 'pending',
    'message'  => 'Refund request submitted successfully'
]);

$conn->close();
