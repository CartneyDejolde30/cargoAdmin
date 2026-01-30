<?php
// Quick verification of image paths
header('Content-Type: text/html; charset=utf-8');

$test_owner_id = 1;
$url = "http://localhost/cargoAdmin/api/dashboard/recent_bookings.php?owner_id={$test_owner_id}";
$response = @file_get_contents($url);
$data = json_decode($response, true);

echo "<h3>Image Path Verification</h3>";
echo "<p>Testing how Flutter will construct the URLs:</p>";

if ($data && isset($data['bookings']) && count($data['bookings']) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Booking ID</th><th>Car Name</th><th>API Returns</th><th>Flutter Constructs</th><th>Image Preview</th></tr>";
    
    foreach (array_slice($data['bookings'], 0, 3) as $booking) {
        $apiPath = $booking['car_image'];
        $flutterUrl = "http://10.218.197.49/cargoAdmin/uploads/" . $apiPath;
        
        echo "<tr>";
        echo "<td>{$booking['booking_id']}</td>";
        echo "<td>{$booking['car_full_name']}</td>";
        echo "<td><code>{$apiPath}</code></td>";
        echo "<td><code>{$flutterUrl}</code></td>";
        echo "<td><img src='../../{$apiPath}' style='max-width:100px; max-height:60px;' onerror=\"this.src='../../uploads/default_car.png'\"></td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No bookings found.</p>";
}
?>
