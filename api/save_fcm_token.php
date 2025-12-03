<?php
include "include/db.php";

$user_id = $_POST['user_id'];
$token = $_POST['token'];

$conn->query("UPDATE users SET fcm_token='$token' WHERE id='$user_id'");

echo json_encode(["status" => "success"]);
?>
