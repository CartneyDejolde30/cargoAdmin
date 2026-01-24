<?php
// ========================================
// 2. GET CURRENT LOCATION (Owner app fetches latest GPS)
// File: get_current_location.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

    if ($booking_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid booking ID'
        ]);
        exit;
    }

    try {
        // Get the most recent location for this booking
        $stmt = $pdo->prepare("
            SELECT 
                latitude,
                longitude,
                speed,
                accuracy,
                timestamp
            FROM gps_locations
            WHERE booking_id = ?
            ORDER BY timestamp DESC
            LIMIT 1
        ");
        
        $stmt->execute([$booking_id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($location) {
            echo json_encode([
                'success' => true,
                'location' => $location
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No location data found'
            ]);
        }
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