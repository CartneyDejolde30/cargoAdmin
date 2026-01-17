<?php
<<<<<<< HEAD
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
=======
/**
 * Transaction Logger Class
 * Logs all payment-related transactions for audit trail
 */

class TransactionLogger {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    }
    
    /**
     * Log a payment transaction
     * 
<<<<<<< HEAD
     * @param int $bookingId
     * @param string $type - 'payment', 'escrow_hold', 'escrow_release', 'payout', 'refund'
     * @param float $amount
     * @param string $description
     * @param int|null $createdBy - Admin ID if applicable
     * @param array $metadata - Additional data to store as JSON
     * @return bool
     */
    public function log($bookingId, $type, $amount, $description, $createdBy = null, $metadata = []) {
=======
     * @param int $bookingId The booking ID
     * @param string $transactionType Type: payment, escrow_hold, escrow_release, payout, refund
     * @param float $amount Transaction amount
     * @param string $description Human-readable description
     * @param int|null $createdBy Admin ID who performed the action
     * @param array $metadata Additional data to store as JSON
     * @return bool Success status
     */
    public function log(
        int $bookingId,
        string $transactionType,
        float $amount,
        string $description,
        ?int $createdBy = null,
        array $metadata = []
    ): bool {
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
        try {
            $metadataJson = !empty($metadata) ? json_encode($metadata) : null;
            
            $stmt = $this->conn->prepare("
<<<<<<< HEAD
                INSERT INTO payment_transactions
                (booking_id, transaction_type, amount, description, created_by, metadata, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "isdsss",
                $bookingId,
                $type,
=======
                INSERT INTO payment_transactions (
                    booking_id,
                    transaction_type,
                    amount,
                    description,
                    created_by,
                    metadata,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "isdsis",
                $bookingId,
                $transactionType,
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
                $amount,
                $description,
                $createdBy,
                $metadataJson
            );
            
<<<<<<< HEAD
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[AUDIT] $type | Booking #$bookingId | ₱$amount | $description");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Transaction logging failed: " . $e->getMessage());
=======
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Transaction Logger Error: " . $e->getMessage());
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
            return false;
        }
    }
    
    /**
<<<<<<< HEAD
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
=======
     * Get all transactions for a booking
     * 
     * @param int $bookingId
     * @return array Transaction history
     */
    public function getBookingTransactions(int $bookingId): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    pt.*,
                    a.fullname as admin_name,
                    DATE_FORMAT(pt.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                FROM payment_transactions pt
                LEFT JOIN admin a ON pt.created_by = a.id
                WHERE pt.booking_id = ?
                ORDER BY pt.created_at DESC
            ");
            
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['metadata']) {
                    $row['metadata'] = json_decode($row['metadata'], true);
                }
                $transactions[] = $row;
            }
            
            return $transactions;
            
        } catch (Exception $e) {
            error_log("Get Transactions Error: " . $e->getMessage());
            return [];
        }
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
    }
    
    /**
     * Get transaction summary for a booking
<<<<<<< HEAD
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
=======
     * 
     * @param int $bookingId
     * @return array Summary data
     */
    public function getBookingSummary(int $bookingId): array {
        try {
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
                    'total' => $row['total_amount'],
                    'count' => $row['transaction_count']
                ];
            }
            
            return $summary;
            
        } catch (Exception $e) {
            error_log("Get Summary Error: " . $e->getMessage());
            return [];
        }
    }
>>>>>>> fd3412cec13f65276ca33caa906de09680d00ba5
}