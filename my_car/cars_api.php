<?php
include("include/db.php");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['owner_id'])) {
            $owner_id = intval($_GET['owner_id']);
            $result = $conn->query("SELECT * FROM cars WHERE owner_id = $owner_id ORDER BY id DESC");
            $cars = [];
            while ($row = $result->fetch_assoc()) {
                $cars[] = $row;
            }
            echo json_encode($cars);
        }
        break;

    case 'POST':
        // Handle form-data POST (with image)
        $owner_id = $_POST['owner_id'];
        $car_name = $_POST['car_name'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $plate_number = $_POST['plate_number'];
        $price_per_day = $_POST['price_per_day'];
        $status = $_POST['status'];

        $imagePath = "";
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES["image"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = $targetFilePath;
            }
        }

        $sql = "INSERT INTO cars (owner_id, car_name, brand, model, plate_number, price_per_day, status, image)
                VALUES ('$owner_id', '$car_name', '$brand', '$model', '$plate_number', '$price_per_day', '$status', '$imagePath')";
        echo json_encode(['success' => $conn->query($sql)]);
        break;

    case 'POST':
        // (Handled above)
        break;

    case 'PUT':
        // Flutter uses multipart for PUT edits (use POST override if needed)
        parse_str(file_get_contents("php://input"), $_PUT);
        echo json_encode(['error' => 'Use POST for updates with image']);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'];
        $conn->query("DELETE FROM cars WHERE id = $id");
        echo json_encode(['success' => true]);
        break;
}
?>
