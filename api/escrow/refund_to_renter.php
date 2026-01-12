<?php
require_once 'include/db.php';

function refundEscrowToRenter($bookingId, $reason, $conn = null, $processedBy = null) {
    $shouldClose = false;
    if (!$conn) {
        $conn = new mysqli("localhost", "root", "", "dbcargo");
        $shouldClose = true;
    }
    
    try {
        mysqli_begin_transaction($conn);
        
        // Get escrow
        $stmt = $conn->prepare("
            SELECT e.id, e.amount, b.user_id
            FROM escrow e
            JOIN bookings b ON b.id = e.booking_id
            WHERE e.booking_id = ? AND e.status = 'held'
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $escrow = $stmt->get_result()->fetch_assoc();
        
        if (!$escrow) {
            throw new Exception("No held escrow found");
        }
        
        // Update escrow
        $stmt = $conn->prepare("
            UPDATE escrow 
            SET status = 'refunded',
                refunded_at = NOW(),
                refund_reason = ?,
                processed_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $reason, $processedBy, $escrow['id']);
        $stmt->execute();
        
        // Update booking
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET escrow_status = 'refunded',
                payment_status = 'refunded'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        // Log
        $stmt = $conn->prepare("
            INSERT INTO payment_transactions 
            (booking_id, transaction_type, amount, description, created_by)
            VALUES (?, 'refund', ?, ?, ?)
        ");
        $stmt->bind_param("idsi", $bookingId, $escrow['amount'], "Refund: $reason", $processedBy);
        $stmt->execute();
        
        // Notify
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message)
            VALUES (?, 'Refund Processed 💵', CONCAT('Your refund of ₱', FORMAT(?, 2), ' has been processed.'))
        ");
        $stmt->bind_param("id", $escrow['user_id'], $escrow['amount']);
        $stmt->execute();
        
        mysqli_commit($conn);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['error' => $e->getMessage()];
    } finally {
        if ($shouldClose) {
            $conn->close();
        }
    }
}