<?php
require_once __DIR__ . '/../../include/db.php';  // ✅ FIXED PATH

/**
 * Centralized Transaction Logging System
 * Logs all payment-related activities for audit trail
 */
class TransactionLogger {
    private $conn;
    
    public function __construct($conn = null) {
        if ($conn === null) {
            $this->conn = new mysqli("localhost", "root", "", "dbcargo");
        } else {
            $this->conn = $conn;
        }
    }
    
    /**
     * Log a payment transaction
     * 
     * @param int $bookingId
     * @param string $type - 'payment', 'escrow_hold', 'escrow_release', 'payout', 'refund'
     * @param float $amount
     * @param string $description
     * @param int|null $createdBy - Admin ID if applicable
     * @param array $metadata - Additional data to store as JSON
     * @return bool
     */
    public function log($bookingId, $type, $amount, $description, $createdBy = null, $metadata = []) {
        try {
            $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
            
            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions
                (booking_id, transaction_type, amount, description, created_by, metadata, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "isdsss",
                $bookingId,
                $type,
                $amount,
                $description,
                $createdBy,
                $metadataJson
            );
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[AUDIT] $type | Booking #$bookingId | ₱$amount | $description");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Transaction logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get transaction history for a booking
     */
    public function getHistory($bookingId) {
        $stmt = $this->conn->prepare("
            SELECT 
                pt.*,
                a.fullname as admin_name
            FROM payment_transactions pt
            LEFT JOIN admin a ON pt.created_by = a.id
            WHERE pt.booking_id = ?
            ORDER BY pt.created_at ASC
        ");
        
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['metadata']) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * Get transaction summary for a booking
     */
    public function getSummary($bookingId) {
        $stmt = $this->conn->prepare("
            SELECT 
                transaction_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM payment_transactions
            WHERE booking_id = ?
            GROUP BY transaction_type
        ");
        
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $summary = [];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['transaction_type']] = [
                'total' => floatval($row['total_amount']),
                'count' => intval($row['transaction_count'])
            ];
        }
        
        return $summary;
    }
}

// Helper function for quick logging
function logTransaction($bookingId, $type, $amount, $description, $createdBy = null, $metadata = []) {
    $logger = new TransactionLogger();
    return $logger->log($bookingId, $type, $amount, $description, $createdBy, $metadata);
}