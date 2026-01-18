<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../include/db.php";

$car_id = intval($_GET['car_id'] ?? 0);

if ($car_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid car ID"
    ]);
    exit;
}

$query = $conn->prepare("
    SELECT
        r.rating,
        r.review AS comment,
        r.created_at,
        u.fullname AS name,
        IFNULL(u.profile_image, '') AS avatar
    FROM reviews r
    JOIN users u ON u.id = r.renter_id
    WHERE r.car_id = ?
    ORDER BY r.created_at DESC
");

$query->bind_param("i", $car_id);
$query->execute();
$result = $query->get_result();

$reviews = [];

while ($row = $result->fetch_assoc()) {
    $row['rating'] = floatval($row['rating']);

    // avatar fallback
    if ($row['avatar'] !== '') {
        $row['avatar'] = "http://192.168.137.1/carGOAdmin/uploads/" . $row['avatar'];
    } else {
        $row['avatar'] = "https://ui-avatars.com/api/?name=" . urlencode($row['name']);
    }

    $reviews[] = $row;
}

echo json_encode([
    "success" => true,
    "reviews" => $reviews
]);

$conn->close();
