<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../include/db.php';

$userId = intval($_GET['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$sql = "
SELECT 
    b.id AS bookingId,
    b.car_id AS carId,
    b.vehicle_type,
    b.owner_id AS ownerId,
    b.pickup_date,
    b.pickup_time,
    b.return_date,
    b.return_time,
    b.total_amount,
    b.price_per_day,
    b.insurance_premium,
    b.security_deposit_amount,
    b.rental_period,
    b.status,
    b.created_at,
    b.is_reviewed,
    b.trip_started,
    b.odometer_start,
    
    -- Refund fields
    b.refund_status,
    b.refund_requested,
    b.refund_amount,
    b.escrow_status,

    -- Car fields
    c.brand AS car_brand,
    c.model AS car_model,
    c.image AS car_image,
    c.location AS car_location,
    c.latitude AS car_latitude,
    c.longitude AS car_longitude,
    
    -- Motorcycle fields
    m.brand AS moto_brand,
    m.model AS moto_model,
    m.image AS moto_image,
    m.location AS moto_location,
    m.latitude AS moto_latitude,
    m.longitude AS moto_longitude,

    -- Owner fields
    u.fullname AS ownerName,
    u.profile_image AS ownerAvatar,
    u.phone AS ownerPhone
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id AND b.vehicle_type = 'car'
LEFT JOIN motorcycles m ON b.car_id = m.id AND b.vehicle_type = 'motorcycle'
LEFT JOIN users u ON b.owner_id = u.id
WHERE b.user_id = ?
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    // Determine vehicle details based on type
    $vehicleType = $row['vehicle_type'] ?? 'car';
    $brand = $vehicleType === 'motorcycle' ? $row['moto_brand'] : $row['car_brand'];
    $model = $vehicleType === 'motorcycle' ? $row['moto_model'] : $row['car_model'];
    $image = $vehicleType === 'motorcycle' ? $row['moto_image'] : $row['car_image'];
    $location = $vehicleType === 'motorcycle' ? $row['moto_location'] : $row['car_location'];
    $latitude = $vehicleType === 'motorcycle' ? $row['moto_latitude'] : $row['car_latitude'];
    $longitude = $vehicleType === 'motorcycle' ? $row['moto_longitude'] : $row['car_longitude'];
    
    // Build the final image URL
    $finalImageUrl = '';
    if (!empty($image) && trim($image) !== '') {
        if (strpos($image, 'http') === 0) {
            $finalImageUrl = $image;  // Already full URL
        } else {
            $cleanPath = ltrim(str_replace('uploads/', '', $image), '/');
            $finalImageUrl = UPLOADS_URL . '/' . $cleanPath;
        }
    }

    // Build owner avatar URL
    $ownerAvatarUrl = '';
    $ownerAvatar = $row['ownerAvatar'] ?? '';
    if (!empty($ownerAvatar) && trim($ownerAvatar) !== '') {
        if (strpos($ownerAvatar, 'http') === 0) {
            $ownerAvatarUrl = $ownerAvatar;
        } else {
            $cleanAvatarPath = ltrim(str_replace('uploads/', '', $ownerAvatar), '/');
            $ownerAvatarUrl = UPLOADS_URL . '/' . $cleanAvatarPath;
        }
    }

    // Compute price breakdown
    $bDays     = max(1, (int)((strtotime($row['return_date']) - strtotime($row['pickup_date'])) / 86400) + 1);
    $bPpd      = (float)($row['price_per_day'] ?? 0);
    $bBase     = $bPpd * $bDays;
    $bIns      = (float)($row['insurance_premium'] ?? 0);
    $bPeriod   = $row['rental_period'] ?? 'Day';
    $bDiscount = 0.0;
    if ($bPeriod === 'Weekly' && $bDays >= 7) {
        $bDiscount = $bBase * 0.12;
    } elseif ($bPeriod === 'Monthly' && $bDays >= 30) {
        $bDiscount = $bBase * 0.25;
    }
    $bDiscounted = $bBase - $bDiscount;
    $bSvcFee   = ($bDiscounted + $bIns) * 0.05;
    $bSecDep   = (float)($row['security_deposit_amount'] ?? 0);

    $bookings[] = [
        'bookingId'   => (int)$row['bookingId'],
        'carId'       => (int)$row['carId'],
        'ownerId'     => (int)$row['ownerId'],

        'carName'     => trim(($brand ?? '') . ' ' . ($model ?? '')),
        'carImage'    => $finalImageUrl, // Returns empty string if no image

        'location'    => $location ?? 'Location not set',
        'latitude'    => !empty($latitude) ? (float)$latitude : null,
        'longitude'   => !empty($longitude) ? (float)$longitude : null,

        // ⚠️ SEND RAW VALUES (FORMAT IN FLUTTER)
        'pickupDate'  => $row['pickup_date'],
        'pickupTime'  => $row['pickup_time'],
        'returnDate'  => $row['return_date'],
        'returnTime'  => $row['return_time'],

        'totalPrice'        => (float)$row['total_amount'],
        'pricePerDay'       => $bPpd,
        'securityDeposit'   => $bSecDep,
        // Price breakdown
        'baseRental'        => round($bBase, 2),
        'discount'          => round($bDiscount, 2),
        'insurancePremium'  => round($bIns, 2),
        'serviceFee'        => round($bSvcFee, 2),
        'grandTotal'        => round((float)$row['total_amount'] + $bSecDep, 2),
        'rentalDays'        => (int)$bDays,
        'rentalPeriod'      => $bPeriod,
        'status'            => $row['status'],
        'ownerName'   => $row['ownerName'] ?? 'Unknown',
        'ownerAvatar' => $ownerAvatarUrl,
        'ownerPhone'  => $row['ownerPhone'] ?? '',
        'isReviewed'  => (int)($row['is_reviewed'] ?? 0),
        'tripStarted' => (int)($row['trip_started'] ?? 0),
        'odometerStart' => $row['odometer_start'] ?? null,

        // Refund status fields
        'refundStatus'    => $row['refund_status'] ?? 'not_requested',
        'refundRequested' => (int)($row['refund_requested'] ?? 0),
        'refundAmount'    => (float)($row['refund_amount'] ?? 0),
        'escrowStatus'    => $row['escrow_status'] ?? null,
    ];
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);

$conn->close();
