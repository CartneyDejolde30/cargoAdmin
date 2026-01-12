<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include "../include/db.php";

/* ---------- READ JSON ---------- */
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status"=>"error","message"=>"Invalid JSON"]);
    exit;
}

/* ---------- INPUT ---------- */
$booking_id = (int)($data['booking_id'] ?? 0);
$user_id    = (int)($data['user_id'] ?? 0);
$car_id     = (int)($data['car_id'] ?? 0);
$owner_id   = (int)($data['owner_id'] ?? 0);

$rating     = (float)($data['car_rating'] ?? 0); // use car rating as main
$review     = trim($data['car_review'] ?? '');

$categories = json_encode([
    "car"   => $data['car_categories'] ?? [],
    "owner" => $data['owner_categories'] ?? []
]);

/* ---------- VALIDATION ---------- */
if (!$booking_id || !$user_id || !$car_id || !$owner_id || !$rating || $review === '') {
    echo json_encode(["status"=>"error","message"=>"Invalid data"]);
    exit;
}

/* ---------- ELIGIBILITY ---------- */
$check = $conn->prepare("
    SELECT id FROM bookings
    WHERE id = ?
      AND user_id = ?
      AND IFNULL(is_reviewed,0) = 0
      AND return_date <= NOW()
    LIMIT 1
");
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Booking not eligible for review"
    ]);
    exit;
}
$check->close();

/* ---------- TRANSACTION ---------- */
$conn->begin_transaction();

try {

    /* INSERT REVIEW */
    $stmt = $conn->prepare("
        INSERT INTO reviews
        (booking_id, car_id, renter_id, owner_id, rating, review, categories)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiiidss",
        $booking_id,
        $car_id,
        $user_id,
        $owner_id,
        $rating,
        $review,
        $categories
    );
    $stmt->execute();
    $stmt->close();

    /* MARK BOOKING AS REVIEWED */
    $upd = $conn->prepare("UPDATE bookings SET is_reviewed = 1 WHERE id = ?");
    $upd->bind_param("i", $booking_id);
    $upd->execute();
    $upd->close();

    $conn->commit();

    echo json_encode([
        "status"=>"success",
        "message"=>"Review submitted successfully"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status"=>"error",
        "mysql_error"=>$conn->error
    ]);
}

$conn->close();
