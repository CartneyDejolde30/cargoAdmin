<?php
<<<<<<< HEAD
include "../include/db.php";

$user_id = $_POST['user_id'] ?? 0;
$fcm_token = $_POST['fcm_token'] ?? '';

if ($user_id == 0 || empty($fcm_token)) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET fcm_token=? WHERE id=?");
$stmt->bind_param("si", $fcm_token, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => true, "message" => "Token saved"]);
=======
include "include/db.php";

$user_id = $_POST['user_id'];
$token = $_POST['token'];

$conn->query("UPDATE users SET fcm_token='$token' WHERE id='$user_id'");

echo json_encode(["status" => "success"]);
?>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
