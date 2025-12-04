<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../include/db.php';  // ✅ FIXED PATH

$owner_id = $_GET['owner_id'] ?? '';

if (empty($owner_id)) {
    echo json_encode(["status" => "error", "message" => "Owner ID required"]);
    exit;
}

// Get owner details
$ownerQuery = $conn->prepare("SELECT id, fullname, email, profile_image, created_at FROM users WHERE id = ?");
$ownerQuery->bind_param("s", $owner_id);
$ownerQuery->execute();
$ownerResult = $ownerQuery->get_result();

if ($ownerResult->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Owner not found"]);
    exit;
}

$owner = $ownerResult->fetch_assoc();

// Count ONLY approved cars for this owner
$carsQuery = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'approved'");
$carsQuery->bind_param("s", $owner_id);
$carsQuery->execute();
$carsResult = $carsQuery->get_result();
$totalCars = $carsResult->fetch_assoc()['total'];

// Get total reviews and average rating
$reviewsQuery = $conn->prepare("
    SELECT COUNT(*) as total_reviews, COALESCE(AVG(r.rating), 0) as avg_rating 
    FROM reviews r
    INNER JOIN cars c ON r.car_id = c.id
    WHERE c.owner_id = ?
");
$reviewsQuery->bind_param("s", $owner_id);
$reviewsQuery->execute();
$reviewsResult = $reviewsQuery->get_result();
$reviewStats = $reviewsResult->fetch_assoc();

echo json_encode([
    "status" => "success",
    "owner" => $owner,
    "total_cars" => (int)$totalCars,
    "total_reviews" => (int)$reviewStats['total_reviews'],
    "average_rating" => round((float)$reviewStats['avg_rating'], 1)
]);

$conn->close();
?>