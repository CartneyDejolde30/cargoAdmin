<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . "/include/db.php";

// Allow OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Ensure request is multipart/form-data (required for image upload)
if (!isset($_POST['user_id'])) {
    echo json_encode(["success" => false, "message" => "No form data received"]);
    exit;
}

// Extract normal form fields
$user_id        = $_POST['user_id'];
$first_name     = $_POST['first_name'] ?? null;
$last_name      = $_POST['last_name'] ?? null;
$email          = $_POST['email'] ?? null;
$mobile         = $_POST['mobile'] ?? null;
$gender         = $_POST['gender'] ?? null;
$dob            = $_POST['dob'] ?? null;
$region         = $_POST['region'] ?? null;
$province       = $_POST['province'] ?? null;
$municipality   = $_POST['municipality'] ?? null;
$barangay       = $_POST['barangay'] ?? null;
$id_type        = $_POST['id_type'] ?? null;

// Prevent duplicate submission
$check = $conn->prepare("SELECT id FROM user_verification WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Verification already submitted."]);
    exit;
}

// Folder for images
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper function to upload file
function uploadFile($fileKey, $uploadDir) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $fileName = time() . "_" . basename($_FILES[$fileKey]["name"]);
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $filePath)) {
        return $filePath;
    }

    return null;
}

// Upload 3 images
$id_front_photo = uploadFile("id_front_photo", $uploadDir);
$id_back_photo  = uploadFile("id_back_photo", $uploadDir);
$selfie_photo   = uploadFile("selfie_photo", $uploadDir);

// Ensure all images uploaded
if (!$id_front_photo || !$id_back_photo || !$selfie_photo) {
    echo json_encode(["success" => false, "message" => "Image upload failed."]);
    exit;
}

// Insert into database
$stmt = $conn->prepare("
    INSERT INTO user_verification 
    (user_id, first_name, last_name, email, mobile, gender, dob, region, province, municipality, barangay, id_type, id_front_photo, id_back_photo, selfie_photo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssssssssss",
    $user_id, $first_name, $last_name, $email, $mobile, $gender, $dob, $region, $province,
    $municipality, $barangay, $id_type, $id_front_photo, $id_back_photo, $selfie_photo
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Verification submitted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
