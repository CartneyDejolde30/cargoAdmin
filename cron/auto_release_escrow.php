<?php
require_once __DIR__ . 'include/db.php';
require_once __DIR__ . '/../api/escrow/release_to_owner.php';

error_log("=== Auto-Release Escrow Cron Started ===");

try {
    $conn = new mysqli("localhost", "root", "", "dbcargo");
    
    // Release after 3 days of completed rental
    $releaseDays = 3;
    
    $sql = "
        SELECT b.id, b.owner_id, b.owner_payout, b.return_date
        FROM bookings b
        WHERE b.return_date <= DATE_SUB(NOW(), INTERVAL ? DAY)
          AND b.escrow_status = 'held'
          AND b.status = 'completed'
          AND b.payment_status = 'paid'
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $releaseDays);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $released = 0;
    $failed = 0;
    
    while ($booking = $result->fetch_assoc()) {
        echo "Processing booking #{$booking['id']}... ";
        
        $result = releaseEscrowToOwner($booking['id'], $conn, null);
        
        if (isset($result['success'])) {
            $released++;
            echo "✓ Released ₱{$booking['owner_payout']}\n";
        } else {
            $failed++;
            echo "✗ Failed: {$result['error']}\n";
        }
    }
    
    $message = "Auto-release completed: $released released, $failed failed";
    error_log($message);
    echo "\n$message\n";
    
} catch (Exception $e) {
    error_log("Auto-release error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}

error_log("=== Auto-Release Escrow Cron Finished ===");