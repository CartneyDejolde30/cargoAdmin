<?php
/**
 * ============================================================================
 * PROCESS REFUND API - Admin processes refund requests
 * Actions: approve, reject, complete
 * ============================================================================
 */

session_start();
header('Content-Type: application/json');

require_once '../../include/db.php';

// ============================================================================
// CHECK ADMIN AUTHENTICATION
// ============================================================================

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin login required.'
    ]);
    exit;
}

$admin_id = $_SESSION['admin_id'];

// ============================================================================
// VALIDATE REQUEST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, $_POST['admin_notes']) : '';
$rejection_reason = isset($_POST['rejection_reason']) ? mysqli_real_escape_string($conn, $_POST['rejection_reason']) : '';
$refund_reference = isset($_POST['refund_reference']) ? mysqli_real_escape_string($conn, $_POST['refund_reference']) : '';
$deduction_amount = isset($_POST['deduction_amount']) ? floatval($_POST['deduction_amount']) : 0;
$deduction_reason = isset($_POST['deduction_reason']) ? mysqli_real_escape_string($conn, $_POST['deduction_reason']) : '';

// ============================================================================
// VALIDATION
// ============================================================================

if ($refund_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid refund ID'
    ]);
    exit;
}

$allowed_actions = ['approve', 'reject', 'complete'];
if (!in_array($action, $allowed_actions)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Allowed: approve, reject, complete'
    ]);
    exit;
}

// ============================================================================
// GET REFUND DETAILS
// ============================================================================

$refund_query = "
    SELECT 
        r.*,
        b.status AS booking_status,
        b.total_amount AS booking_amount,
        p.payment_status,
        p.id AS payment_id
    FROM refunds r
    JOIN bookings b ON r.booking_id = b.id
    JOIN payments p ON r.payment_id = p.id
    WHERE r.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($refund_query);
$stmt->bind_param("i", $refund_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Refund request not found'
    ]);
    exit;
}

$refund = $result->fetch_assoc();

// ============================================================================
// PROCESS ACTION
// ============================================================================

$conn->begin_transaction();

try {
    switch ($action) {
        // ========================================
        // APPROVE REFUND
        // ========================================
        case 'approve':
            if ($refund['status'] !== 'pending') {
                throw new Exception('Only pending refunds can be approved');
            }

            // Calculate final refund amount after deductions
            $final_refund_amount = $refund['refund_amount'] - $deduction_amount;

            if ($final_refund_amount < 0) {
                throw new Exception('Deduction amount cannot exceed refund amount');
            }

            // Update refund record
            $update_refund = "
                UPDATE refunds 
                SET 
                    status = 'approved',
                    deduction_amount = ?,
                    deduction_reason = ?,
                    admin_notes = ?,
                    approved_at = NOW(),
                    processed_by = ?
                WHERE id = ?
            ";

            $stmt = $conn->prepare($update_refund);
            $stmt->bind_param(
                "dssii",
                $deduction_amount,
                $deduction_reason,
                $admin_notes,
                $admin_id,
                $refund_id
            );
            $stmt->execute();

            // Update booking
            $conn->query("UPDATE bookings SET refund_status = 'approved' WHERE id = {$refund['booking_id']}");

            $message = 'Refund approved successfully';
            break;

        // ========================================
        // REJECT REFUND
        // ========================================
        case 'reject':
            if ($refund['status'] !== 'pending') {
                throw new Exception('Only pending refunds can be rejected');
            }

            if (empty($rejection_reason)) {
                throw new Exception('Rejection reason is required');
            }

            // Update refund record
            $update_refund = "
                UPDATE refunds 
                SET 
                    status = 'rejected',
                    rejection_reason = ?,
                    admin_notes = ?,
                    processed_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";

            $stmt = $conn->prepare($update_refund);
            $stmt->bind_param("ssii", $rejection_reason, $admin_notes, $admin_id, $refund_id);
            $stmt->execute();

            // Update booking
            $conn->query("UPDATE bookings SET refund_status = 'rejected' WHERE id = {$refund['booking_id']}");

            $message = 'Refund rejected';
            break;

        // ========================================
        // COMPLETE REFUND (Mark as transferred)
        // ========================================
        case 'complete':
            if (!in_array($refund['status'], ['approved', 'processing'])) {
                throw new Exception('Only approved refunds can be completed');
            }

            if (empty($refund_reference)) {
                throw new Exception('Refund reference number is required');
            }

            // Update refund record
            $update_refund = "
                UPDATE refunds 
                SET 
                    status = 'completed',
                    refund_reference = ?,
                    admin_notes = ?,
                    completed_at = NOW(),
                    processed_by = ?
                WHERE id = ?
            ";

            $stmt = $conn->prepare($update_refund);
            $stmt->bind_param("ssii", $refund_reference, $admin_notes, $admin_id, $refund_id);
            $stmt->execute();

            // Update booking
            $conn->query("UPDATE bookings SET refund_status = 'completed' WHERE id = {$refund['booking_id']}");

            // Mark payment as refunded
            $conn->query("
                UPDATE payments 
                SET 
                    is_refunded = 1,
                    refund_id = {$refund_id},
                    refunded_at = NOW()
                WHERE id = {$refund['payment_id']}
            ");

            // If escrow is held, release it back to renter
            if ($refund['payment_status'] === 'verified') {
                $conn->query("
                    UPDATE bookings 
                    SET escrow_status = 'refunded'
                    WHERE id = {$refund['booking_id']}
                ");
            }

            $message = 'Refund completed and funds transferred';
            break;
    }

    $conn->commit();

    // ============================================================================
    // SUCCESS RESPONSE
    // ============================================================================

    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'refund_id' => $refund_id,
            'action' => $action,
            'new_status' => $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'completed'),
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $admin_id
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();