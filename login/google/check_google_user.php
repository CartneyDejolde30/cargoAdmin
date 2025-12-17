<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$dbname = "dbcargo";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->email)) {
        echo json_encode([
            "exists" => false, 
            "message" => "Email is required"
        ]);
        exit;
    }
    
    $email = $data->email;
    
    // Check if user exists and get all necessary fields
    $stmt = $conn->prepare("
        SELECT 
            id, 
            fullname, 
            email, 
            role, 
            municipality, 
            profile_image, 
            phone, 
            address,
            google_uid,
            auth_provider,
            fcm_token,
            gcash_number,
            gcash_name
        FROM users 
        WHERE email = :email 
        LIMIT 1
    ");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update last_login timestamp
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $updateStmt->bindParam(":id", $user['id']);
        $updateStmt->execute();
        
        echo json_encode([
            "exists" => true,
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "exists" => false,
            "message" => "User not found"
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "exists" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "exists" => false,
        "error" => "Server error: " . $e->getMessage()
    ]);
}
?>