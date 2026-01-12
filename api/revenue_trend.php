<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once "include/db.php";

$owner_id = $_GET['owner_id'] ?? 0;
$period = $_GET['period'] ?? 'week'; // week, month, year

if ($owner_id <= 0) {
    echo json_encode(["success" => false, "message" => "Missing owner_id"]);
    exit;
}

// Validate period
$validPeriods = ['week' => 7, 'month' => 30, 'year' => 365];
$days = $validPeriods[$period] ?? 7;

$query = "
    SELECT 
        DATE(created_at) as date, 
        SUM(total_amount) as revenue,
        COUNT(*) as booking_count
    FROM bookings
    WHERE owner_id = ? 
      AND status IN ('approved', 'completed')
      AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $owner_id, $days);
$stmt->execute();
$result = $stmt->get_result();

$trend = [];
while ($row = $result->fetch_assoc()) {
    $trend[] = [
        "date" => $row['date'],
        "revenue" => floatval($row['revenue']),
        "bookings" => intval($row['booking_count'])
    ];
}

echo json_encode([
    "success" => true,
    "period" => $period,
    "data" => $trend,
    "total_revenue" => array_sum(array_column($trend, 'revenue'))
]);

$stmt->close();
$conn->close();
?>