<?php
// ========================================
// 3. GET LOCATION HISTORY (For tracking path)
// File: get_location_history.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    if ($booking_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid booking ID'
        ]);
        exit;
    }

    try {
        // Get location history for the past 24 hours
        $stmt = $pdo->prepare("
            SELECT 
                latitude,
                longitude,
                speed,
                timestamp
            FROM gps_locations
            WHERE booking_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY timestamp ASC
            LIMIT ?
        ");
        
        $stmt->bindValue(1, $booking_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'history' => $history,
            'count' => count($history)
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>

<?php