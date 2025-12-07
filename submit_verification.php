<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . "/include/db.php";

// Allow OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Ensure request has form data
if (!isset($_POST['user_id'])) {
    echo json_encode(["success" => false, "message" => "No form data received"]);
    exit;
}

// Extract form fields
$user_id        = intval($_POST['user_id']);
$first_name     = trim($_POST['first_name'] ?? '');
$last_name      = trim($_POST['last_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$mobile         = trim($_POST['mobile'] ?? '');
$gender         = trim($_POST['gender'] ?? '');
$dob            = trim($_POST['dob'] ?? '');
$region         = trim($_POST['region'] ?? '');
$id_type        = trim($_POST['id_type'] ?? '');

// Validate required fields
if (!$user_id || !$first_name || !$last_name || !$email || !$mobile || !$id_type) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Prevent duplicate submission - check for existing verification
$checkStmt = $conn->prepare("SELECT id, status FROM user_verifications WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    if ($existing['status'] === 'pending') {
        echo json_encode([
            "success" => false, 
            "message" => "Verification already submitted and pending review. Please wait for admin approval."
        ]);
    } else if ($existing['status'] === 'approved') {
        echo json_encode([
            "success" => false, 
            "message" => "Your account is already verified."
        ]);
    } else {
        // Allow resubmission if rejected
        echo json_encode([
            "success" => false, 
            "message" => "Previous verification was rejected. Please contact support to resubmit.",
            "can_resubmit" => true
        ]);
    }
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Create upload directory if not exists
$uploadDir = __DIR__ . "/uploads/verification/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode([
            "success" => false, 
            "message" => "Server error: Unable to create upload directory. Please contact administrator."
        ]);
        exit;
    }
}

// Helper function to upload file with validation
function uploadFile($fileKey, $uploadDir, $userId, &$errorMsg) {
    if (!isset($_FILES[$fileKey])) {
        $errorMsg = "File '$fileKey' not found in upload";
        return null;
    }

    $file = $_FILES[$fileKey];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File '$fileKey' is too large (max 5MB)",
            UPLOAD_ERR_PARTIAL => "File '$fileKey' was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file uploaded for '$fileKey'",
            default => "Upload error for '$fileKey'"
        };
        return null;
    }

    // Validate file type (only images)
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errorMsg = "File '$fileKey' must be an image (JPG, PNG, GIF, or WEBP)";
        return null;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $errorMsg = "File '$fileKey' exceeds 5MB limit";
        return null;
    }

    // Generate unique filename
    $extension = match($mimeType) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg'
    };
    
    $fileName = $fileKey . "_" . $userId . "_" . time() . "_" . bin2hex(random_bytes(8)) . "." . $extension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $filePath)) {
        // Return relative path for database storage
        return "uploads/verification/" . $fileName;
    }

    $errorMsg = "Failed to save file '$fileKey'";
    return null;
}

// Upload 3 required images (passing $user_id to the function)
$uploadError = "";
$id_front_photo = uploadFile("id_front_photo", $uploadDir, $user_id, $uploadError);
if (!$id_front_photo) {
    echo json_encode(["success" => false, "message" => $uploadError]);
    exit;
}

$id_back_photo = uploadFile("id_back_photo", $uploadDir, $user_id, $uploadError);
if (!$id_back_photo) {
    @unlink($uploadDir . basename($id_front_photo)); // Clean up previous upload
    echo json_encode(["success" => false, "message" => $uploadError]);
    exit;
}

$selfie_photo = uploadFile("selfie_photo", $uploadDir, $user_id, $uploadError);
if (!$selfie_photo) {
    @unlink($uploadDir . basename($id_front_photo)); // Clean up
    @unlink($uploadDir . basename($id_back_photo));
    echo json_encode(["success" => false, "message" => $uploadError]);
    exit;
}

// Insert into database using prepared statement
$insertStmt = $conn->prepare("
    INSERT INTO user_verifications 
    (user_id, first_name, last_name, email, mobile_number, gender, date_of_birth, region, 
     id_type, id_front_photo, id_back_photo, selfie_photo, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

$insertStmt->bind_param(
    "isssssssssss",
    $user_id, 
    $first_name, 
    $last_name, 
    $email, 
    $mobile, 
    $gender, 
    $dob, 
    $region,
    $id_type, 
    $id_front_photo, 
    $id_back_photo, 
    $selfie_photo
);

if ($insertStmt->execute()) {
    // Create notification for user
    $notificationTitle = "Verification Submitted 📄";
    $notificationMessage = "Your identity verification has been submitted successfully. We'll review it within 24-48 hours.";
    
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, read_status, created_at) VALUES (?, ?, ?, 'unread', NOW())");
    $notifStmt->bind_param("iss", $user_id, $notificationTitle, $notificationMessage);
    $notifStmt->execute();
    $notifStmt->close();
    
    echo json_encode([
        "success" => true, 
        "message" => "Verification submitted successfully! We'll review your documents within 24-48 hours.",
        "verification_id" => $insertStmt->insert_id
    ]);
} else {
    // Delete uploaded files if database insert fails
    @unlink($uploadDir . basename($id_front_photo));
    @unlink($uploadDir . basename($id_back_photo));
    @unlink($uploadDir . basename($selfie_photo));
    
    error_log("Database insert failed: " . $insertStmt->error);
    
    echo json_encode([
        "success" => false, 
        "message" => "Database error: Unable to save verification. Please try again or contact support."
    ]);
}

$insertStmt->close();
$conn->close();
?>