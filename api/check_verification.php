<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$host = "127.0.0.1";
$db_name = "dbcargo";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "is_verified" => false,
        "can_add_car" => false,
        "message" => "Database connection failed"
    ]);
    exit();
}

// Support both GET and POST
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) :
            (isset($_POST['user_id']) ? trim($_POST['user_id']) : '');

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode([
        "is_verified" => false,
        "can_add_car" => false,
        "message" => "User ID is required"
    ]);
    exit();
}

try {
    // Check for approved verification
    $query = "SELECT status, verified_at
               FROM user_verifications
               WHERE user_id = :user_id
               AND status = 'approved'
              LIMIT 1";
        
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
        
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    if ($result) {
        // User is VERIFIED - can add cars
        http_response_code(200);
        echo json_encode([
            "is_verified" => true,
            "can_add_car" => true,
            "message" => "Account verified",
            "verified_at" => $result['verified_at']
        ]);
    } else {
        // Check if user has ANY verification record
        $anyQuery = "SELECT status FROM user_verifications
                      WHERE user_id = :user_id
                      ORDER BY created_at DESC
                      LIMIT 1";
        $anyStmt = $conn->prepare($anyQuery);
        $anyStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $anyStmt->execute();
                
        $anyResult = $anyStmt->fetch(PDO::FETCH_ASSOC);
                
        if ($anyResult) {
            $status = $anyResult['status'];
                        
            if ($status == 'pending') {
                http_response_code(200);
                echo json_encode([
                    "is_verified" => false,
                    "can_add_car" => false,
                    "message" => "Your verification is pending approval"
                ]);
            } else if ($status == 'rejected') {
                http_response_code(200);
                echo json_encode([
                    "is_verified" => false,
                    "can_add_car" => false,
                    "message" => "Your verification was rejected. Please submit again."
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    "is_verified" => false,
                    "can_add_car" => false,
                    "message" => "Unknown verification status"
                ]);
            }
        } else {
            // No verification record at all
            http_response_code(200);
            echo json_encode([
                "is_verified" => false,
                "can_add_car" => false,
                "message" => "Please complete your identity verification to add cars"
            ]);
        }
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "is_verified" => false,
        "can_add_car" => false,
        "message" => "Database error occurred"
    ]);
}

$conn = null;
?>