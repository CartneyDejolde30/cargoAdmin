<?php

require_once "db.php"; // contains $conn

header("Content-Type: application/json");

if (!isset($_POST['userId'])) {
    echo json_encode(["error" => "Missing user ID"]);
    exit;
}

$uploadDir = "../uploads/verification/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

function saveFile($field, $uploadDir) {
    if (!isset($_FILES[$field])) return null;
    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . "." . $ext;
    move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename);
    return "uploads/verification/" . $filename;
}

// Save images
$idFront = saveFile("id_front_photo", $uploadDir);
$idBack  = saveFile("id_back_photo", $uploadDir);
$selfie  = saveFile("selfie_photo", $uploadDir);

// Insert
$stmt = $conn->prepare("
    INSERT INTO user_verifications (
      user_id, first_name, middle_name, last_name, suffix, nationality, 
      gender, date_of_birth, permRegion, permProvince, permCity, permBarangay,
      permZipCode, permAddressLine, sameAsPermanent, presRegion, presProvince,
      presCity, presBarangay, presZipCode, presAddressLine, email,
      mobileNumber, id_type, id_front_photo, id_back_photo, selfie_photo
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
  "issssssssssssssssssssssssss",
  $_POST['userId'], $_POST['firstName'], $_POST['middleName'], $_POST['lastName'], $_POST['suffix'], 
  $_POST['nationality'], $_POST['gender'], $_POST['dateOfBirth'], 
  $_POST['permRegion'], $_POST['permProvince'], $_POST['permCity'], $_POST['permBarangay'],
  $_POST['permZipCode'], $_POST['permAddressLine'], $_POST['sameAsPermanent'],
  $_POST['presRegion'], $_POST['presProvince'], $_POST['presCity'], $_POST['presBarangay'],
  $_POST['presZipCode'], $_POST['presAddressLine'], $_POST['email'], $_POST['mobileNumber'],
  $_POST['idType'], $idFront, $idBack, $selfie
);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
