<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "127.0.0.1";
$db_name = "dbcargo";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query for user 7
    $query = "SELECT * FROM user_verifications WHERE user_id = 7";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "debug" => true,
        "user_id" => 7,
        "results" => $result,
        "count" => count($result)
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>