<?php
include "include/db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ------------------ FILE HELPERS ------------------ */

function uploadSingle($field, $prefix) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $name = $prefix . time() . "_" . rand(1000, 9999) . "." . $ext;

    $folder = __DIR__ . "/uploads/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    return move_uploaded_file($_FILES[$field]['tmp_name'], $folder . $name) ? "uploads/$name" : null;
}

function uploadMultiple($field, $prefix) {
    if (!isset($_FILES[$field])) return [];

    $paths = [];
    foreach ($_FILES[$field]['name'] as $i => $file) {

        if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = pathinfo($file, PATHINFO_EXTENSION) ?: 'jpg';
        $name = $prefix . time() . "_" . $i . "_" . rand(1000,9999) . "." . $ext;


        $folder = __DIR__ . "/uploads/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        if (move_uploaded_file($_FILES[$field]['tmp_name'][$i], $folder . $name)) {
            $paths[] = "uploads/$name";
        }
    }
    return $paths;
}

/* ------------------ PROCESS REQUEST ------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? "insert";
    $id     = $_POST['id'] ?? null;

    /* ---------- DELETE ---------- */
    if ($action === "delete" && $id) {
        $stmt = $conn->prepare("DELETE FROM cars WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }

    /* ---------- READ INPUT ---------- */
    $owner_id   = $_POST['owner_id'] ?? null;
    $status     = $_POST['status'] ?? "Available";
    $year       = $_POST['year'] ?? "";
    $brand      = $_POST['brand'] ?? "";
    $model      = $_POST['model'] ?? "";
    $body_style = $_POST['body_style'] ?? "";
    $trim       = $_POST['trim'] ?? "";
    $plate      = $_POST['plate_number'] ?? "";
    $color      = $_POST['color'] ?? "";
    $desc       = $_POST['description'] ?? "";

    $notice     = $_POST['advance_notice'] ?? "";
    $minTrip    = $_POST['min_trip_duration'] ?? "";
    $maxTrip    = $_POST['max_trip_duration'] ?? "";

    $delivery   = $_POST['delivery_types'] ?? "[]";
    $features   = $_POST['features'] ?? "[]";
    $rules      = $_POST['rules'] ?? "[]";

    $unlim      = $_POST['has_unlimited_mileage'] ?? 1;
    $limit      = $_POST['mileage_limit'] ?? 0;

    $rate       = $_POST['price_per_day'] ?? 0;
    $location    = $_POST['location'] ?? "";
    $lat        = $_POST['latitude'] ?? 0;
    $lng        = $_POST['longitude'] ?? 0;

    /* ---------- UPLOAD FILES ---------- */
    $main = uploadSingle("image", "car_");
    $or = uploadSingle("official_receipt", "or_");
    $cr = uploadSingle("certificate_of_registration", "cr_");
    $extra = uploadMultiple("extra_photos", "extra_");

    /* ---------- If updating, keep old data ---------- */
    if ($action === "update" && $id) {

        $q = $conn->prepare("SELECT image, official_receipt, certificate_of_registration, extra_images FROM cars WHERE id=?");
        $q->bind_param("i", $id);
        $q->execute();
        $old = $q->get_result()->fetch_assoc();

        $main = $main ?: $old['image'];
        $or   = $or ?: $old['official_receipt'];
        $cr   = $cr ?: $old['certificate_of_registration'];

        $existing = json_decode($old['extra_images'], true) ?? [];
        $extra = array_merge($existing, $extra);
    }

    $extra_json = json_encode($extra);

    /* ---------- INSERT ---------- */
if ($action === "insert") {

    $stmt = $conn->prepare("
        INSERT INTO cars (
            owner_id, status, car_year, brand, model, body_style, trim,
            plate_number, color, description, advance_notice, min_trip_duration,
            max_trip_duration, delivery_types, features, rules,
            has_unlimited_mileage, mileage_limit, price_per_day, location,
            latitude, longitude, image, official_receipt, certificate_of_registration,
            extra_images
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
    "issssssssssiisssiidsddssss",
    $owner_id,
    $status,
    $year,
    $brand,
    $model,
    $body_style,
    $trim,
    $plate,
    $color,
    $desc,
    $notice,
    $minTrip,
    $maxTrip,
    $delivery,
    $features,
    $rules,
    $unlim,
    $limit,
    $rate,
    $location,
    $lat,
    $lng,
    $main,
    $or,
    $cr,
    $extra_json
);


    $stmt->execute();
    echo json_encode(["success" => true, "id" => $stmt->insert_id]);
    exit;
}


    /* ---------- UPDATE ---------- */
    if ($action === "update" && $id) {

        $stmt = $conn->prepare("
            UPDATE cars SET
                owner_id=?, status=?, car_year=?, brand=?, model=?, body_style=?, trim=?,
                plate_number=?, color=?, description=?, advance_notice=?, min_trip_duration=?, max_trip_duration=?,
                delivery_types=?, features=?, rules=?, unlimited_mileage=?, mileage_limit=?, price_per_day=?,
                location=?, latitude=?, longitude=?, image=?, official_receipt=?, certificate_of_registration=?,
                extra_images=? WHERE id=?
        ");

        $stmt->bind_param(
    "issssssssssiisssiidsddssssi",
    $owner_id,
    $status,
    $year,
    $brand,
    $model,
    $body_style,
    $trim,
    $plate,
    $color,
    $desc,
    $notice,
    $minTrip,
    $maxTrip,
    $delivery,
    $features,
    $rules,
    $unlim,
    $limit,
    $rate,
    $location,
    $lat,
    $lng,
    $main,
    $or,
    $cr,
    $extra_json,
    $id
);


        $stmt->execute();
        echo json_encode(["success" => true, "updated_id" => $id]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit;
}

//* ---------- FETCH CARS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['owner_id'])) {

    $stmt = $conn->prepare("
        SELECT * FROM cars WHERE owner_id=? 
        ORDER BY id DESC, FIELD(status, 'approved', 'pending', 'rejected')
    ");
    $stmt->bind_param("i", $_GET['owner_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $cars = [];
    while ($row = $result->fetch_assoc()) {

       
        $row["status"] = strtolower(trim($row["status"]));

        
        $row["extra_images"] = $row["extra_images"] ? json_decode($row["extra_images"], true) : [];

        
        $row["photo_urls"] = $row["extra_images"];

        $cars[] = $row;
    }

    echo json_encode($cars);
    exit;
}



?>
