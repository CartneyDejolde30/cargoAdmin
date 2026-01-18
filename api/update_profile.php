<?php
header("Content-Type: application/json");
include "../include/db.php";

// ----------------------------
// INPUT
// ----------------------------
$user_id  = $_POST["user_id"] ?? "";
$fullname = $_POST["fullname"] ?? "";
$phone    = $_POST["phone"] ?? "";
$address = $_POST["address"] ?? "";

if (empty($user_id)) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

$profile_image_name = null;

// ----------------------------
// FILE UPLOAD
// ----------------------------
if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {

    // Absolute, reliable path
    $uploadDir = __DIR__ . "/../uploads/";

    // Log real path for debugging
    error_log("UPLOAD PATH: " . realpath($uploadDir));

    // Create folder if missing
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Permission check
    if (!is_writable($uploadDir)) {
        echo json_encode(["success" => false, "message" => "Uploads folder not writable"]);
        exit;
    }

    // Validate file type
    $ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($ext, $allowed)) {
        echo json_encode(["success" => false, "message" => "Invalid image type"]);
        exit;
    }

    // Unique filename (no caching issues, no overwrite)
    $newName = "user_" . $user_id . "_" . round(microtime(true) * 1000) . "." . $ext;
    $uploadPath = $uploadDir . $newName;

    if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $uploadPath)) {
        echo json_encode(["success" => false, "message" => "File upload failed"]);
        exit;
    }

    $profile_image_name = $newName;
}

// ----------------------------
// DATABASE UPDATE
// ----------------------------
if ($profile_image_name) {
    $stmt = $conn->prepare("
        UPDATE users
        SET fullname = ?, phone = ?, address = ?, profile_image = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $fullname, $phone, $address, $profile_image_name, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE users
        SET fullname = ?, phone = ?, address = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $fullname, $phone, $address, $user_id);
}

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Database update failed"]);
    exit;
}

// ----------------------------
// RETURN UPDATED USER
// ----------------------------
$getUser = $conn->prepare("
    SELECT fullname, phone, address, profile_image
    FROM users WHERE id = ?
");
$getUser->bind_param("i", $user_id);
$getUser->execute();
$user = $getUser->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "user" => $user
]);

$conn->close();
?>
