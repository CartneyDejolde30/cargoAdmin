<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "../include/db.php";

// Get filter parameters from request
$location = isset($_GET['location']) ? $_GET['location'] : '';
$vehicleType = isset($_GET['vehicleType']) ? $_GET['vehicleType'] : '';
$minPrice = isset($_GET['minPrice']) ? floatval($_GET['minPrice']) : 0;
$maxPrice = isset($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : 999999;
$deliveryMethod = isset($_GET['deliveryMethod']) ? $_GET['deliveryMethod'] : '';
$transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
$fuelType = isset($_GET['fuelType']) ? $_GET['fuelType'] : '';
$seats = isset($_GET['seats']) ? intval($_GET['seats']) : 0;
$bodyStyle = isset($_GET['bodyStyle']) ? $_GET['bodyStyle'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';

// Build WHERE clause dynamically
$whereConditions = ["cars.status = 'approved'"];
$params = [];
$types = "";

// Price range filter (in PHP Pesos)
// Only apply if different from default range (0-5000)
if ($minPrice > 0 || $maxPrice < 999999) {
    $whereConditions[] = "cars.price_per_day BETWEEN ? AND ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
    $types .= "dd";
}

// Location filter
if (!empty($location)) {
    $whereConditions[] = "(users.address LIKE ? OR cars.address LIKE ? OR users.fullname LIKE ?)";
    $locationPattern = "%$location%";
    $params[] = $locationPattern;
    $params[] = $locationPattern;
    $params[] = $locationPattern;
    $types .= "sss";
}

// Transmission filter
if (!empty($transmission)) {
    $whereConditions[] = "cars.transmission = ?";
    $params[] = $transmission;
    $types .= "s";
}

// Fuel type filter
if (!empty($fuelType)) {
    $whereConditions[] = "cars.fuel_type = ?";
    $params[] = $fuelType;
    $types .= "s";
}

// Seats filter
if ($seats > 0) {
    $whereConditions[] = "cars.seat >= ?";
    $params[] = $seats;
    $types .= "i";
}

// Body style filter
if (!empty($bodyStyle)) {
    $whereConditions[] = "cars.body_style = ?";
    $params[] = $bodyStyle;
    $types .= "s";
}

// Brand filter
if (!empty($brand)) {
    $whereConditions[] = "cars.brand = ?";
    $params[] = $brand;
    $types .= "s";
}

// Year filter
if (!empty($year)) {
    $whereConditions[] = "cars.car_year = ?";
    $params[] = $year;
    $types .= "s";
}

// Delivery method filter (check if delivery_types JSON contains the method)
if (!empty($deliveryMethod)) {
    $whereConditions[] = "(cars.delivery_types LIKE ? OR cars.delivery_types IS NULL)";
    $deliveryPattern = "%$deliveryMethod%";
    $params[] = $deliveryPattern;
    $types .= "s";
}

// Combine WHERE conditions
$whereClause = implode(" AND ", $whereConditions);

// Build the query
$sql = "
    SELECT 
        cars.id,
        cars.owner_id,
        cars.brand,
        cars.model,
        cars.car_year,
        cars.price_per_day AS price,
        cars.image,
        cars.seat,
        cars.has_unlimited_mileage,
        cars.transmission,
        cars.fuel_type,
        cars.body_style,
        cars.color,
        cars.address,
        cars.latitude,
        cars.longitude,
        cars.delivery_types,
        users.fullname AS owner_name,
        users.address AS location,
        COALESCE(cars.rating, 5) AS rating,
        COALESCE(cars.seat, 4) AS seats
    FROM cars
    JOIN users ON users.id = cars.owner_id
    WHERE $whereClause
    ORDER BY cars.created_at DESC
";

// Prepare and execute statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$cars = [];

while ($row = $result->fetch_assoc()) {
    // Handle empty images
    if (empty($row['image'])) {
        $row['image'] = "https://via.placeholder.com/400x250?text=No+Image";
    }

    // Round rating
    $row['rating'] = round(floatval($row['rating']), 1);
    
    // Parse delivery types JSON
    if (!empty($row['delivery_types'])) {
        $row['delivery_types'] = json_decode($row['delivery_types'], true);
    } else {
        $row['delivery_types'] = [];
    }
    
    $cars[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($cars),
    "cars" => $cars,
    "filters_applied" => [
        "location" => $location,
        "minPrice" => $minPrice,
        "maxPrice" => $maxPrice,
        "transmission" => $transmission,
        "fuelType" => $fuelType,
        "seats" => $seats,
        "bodyStyle" => $bodyStyle,
        "brand" => $brand,
        "year" => $year,
        "deliveryMethod" => $deliveryMethod
    ]
]);

$stmt->close();
$conn->close();
?>
