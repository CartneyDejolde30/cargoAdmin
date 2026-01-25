<?php
// ========================================
// FILE 3: insert_test_gps.php
// Save as: carGOAdmin/api/GPS_tracking/insert_test_gps.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = isset($input['booking_id']) ? intval($input['booking_id']) : 41;
    
    try {
        // Base coordinates (San Francisco, Agusan del Sur)
        $base_lat = 8.4319;
        $base_lng = 125.9831;
        
        // Delete old test data for this booking
        $stmt = $pdo->prepare("DELETE FROM gps_locations WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        // Insert 10 test locations simulating movement
        $inserted = 0;
        $sample_data = [];
        
        for ($i = 0; $i < 10; $i++) {
            // Create slight variations in coordinates (simulating movement)
            $lat = $base_lat + (($i - 5) * 0.001); // Move north/south
            $lng = $base_lng + (($i - 5) * 0.001); // Move east/west
            $speed = rand(0, 60) * 1.0; // Random speed 0-60 km/h
            $accuracy = rand(5, 20) * 1.0; // Random accuracy 5-20m
            
            // Create timestamps going backwards (newest first in DB will be oldest timestamp)
            $minutes_ago = $i * 3; // 3 minutes apart
            
            $stmt = $pdo->prepare("
                INSERT INTO gps_locations 
                (booking_id, latitude, longitude, speed, accuracy, timestamp) 
                VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))
            ");
            
            $stmt->execute([
                $booking_id,
                $lat,
                $lng,
                $speed,
                $accuracy,
                $minutes_ago
            ]);
            
            $inserted++;
            
            if ($i < 3) {
                $sample_data[] = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed' => $speed,
                    'minutes_ago' => $minutes_ago
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Inserted $inserted test GPS records",
            'inserted_count' => $inserted,
            'booking_id' => $booking_id,
            'sample_data' => $sample_data
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
        'message' => 'Invalid request method (use POST)'
    ]);
}
?>