<?php
/**
 * ============================================================================
 * REQUEST REFUND API - CarGo Refund System
 * Allows renters to request a refund for cancelled bookings
 * ============================================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../include/db.php';

// ============================================================================
// VALIDATE REQUEST METHOD
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// ============================================================================
// GET POST DATA
// ============================================================================

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$refund_amount = isset($_POST['refund_amount']) ? floatval($_POST['refund_amount']) : 0;
$refund_method = isset($_POST['refund_method']) ? mysqli_real_escape_string($conn, $_POST['refund_method']) : '';
$account_number = isset($_POST['account_number']) ? mysqli_real_escape_string($conn, $_POST['account_number']) : '';
$account_name = isset($_POST['account_name']) ? mysqli_real_escape_string($conn, $_POST['account_name']) : '';
$bank_name = isset($_POST['bank_name']) ? mysqli_real_escape_string($conn, $_POST['bank_name']) : null;
$refund_reason = isset($_POST['refund_reason']) ? mysqli_real_escape_string($conn, $_POST['refund_reason']) : '';
$reason_details = isset($_POST['reason_details']) ? mysqli_real_escape_string($conn, $_POST['reason_details']) : '';
$original_payment_method = isset($_POST['original_payment_method']) ? mysqli_real_escape_string($conn, $_POST['original_payment_method']) : '';
$original_payment_reference = isset($_POST['original_payment_reference']) ? mysqli_real_escape_string($conn, $_POST['original_payment_reference']) : '';

// ============================================================================
// VALIDATION
// ============================================================================

$errors = [];

if ($booking_id <= 0) {
    $errors[] = 'Invalid booking ID';
}

if ($refund_amount <= 0) {
    $errors[] = 'Invalid refund amount';
}

if (empty($refund_method) || !in_array($refund_method, ['gcash', 'bank', 'original_method'])) {
    $errors[] = 'Invalid refund method';
}

if (empty($account_number)) {
    $errors[] = 'Account number is required';
}

if (empty($account_name)) {
    $errors[] = 'Account name is required';
}

if ($refund_method === 'gcash' && !preg_match('/^09\d{9}$/', $account_number)) {
    $errors[] = 'Invalid GCash number format';
}

if (empty($refund_reason)) {
    $errors[] = 'Refund reason is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

// ============================================================================
// CHECK BOOKING EXISTS AND GET DETAILS
// ============================================================================

$booking_query = "
    SELECT 
        b.*,
        p.id AS payment_id,
        p.amount AS payment_amount,
        p.payment_status,
        p.is_refunded
    FROM bookings b
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ]);
    exit;
}

$booking = $booking_result->fetch_assoc();

// ============================================================================
// VALIDATE REFUND ELIGIBILITY
// ============================================================================

// Check if already refunded
if ($booking['is_refunded'] == 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'This booking has already been refunded'
    ]);
    exit;
}

// Check if refund already requested
if ($booking['refund_requested'] == 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'A refund has already been requested for this booking'
    ]);
    exit;
}

// Check if booking is cancelled or rejected
$allowed_statuses = ['cancelled', 'rejected'];
if (!in_array($booking['status'], $allowed_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Only cancelled or rejected bookings are eligible for refund',
        'current_status' => $booking['status']
    ]);
    exit;
}

// Check if payment was verified
if ($booking['payment_status'] !== 'verified') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Payment must be verified before requesting refund',
        'payment_status' => $booking['payment_status']
    ]);
    exit;
}

// ============================================================================
// GENERATE REFUND ID
// ============================================================================

$refund_id = 'REF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

// Make sure it's unique
$check_query = "SELECT id FROM refunds WHERE refund_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("s", $refund_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    $refund_id = 'REF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)) . rand(10, 99);
}

// ============================================================================
// INSERT REFUND REQUEST
// ============================================================================

$insert_query = "
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

$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "siiidddssssssss",
    $refund_id,
    $booking_id,
    $booking['payment_id'],
    $booking['user_id'],
    $booking['owner_id'],
    $refund_amount,
    $booking['payment_amount'],
    $refund_method,
    $account_number,
    $account_name,
    $bank_name,
    $refund_reason,
    $reason_details,
    $original_payment_method,
    $original_payment_reference
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create refund request',
        'error' => $stmt->error
    ]);
    exit;
}

$refund_record_id = $stmt->insert_id;

// ============================================================================
// UPDATE BOOKING STATUS
// ============================================================================

$update_booking = "
    UPDATE bookings 
    SET 
        refund_requested = 1,
        refund_status = 'requested',
        refund_amount = ?
    WHERE id = ?
";

$stmt = $conn->prepare($update_booking);
$stmt->bind_param("di", $refund_amount, $booking_id);
$stmt->execute();

// ============================================================================
// SEND NOTIFICATION TO ADMIN (Optional)
// ============================================================================

// You can add notification logic here
// For example, send email to admin or create in-app notification

// ============================================================================
// SUCCESS RESPONSE
// ============================================================================

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Refund request submitted successfully',
    'refund_id' => $refund_id,
    'data' => [
        'id' => $refund_record_id,
        'refund_id' => $refund_id,
        'booking_id' => $booking_id,
        'amount' => $refund_amount,
        'status' => 'pending',
        'estimated_processing_days' => '3-5 business days',
        'refund_method' => $refund_method,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);

$conn->close();