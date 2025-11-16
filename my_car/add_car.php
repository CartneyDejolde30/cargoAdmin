<?php
include "../include/db.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$name = $data["name"];
$status = $data["status"];
$owner_id = $data["owner_id"];

$stmt = $conn->prepare("INSERT INTO cars (name, status, owner_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $status, $owner_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Failed to add car"]);
}
$stmt->close();
$conn->close();
?>
