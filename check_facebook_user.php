<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'])) {
    echo json_encode([
        "exists" => false,
        "message" => "Email required"
    ]);
    exit;
}

$email = $conn->real_escape_string($data['email']);

$sql = "SELECT id, fullname, email, role, phone, address, municipality, profile_image, facebook_id 
        FROM users 
        WHERE email = '$email'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        "exists" => true,
        "user" => $user
    ]);
} else {
    echo json_encode([
        "exists" => false
    ]);
}

$conn->close();
?>