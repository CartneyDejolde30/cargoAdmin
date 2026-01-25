<?php
// ========================================
// FIXED VERSION: get_location_history.php
// Replace your existing file with this
// Location: carGOAdmin/api/GPS_tracking/get_location_history.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    // Validate booking ID
    if ($booking_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid booking ID',
            'history' => []
        ]);
        exit;
    }

    try {
        // FIXED: Removed 24-hour restriction to get ALL data
        // The original version only showed data from the last 24 hours
        $sql = "
            SELECT 
                latitude,
                longitude,
                speed,
                accuracy,
                timestamp
            FROM gps_locations
            WHERE booking_id = ?
            ORDER BY timestamp ASC
            LIMIT $limit
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return response
        echo json_encode([
            'success' => true,
            'history' => $history,
            'count' => count($history),
            'booking_id' => $booking_id
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'history' => []
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Use GET.',
        'history' => []
    ]);
}
?>