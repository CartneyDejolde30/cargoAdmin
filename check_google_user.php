<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'include/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['email'])) {
        echo json_encode([
            "exists" => false, 
            "message" => "Email is required"
        ]);
        exit;
    }
    
    $email = $conn->real_escape_string($data['email']);
    
    $sql = "SELECT 
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
            WHERE email = '$email' 
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update last_login
        $updateSql = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
        $conn->query($updateSql);
        
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
    
} catch (Exception $e) {
    echo json_encode([
        "exists" => false,
        "error" => "Server error: " . $e->getMessage()
    ]);
}

$conn->close();
?>