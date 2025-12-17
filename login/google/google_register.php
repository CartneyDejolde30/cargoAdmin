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
    
    // Validate input
    if (!isset($data->email) || !isset($data->fullname) || !isset($data->role) || !isset($data->municipality)) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required fields"
        ]);
        exit;
    }
    
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $checkStmt->bindParam(":email", $data->email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "User already exists with this email"
        ]);
        exit;
    }
    
    // Generate a secure random password for Google users (they won't use it for login)
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    
    // Insert new user with Google sign-in fields
    $stmt = $conn->prepare("
        INSERT INTO users (
            fullname, 
            email, 
            password, 
            role, 
            municipality, 
            profile_image,
            phone,
            address,
            google_uid,
            auth_provider,
            created_at,
            last_login
        ) 
        VALUES (
            :fullname, 
            :email, 
            :password, 
            :role, 
            :municipality, 
            :profile_image,
            :phone,
            :address,
            :google_uid,
            'google',
            NOW(),
            NOW()
        )
    ");
    
    $stmt->bindParam(":fullname", $data->fullname);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $randomPassword);
    $stmt->bindParam(":role", $data->role);
    $stmt->bindParam(":municipality", $data->municipality);
    
    $profileImage = $data->profile_image ?? "";
    $stmt->bindParam(":profile_image", $profileImage);
    
    $phone = $data->phone ?? "";
    $stmt->bindParam(":phone", $phone);
    
    $address = $data->address ?? "";
    $stmt->bindParam(":address", $address);
    
    $googleUid = $data->firebase_uid ?? "";
    $stmt->bindParam(":google_uid", $googleUid);
    
    if ($stmt->execute()) {
        // Get the newly created user
        $userId = $conn->lastInsertId();
        $getUserStmt = $conn->prepare("
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
                fcm_token
            FROM users 
            WHERE id = :id
        ");
        $getUserStmt->bindParam(":id", $userId);
        $getUserStmt->execute();
        $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "message" => "User registered successfully",
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to register user"
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>