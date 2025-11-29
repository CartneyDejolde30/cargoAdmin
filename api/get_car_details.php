<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "../include/db.php";

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Car ID missing"]);
    exit;
}

$carId = intval($_GET['id']);
$baseUrl = "http://10.72.15.180/carGOAdmin/";

/* --------------------------------------------
   Fetch Car + Owner Information
-------------------------------------------- */
$query = $conn->query("
    SELECT cars.*, 
           users.id AS owner_id,               -- ✅ Added
           users.fullname AS owner_name, 
           users.profile_image AS owner_image,
           users.phone AS owner_phone
    FROM cars
    JOIN users ON users.id = cars.owner_id
    WHERE cars.id = $carId
");

if ($query->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Car not found"]);
    exit;
}

$car = $query->fetch_assoc();

/* --------------------------------------------
   Fix Images
-------------------------------------------- */
$cleanImage = str_replace(["uploads/uploads/", "uploads//"], "uploads/", $car["image"]);
$car["image"] = !empty($cleanImage) ? $baseUrl . $cleanImage : "https://via.placeholder.com/400";

/* Owner Image Fix */
$car["owner_image"] = !empty($car["owner_image"])
    ? $baseUrl . "uploads/" . $car["owner_image"]
    : "https://ui-avatars.com/api/?name=" . urlencode($car["owner_name"]);

/* --------------------------------------------
   Fix Location
-------------------------------------------- */
$car["location"] = (!empty($car["location"]) && $car["location"] !== "0")
    ? $car["location"]
    : "Location not set";

/* Expose Phone */
$car["phone"] = $car["owner_phone"] ?? "";

/* --------------------------------------------
   Fetch Reviews
-------------------------------------------- */
$reviewQuery = $conn->query("
    SELECT reviews.*, users.fullname AS reviewer_name 
    FROM reviews
    JOIN users ON users.id = reviews.user_id
    WHERE reviews.car_id = $carId
    ORDER BY reviews.created_at DESC
");

$reviews = [];
$totalRating = 0;

while ($row = $reviewQuery->fetch_assoc()) {
    $row["user_avatar"] = "https://ui-avatars.com/api/?name=" . urlencode($row["reviewer_name"]);
    $totalRating += floatval($row["rating"]);
    $reviews[] = $row;
}

/* Rating Summary */
$car["review_count"] = count($reviews);
$car["average_rating"] = count($reviews) > 0
    ? round($totalRating / count($reviews), 1)
    : 5.0;

/* --------------------------------------------
   Final Output
-------------------------------------------- */
echo json_encode([
    "status" => "success",
    "car" => $car,
    "reviews" => $reviews
]);

exit;
?>
