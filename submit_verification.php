<?php
header("Content-Type: application/json");
require_once "../db.php";

try {
    if (!isset($_POST["user_id"])) {
        echo json_encode(["success" => false, "message" => "Missing user_id"]);
        exit;
    }

    $userId = $_POST["user_id"];

    // Check if user already submitted
    $check = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
    $check->execute([$userId]);
    if ($check->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "You have already submitted verification. Please wait for review."
        ]);
        exit;
    }

    // Insert WITHOUT images first
    $stmt = $conn->prepare("
        INSERT INTO user_verifications (
            user_id, first_name, last_name, email, mobile_number, gender,
            region, province, municipality, barangay, date_of_birth, id_type,
            id_front_photo, id_back_photo, selfie_photo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '')
    ");

    $stmt->execute([
        $_POST["user_id"],
        $_POST["first_name"],
        $_POST["last_name"],
        $_POST["email"],
        $_POST["mobile_number"], // FIXED
        $_POST["gender"],
        $_POST["region"],
        $_POST["province"],
        $_POST["municipality"],  // FIXED
        $_POST["barangay"],
        $_POST["date_of_birth"],
        $_POST["id_type"]
    ]);

    $verificationId = $conn->lastInsertId();

    // Prepare folder
    $uploadDir = "../uploads/verifications/$verificationId/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    function saveImage($key, $path) {
        if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]["tmp_name"])) {
            move_uploaded_file($_FILES[$key]["tmp_name"], $path);
        }
    }

    // Save images (MUST MATCH DART FIELD NAMES)
    $frontPath = $uploadDir . "id_front.jpg";
    $backPath = $uploadDir . "id_back.jpg";
    $selfiePath = $uploadDir . "selfie.jpg";

    saveImage("id_front_photo", $frontPath); // FIXED
    saveImage("id_back_photo", $backPath);   // FIXED
    saveImage("selfie_photo", $selfiePath);  // FIXED

    // Update record with image paths
    $update = $conn->prepare("
        UPDATE user_verifications 
        SET id_front_photo=?, id_back_photo=?, selfie_photo=?
        WHERE id=?
    ");

    $update->execute([
        $frontPath,
        $backPath,
        $selfiePath,
        $verificationId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Verification submitted successfully",
        "verification_id" => $verificationId
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
