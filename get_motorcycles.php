<?php
include "../include/db.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Get only approved motorcycles
$query = "
    SELECT 
        m.*,
        u.fullname as owner_name,
        u.phone as owner_phone,
        COALESCE(AVG(r.rating), 5.0) as rating
    FROM motorcycles m
    LEFT JOIN users u ON m.owner_id = u.id
    LEFT JOIN reviews r ON m.id = r.car_id AND r.car_id IN (SELECT id FROM motorcycles)
    WHERE m.status = 'approved'
    GROUP BY m.id
    ORDER BY m.created_at DESC
";

$result = $conn->query($query);

$motorcycles = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields
        $row['features'] = json_decode($row['features'] ?? '[]', true);
        $row['rules'] = json_decode($row['rules'] ?? '[]', true);
        $row['delivery_types'] = json_decode($row['delivery_types'] ?? '[]', true);
        $row['extra_images'] = json_decode($row['extra_images'] ?? '[]', true);
        
        // Add vehicle type identifier
        $row['vehicle_type'] = 'motorcycle';
        $row['type'] = $row['body_style'] ?? 'Standard';
        $row['year'] = $row['motorcycle_year'] ?? '';
        $row['engine_size'] = $row['engine_displacement'] ?? '';
        $row['price'] = $row['price_per_day'] ?? 0;
        
        $motorcycles[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'motorcycles' => $motorcycles,
    'count' => count($motorcycles)
]);

$conn->close();
?>