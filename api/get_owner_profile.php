<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include '../db.php';

if (!isset($_GET['owner_id'])) {
    echo json_encode(["status" => "error", "message" => "Owner ID required"]);
    exit;
}

$owner_id = $_GET['owner_id'];

try {
    // Get owner details
    $stmt = $conn->prepare("
        SELECT 
            id,
            fullname,
            email,
            phone,
            address,
            profile_image,
            created_at
        FROM users 
        WHERE id = ? AND role = 'Owner'
    ");
    
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Owner not found"]);
        exit;
    }
    
    $owner = $result->fetch_assoc();
    
    // Get total cars
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM cars 
        WHERE owner_id = ? AND status = 'approved'
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $cars_result = $stmt->get_result();
    $total_cars = $cars_result->fetch_assoc()['total'];
    
    // Get total reviews and average rating
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT r.id) as total_reviews,
            COALESCE(AVG(r.rating), 0) as average_rating
        FROM reviews r
        INNER JOIN cars c ON r.car_id = c.id
        WHERE c.owner_id = ?
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    $reviews_data = $reviews_result->fetch_assoc();
    
    echo json_encode([
        "status" => "success",
        "owner" => $owner,
        "total_cars" => (int)$total_cars,
        "total_reviews" => (int)$reviews_data['total_reviews'],
        "average_rating" => round((float)$reviews_data['average_rating'], 1)
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>