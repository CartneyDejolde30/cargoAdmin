<?php
include "../include/db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid motorcycle ID"
    ]);
    exit;
}

$query = "
    SELECT
        m.*,
        u.fullname AS owner_name,
        u.phone AS phone,
        u.profile_image AS owner_image,
        COALESCE(AVG(r.rating), 5.0) AS rating
    FROM motorcycles m
    LEFT JOIN users u ON u.id = m.owner_id
    LEFT JOIN reviews r ON r.car_id = m.id
    WHERE m.id = ?
    GROUP BY m.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Motorcycle not found"
    ]);
    exit;
}

$motorcycle = $result->fetch_assoc();

/* Decode JSON fields safely */
$motorcycle['features'] = json_decode($motorcycle['features'] ?? '[]', true);
$motorcycle['rules'] = json_decode($motorcycle['rules'] ?? '[]', true);
$motorcycle['delivery_types'] = json_decode($motorcycle['delivery_types'] ?? '[]', true);
$motorcycle['extra_images'] = json_decode($motorcycle['extra_images'] ?? '[]', true);

/* Safety fallbacks */
$motorcycle['location'] = $motorcycle['location'] ?: 'Unknown';
$motorcycle['owner_name'] = $motorcycle['owner_name'] ?: 'Unknown';
$motorcycle['phone'] = $motorcycle['phone'] ?: '';

/* Fetch reviews */
$reviewsQuery = "
    SELECT
        r.rating,
        r.review AS comment,
        r.created_at,
        u.fullname
    FROM reviews r
    LEFT JOIN users u ON u.id = r.renter_id
    WHERE r.car_id = ?
    ORDER BY r.created_at DESC
";

$reviewsStmt = $conn->prepare($reviewsQuery);
$reviewsStmt->bind_param("i", $id);
$reviewsStmt->execute();
$reviewsResult = $reviewsStmt->get_result();

$reviews = [];
while ($row = $reviewsResult->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode([
    "status" => "success",
    "motorcycle" => $motorcycle,
    "reviews" => $reviews
]);

$conn->close();
