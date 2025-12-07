<?php
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
