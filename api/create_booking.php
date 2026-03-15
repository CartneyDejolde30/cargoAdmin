<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../include/db.php";
require_once __DIR__ . "/security/suspension_guard.php";
require_once __DIR__ . "/../include/send_notification.php";

$response = ["success" => false, "message" => ""];

/* =========================================================
   1️⃣ AUTHENTICATION (MOBILE + WEB)
========================================================= */
$userId = null;

if (!empty($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);

    $stmt = $conn->prepare("
        SELECT u.id,
               CASE WHEN uv.status = 'approved' THEN 1 ELSE 0 END AS is_verified
        FROM users u
        LEFT JOIN user_verifications uv ON u.id = uv.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["success"=>false,"message"=>"User not found"]);
        exit;
    }

    // Block suspended users
    require_not_suspended($conn, $userId);

    if ($user['is_verified'] != 1) {
        echo json_encode(["success"=>false,"message"=>"Account not verified"]);
        exit;
    }

} elseif (!empty($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
} else {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

/* =========================================================
   2️⃣ BASIC VALIDATION
========================================================= */
if (
    empty($_POST['vehicle_type']) ||
    empty($_POST['vehicle_id']) ||
    empty($_POST['pickup_date']) ||
    empty($_POST['return_date'])
) {
    echo json_encode(["success"=>false,"message"=>"Missing required fields"]);
    exit;
}

$vehicleType = $_POST['vehicle_type'];
$vehicleId   = intval($_POST['vehicle_id']);

if (!in_array($vehicleType, ['car','motorcycle'])) {
    echo json_encode(["success"=>false,"message"=>"Invalid vehicle type"]);
    exit;
}

$table = ($vehicleType === 'motorcycle') ? 'motorcycles' : 'cars';


/* =========================================================
   3️⃣ TIME + OTHER INPUTS
========================================================= */
$pickupDate = trim($_POST['pickup_date']);
$returnDate = trim($_POST['return_date']);
$pickupTime = $_POST['pickup_time'] ?? '09:00';
$returnTime = $_POST['return_time'] ?? '18:00';

$pickupTimeTs = strtotime($pickupTime);
$returnTimeTs = strtotime($returnTime);
$pickupTime = $pickupTimeTs !== false ? date("H:i:s", $pickupTimeTs) : '09:00:00';
$returnTime = $returnTimeTs !== false ? date("H:i:s", $returnTimeTs) : '18:00:00';

// Validate date formats and logical order (combine date+time for same-day booking support)
$pickupTs = strtotime($pickupDate);
$returnTs = strtotime($returnDate);
if ($pickupTs === false || $returnTs === false) {
    echo json_encode(["success"=>false,"message"=>"Invalid date format"]);
    exit;
}
if ($pickupTs < strtotime(date('Y-m-d'))) {
    echo json_encode(["success"=>false,"message"=>"Pickup date must be today or in the future"]);
    exit;
}
$pickupDateTimeTs = strtotime($pickupDate . ' ' . $pickupTime);
$returnDateTimeTs = strtotime($returnDate . ' ' . $returnTime);
if ($returnDateTimeTs <= $pickupDateTimeTs) {
    echo json_encode(["success"=>false,"message"=>"Return date/time must be after pickup date/time"]);
    exit;
}

$rentalPeriod = $_POST['rental_period'] ?? 'Day';
$needsDelivery = intval($_POST['needs_delivery'] ?? 0);
$fullName = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$contact = $_POST['contact'] ?? '';

mysqli_begin_transaction($conn);

try {

    /* =========================================================
       4️⃣ FETCH VEHICLE (CAR OR MOTORCYCLE)
    ========================================================= */
    $stmt = $conn->prepare("
        SELECT price_per_day, owner_id, status
        FROM {$table}
        WHERE id = ?
    ");
    $stmt->bind_param("i", $vehicleId);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();

    if (!$vehicle) {
        throw new Exception(ucfirst($vehicleType) . " not found");
    }

    if ($vehicle['status'] !== 'approved') {
        throw new Exception(ucfirst($vehicleType) . " not available");
    }

    /* =========================================================
       4b️⃣ CHECK FOR OVERLAPPING BOOKINGS (with row-level lock)
    ========================================================= */
    $newPickupDatetime = $pickupDate . ' ' . $pickupTime;
    $newReturnDatetime = $returnDate . ' ' . $returnTime;

    $overlapStmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE car_id = ?
          AND vehicle_type = ?
          AND status NOT IN ('cancelled', 'rejected', 'completed')
          AND CONCAT(pickup_date, ' ', pickup_time) < ?
          AND CONCAT(return_date, ' ', return_time) > ?
        LIMIT 1
        FOR UPDATE
    ");
    $overlapStmt->bind_param("isss", $vehicleId, $vehicleType, $newReturnDatetime, $newPickupDatetime);
    $overlapStmt->execute();
    $overlapResult = $overlapStmt->get_result();
    if ($overlapResult->num_rows > 0) {
        throw new Exception("Vehicle is already booked for the selected dates");
    }
    $overlapStmt->close();

    /* =========================================================
       5️⃣ CALCULATE TOTAL
    ========================================================= */
    $pickup = strtotime($pickupDate);
    $return = strtotime($returnDate);
    // +1 matches Flutter's numberOfDays = returnDate.difference(pickupDate).inDays + 1
    // e.g., Mon pickup → Fri return = 4 day diff + 1 = 5 rental days
    $days = max(1, (int)(($return - $pickup) / 86400) + 1);

    $baseRental = $days * $vehicle['price_per_day'];
    $insurancePremium = floatval($_POST['insurance_premium'] ?? 0.0);

    // Apply period discount (mirrors Flutter PricingCalculator logic)
    $discount = 0.0;
    if ($rentalPeriod === 'Weekly' && $days >= 7) {
        $discount = $baseRental * 0.12;
    } elseif ($rentalPeriod === 'Monthly' && $days >= 30) {
        $discount = $baseRental * 0.25;
    }
    $discountedRental = $baseRental - $discount;

    // Service fee is 5% of (discounted rental + insurance) — mirrors Flutter PricingCalculator
    $serviceFee = ($discountedRental + $insurancePremium) * 0.05;
    $totalAmount = $discountedRental + $insurancePremium + $serviceFee;

    // Calculate security deposit (20% of rental amount, min ₱500, max ₱10,000)
    $securityDeposit = $totalAmount * 0.20;
    if ($securityDeposit < 500) {
        $securityDeposit = 500;
    } elseif ($securityDeposit > 10000) {
        $securityDeposit = 10000;
    }
    $securityDeposit = round($securityDeposit, 2);

    /* =========================================================
       6️⃣ CREATE BOOKING
    ========================================================= */
    $stmt = $conn->prepare("
        INSERT INTO bookings
            (user_id, vehicle_type, car_id, owner_id,
             pickup_date, return_date, pickup_time, return_time,
             total_amount, price_per_day, rental_period, needs_delivery,
             full_name, email, contact,
             insurance_premium, security_deposit_amount, status, payment_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");

    if (!$stmt) {
        throw new Exception("Failed to prepare booking statement: " . $conn->error);
    }

    $ownerId = intval($vehicle['owner_id']);
    $stmt->bind_param(
        "isiissssddsisssdd",
        $userId,
        $vehicleType,
        $vehicleId,
        $ownerId,
        $pickupDate,
        $returnDate,
        $pickupTime,
        $returnTime,
        $totalAmount,
        $vehicle['price_per_day'],
        $rentalPeriod,
        $needsDelivery,
        $fullName,
        $email,
        $contact,
        $insurancePremium,
        $securityDeposit
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create booking: " . $stmt->error);
    }

    $bookingId = $stmt->insert_id;

    /* =========================================================
       7️⃣ COMMIT
    ========================================================= */
    mysqli_commit($conn);

    // PUSH NOTIFICATION → OWNER (new booking request)
    $renterName = $fullName ?: 'A renter';
    sendPushToUser($conn, $ownerId, '🚗 New Booking Request', "{$renterName} wants to book your vehicle.", [
        'type'       => 'new_booking',
        'booking_id' => (string)$bookingId,
        'screen'     => 'booking_requests',
    ]);

    // DB NOTIFICATION → OWNER
    $notifOwner = $conn->prepare(
        "INSERT INTO notifications (user_id, title, message, type, read_status, created_at)
         VALUES (?, 'New Booking Request', ?, 'booking', 'unread', NOW())"
    );
    $ownerMsg = "{$renterName} has requested to book your vehicle. Booking #BK-" . str_pad($bookingId, 4, '0', STR_PAD_LEFT);
    $notifOwner->bind_param("is", $ownerId, $ownerMsg);
    $notifOwner->execute();
    $notifOwner->close();

    echo json_encode([
        "success" => true,
        "message" => "Booking created successfully. Please proceed to payment.",
        "data" => [
            "booking_id"        => $bookingId,
            "base_rental"       => $baseRental,
            "discount"          => $discount,
            "discounted_rental" => $discountedRental,
            "insurance_premium" => $insurancePremium,
            "service_fee"       => $serviceFee,
            "total_amount"      => $totalAmount,
            "security_deposit"  => $securityDeposit,
            "grand_total"       => $totalAmount + $securityDeposit,
            "payment_method"    => "gcash",
            "rental_period"     => $rentalPeriod,
            "days"              => $days
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}

$conn->close();