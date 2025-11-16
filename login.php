<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include "include/db.php";

// Read input JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["email"], $data["password"])) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Trim inputs
$email = trim($data["email"]);
$password = trim($data["password"]);

// Fetch user from database
$stmt = $conn->prepare("SELECT id, fullname, email, phone, address, role, profile_image, password FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

$row = $result->fetch_assoc();

// Compare password
// ⚠️ If you are storing hashed passwords, use password_verify($password, $row['password'])
if ($password === $row["password"]) {
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "id" => $row["id"],
        "fullname" => $row["fullname"],
        "email" => $row["email"],
        "phone" => $row["phone"] ?? "",
        "address" => $row["address"] ?? "",
        "role" => $row["role"],
        "profile_image" => $row["profile_image"] ?? ""
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid password"
    ]);
}

$stmt->close();
$conn->close();
?>
