<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "../include/db.php";

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Car ID missing"]);
    exit;
}

$carId = intval($_GET['id']);

$query = $conn->query("
    SELECT cars.*, 
           users.fullname AS owner_name, 
           users.profile_image AS owner_image
    FROM cars
    JOIN users ON users.id = cars.owner_id
    WHERE cars.id = $carId
");

if ($query->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Car not found"]);
    exit;
}

$car = $query->fetch_assoc();

$baseUrl = "http://10.72.15.180/carGOAdmin/";

// Fix duplicate upload dirs
$cleanedImagePath = str_replace(["uploads/uploads/", "uploads//"], "uploads/", $car["image"]);

// Car Image
$car["image"] = (!empty($cleanedImagePath))
    ? $baseUrl . $cleanedImagePath
    : "https://via.placeholder.com/400";

// Owner Image
$car["owner_image"] = (!empty($car["owner_image"]))
    ? $baseUrl . "uploads/" . $car["owner_image"]
    : "https://ui-avatars.com/api/?name=" . urlencode($car["owner_name"]);

// 🔥 Fix Location Output
$car["location"] = (empty($car["location"]) || $car["location"] == "0")
    ? "Location not set"
    : $car["location"];

// Fetch Reviews
$reviewQuery = $conn->query("
    SELECT reviews.*, users.fullname AS reviewer_name 
    FROM reviews 
    JOIN users ON users.id = reviews.user_id
    WHERE reviews.car_id = $carId
    ORDER BY reviews.created_at DESC
");

$reviews = [];
$totalRating = 0;
$count = 0;

while ($row = $reviewQuery->fetch_assoc()) {
    $row["user_avatar"] = "https://ui-avatars.com/api/?name=" . urlencode($row["reviewer_name"]);
    $reviews[] = $row;
    $totalRating += floatval($row["rating"]);
    $count++;
}

// Summary
$car["average_rating"] = $count > 0 ? round($totalRating / $count, 1) : 5.0;
$car["review_count"] = $count;

echo json_encode([
    "status" => "success",
    "car" => $car,
    "reviews" => $reviews
]);

exit;
?>
