<?php
include "include/db.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");




$action = $_POST['action'] ?? $_GET['action'] ?? 'fetch';


// ========== FETCH ALL VEHICLES (CARS + MOTORCYCLES) ==========
if ($action === "fetch") {

    $owner_id = $_POST['owner_id'] ?? $_GET['owner_id'] ?? 0;
    $vehicles = [];

    /* -------- FETCH CARS -------- */
    $carStmt = $conn->prepare("
        SELECT 
            id,
            owner_id,
            brand,
            model,
            color,
            body_style,
            plate_number,
            price_per_day,
            image,
            location,
            status,
            created_at,
            'car' AS vehicle_type
        FROM cars
        WHERE owner_id = ?
    ");
    $carStmt->bind_param("i", $owner_id);
    $carStmt->execute();
    $carResult = $carStmt->get_result();

    while ($row = $carResult->fetch_assoc()) {
        $vehicles[] = $row;
    }
    $carStmt->close();

    /* -------- FETCH MOTORCYCLES -------- */
    $motorStmt = $conn->prepare("
        SELECT 
            id,
            owner_id,
            brand,
            model,
            color,
            body_style,
            plate_number,
            price_per_day AS price_per_day,
            image,
            location,
            status,
            created_at,
            'motorcycle' AS vehicle_type
        FROM motorcycles
        WHERE owner_id = ?
    ");
    $motorStmt->bind_param("i", $owner_id);
    $motorStmt->execute();
    $motorResult = $motorStmt->get_result();

    while ($row = $motorResult->fetch_assoc()) {
        $vehicles[] = $row;
    }
    $motorStmt->close();

    /* -------- SORT BY DATE -------- */
    usort($vehicles, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode($vehicles);
    $conn->close();
    exit;
}



// ========== INSERT CAR ==========
if ($action === "insert") {
    $owner_id = $_POST['owner_id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $brand = $_POST['brand'] ?? '';
    $model = $_POST['model'] ?? '';
    $body_style = $_POST['body_style'] ?? '';
    $plate_number = $_POST['plate_number'] ?? '';
    $color = $_POST['color'] ?? '';
    $description = $_POST['description'] ?? '';
    $advance_notice = $_POST['advance_notice'] ?? '';
    $min_trip_duration = $_POST['min_trip_duration'] ?? '';
    $max_trip_duration = $_POST['max_trip_duration'] ?? '';
    $delivery_types = $_POST['delivery_types'] ?? '[]';
    $features = $_POST['features'] ?? '[]';
    $rules = $_POST['rules'] ?? '[]';
    $has_unlimited_mileage = $_POST['has_unlimited_mileage'] ?? '1';
    $price_per_day = $_POST['price_per_day'] ?? 0;
    $location = $_POST['location'] ?? '';
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;
    
    // Car-specific fields
    $car_year = $_POST['year'] ?? '';
    $trim = $_POST['trim'] ?? '';

    // -------- UPLOAD FILES --------
    $uploadDir = "uploads/";
    
    // Main Photo
    $mainPhoto = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $mainPhotoName = uniqid("car_main_") . "." . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $mainPhotoName)) {
            $mainPhoto = $uploadDir . $mainPhotoName;
        }
    }

    // Official Receipt
    $orPhoto = null;
    if (isset($_FILES['official_receipt']) && $_FILES['official_receipt']['error'] == 0) {
        $ext = pathinfo($_FILES['official_receipt']['name'], PATHINFO_EXTENSION);
        $orName = uniqid("or_") . "." . $ext;
        if (move_uploaded_file($_FILES['official_receipt']['tmp_name'], $uploadDir . $orName)) {
            $orPhoto = $uploadDir . $orName;
        }
    }

    // Certificate of Registration
    $crPhoto = null;
    if (isset($_FILES['certificate_of_registration']) && $_FILES['certificate_of_registration']['error'] == 0) {
        $ext = pathinfo($_FILES['certificate_of_registration']['name'], PATHINFO_EXTENSION);
        $crName = uniqid("cr_") . "." . $ext;
        if (move_uploaded_file($_FILES['certificate_of_registration']['tmp_name'], $uploadDir . $crName)) {
            $crPhoto = $uploadDir . $crName;
        }
    }

    // Extra Photos
    $extraImages = [];
    if (isset($_FILES['extra_photos'])) {
        foreach ($_FILES['extra_photos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['extra_photos']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['extra_photos']['name'][$key], PATHINFO_EXTENSION);
                $extraName = uniqid("extra_") . "." . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $extraName)) {
                    $extraImages[] = $uploadDir . $extraName;
                }
            }
        }
    }

    $extraImagesJson = json_encode($extraImages);

    // -------- INSERT INTO DATABASE --------
    $stmt = $conn->prepare("
        INSERT INTO cars (
            owner_id, status, brand, model, body_style, plate_number, color, description,
            advance_notice, min_trip_duration, max_trip_duration, delivery_types,
            features, rules, has_unlimited_mileage, price_per_day,
            location, latitude, longitude, image, official_receipt, certificate_of_registration,
            extra_images, car_year, trim, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "isssssssssssssidsddssssss",
        $owner_id, $status, $brand, $model, $body_style, $plate_number, $color, $description,
        $advance_notice, $min_trip_duration, $max_trip_duration, $delivery_types,
        $features, $rules, $has_unlimited_mileage, $price_per_day,
        $location, $latitude, $longitude, $mainPhoto, $orPhoto, $crPhoto,
        $extraImagesJson, $car_year, $trim
    );

    if ($stmt->execute()) {
        $insertedId = $stmt->insert_id;
        
        // Send notification to owner
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, read_status, created_at) 
            VALUES (?, ?, ?, 'unread', NOW())
        ");
        
        $notifTitle = "Car Submitted ✅";
        $notifMessage = "Your car '{$brand} {$model}' has been submitted for approval.";
        
        $notifStmt->bind_param("iss", $owner_id, $notifTitle, $notifMessage);
        $notifStmt->execute();
        $notifStmt->close();
        
        echo json_encode(["success" => true, "id" => $insertedId, "message" => "Car submitted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);
?>