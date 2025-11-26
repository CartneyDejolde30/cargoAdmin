<?php
// Show errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include "include/db.php";

// Read input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["fullname"], $data["email"], $data["password"], $data["municipality"], $data["role"])) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Trim input to remove spaces
$fullname     = trim($data["fullname"]);
$email        = trim($data["email"]);
$password     = trim($data["password"]); // secure hash
$municipality = trim($data["municipality"]);
$role         = trim($data["role"]);

// Check if email exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists, try another one!"]);
    exit;
}

// Insert user
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, municipality) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullname, $email, $password, $role, $municipality);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User registered successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$stmt->close();
$conn->close();
?>
