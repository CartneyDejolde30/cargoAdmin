<?php
// Enable error reporting for debugging

error_reporting(E_ALL);
ini_set('display_errors', 0);       // disable HTML errors
ini_set('log_errors', 1);           // enable logging to file
ini_set('error_log', __DIR__.'/php_errors.log'); // log file
error_reporting(E_ALL);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include "include/db.php"; // Make sure your DB connection works

// Read POST fields safely
$user_id  = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$phone    = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$address  = isset($_POST['address']) ? trim($_POST['address']) : '';

// Validate required fields
if ($user_id <= 0 || $fullname === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields",
        "received" => $_POST
    ]);
    exit;
}

// Fetch existing profile image
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$existingImage = $row ? $row['profile_image'] : "";
$stmt->close();

$profile_image = $existingImage;

// ✅ Ensure uploads folder exists and is writable
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create uploads directory"
        ]);
        exit;
    }
}

// Make sure folder is writable
if (!is_writable($uploadDir)) {
    chmod($uploadDir, 0777);
}

// Handle new image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = $_FILES['profile_image']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExt, $allowedExt)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid file type"
        ]);
        exit;
    }

    $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;

    if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
        $profile_image = $newFileName;

        // Delete old image if exists
        if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
            @unlink($uploadDir . $existingImage);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Image upload failed"
        ]);
        exit;
    }
}

// Update database
$stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, address=?, profile_image=? WHERE id=?");
$stmt->bind_param("ssssi", $fullname, $phone, $address, $profile_image, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully",
        "fullname" => $fullname,
        "phone" => $phone,
        "address" => $address,
        "profile_image" => $profile_image
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
