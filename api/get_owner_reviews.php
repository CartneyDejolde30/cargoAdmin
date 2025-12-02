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
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            u.fullname,
            c.brand,
            c.model,
            c.car_year
        FROM reviews r
        INNER JOIN cars c ON r.car_id = c.id
        INNER JOIN users u ON r.user_id = u.id
        WHERE c.owner_id = ?
        ORDER BY r.created_at DESC
    ");
    
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "reviews" => $reviews,
        "total" => count($reviews)
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>