<?php
require "db.php";

$userId = $_POST["user_id"];

$result = $conn->query("SELECT status FROM user_verifications WHERE user_id=$userId ORDER BY id DESC LIMIT 1");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["status" => $row["status"]]);
} else {
    echo json_encode(["status" => "not_submitted"]);
}
