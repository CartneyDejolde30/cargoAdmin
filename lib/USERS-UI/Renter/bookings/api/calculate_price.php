<?php
// lib/USERS-UI/Renter/bookings/api/calculate_price.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration constants
define('DRIVER_FEE_PER_DAY', 600.00);
define('INSURANCE_RATE', 0.12);
define('DELIVERY_FEE_BASE', 300.00);
define('DELIVERY_FEE_PER_KM', 15.00);
define('WEEKLY_DISCOUNT_RATE', 0.12);
define('MONTHLY_DISCOUNT_RATE', 0.25);
define('SERVICE_FEE_RATE', 0.05);
define('EXCESS_MILEAGE_RATE', 10.00);
define('DAILY_MILEAGE_LIMIT', 200);
define('LATE_RETURN_FEE_PER_HOUR', 300.00);
define('CLEANING_FEE', 400.00);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Required parameters
    $pricePerDay = floatval($input['price_per_day'] ?? 0);
    $numberOfDays = intval($input['number_of_days'] ?? 0);
    $withDriver = boolval($input['with_driver'] ?? false);
    $rentalPeriod = $input['rental_period'] ?? 'Day'; // 'Day', 'Weekly', 'Monthly'
    
    // Optional parameters
    $needsDelivery = boolval($input['needs_delivery'] ?? false);
    $deliveryDistance = floatval($input['delivery_distance'] ?? 0);
    $includeInsurance = boolval($input['include_insurance'] ?? true);
    
    // Validation
    if ($pricePerDay <= 0 || $numberOfDays <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid price or number of days'
        ]);
        exit;
    }
    
    // 1. Base rental cost
    $baseRental = $pricePerDay * $numberOfDays;
    
    // 2. Apply period discounts
    $discount = 0.0;
    $discountType = 'None';
    
    if ($rentalPeriod === 'Weekly' && $numberOfDays >= 7) {
        $discount = $baseRental * WEEKLY_DISCOUNT_RATE;
        $discountType = 'Weekly (12%)';
    } elseif ($rentalPeriod === 'Monthly' && $numberOfDays >= 30) {
        $discount = $baseRental * MONTHLY_DISCOUNT_RATE;
        $discountType = 'Monthly (25%)';
    }
    
    $discountedRental = $baseRental - $discount;
    
    // 3. Driver fee
    $driverFee = $withDriver ? (DRIVER_FEE_PER_DAY * $numberOfDays) : 0.0;
    
    // 4. Insurance fee
    $insuranceFee = $includeInsurance ? ($discountedRental * INSURANCE_RATE) : 0.0;
    
    // 5. Delivery fee
    $deliveryFee = 0.0;
    if ($needsDelivery) {
        $deliveryFee = DELIVERY_FEE_BASE + ($deliveryDistance * DELIVERY_FEE_PER_KM);
    }
    
    // 6. Calculate subtotal
    $subtotal = $discountedRental + $driverFee + $insuranceFee + $deliveryFee;
    
    // 7. Service fee (platform fee)
    $serviceFee = $subtotal * SERVICE_FEE_RATE;
    
    // 8. Total amount
    $totalAmount = $subtotal + $serviceFee;
    
    // Response
    echo json_encode([
        'success' => true,
        'breakdown' => [
            'base_rental' => round($baseRental, 2),
            'price_per_day' => round($pricePerDay, 2),
            'number_of_days' => $numberOfDays,
            
            'discount' => round($discount, 2),
            'discount_type' => $discountType,
            'discount_percentage' => $discount > 0 ? round(($discount / $baseRental) * 100, 1) : 0,
            'discounted_rental' => round($discountedRental, 2),
            
            'driver_fee' => round($driverFee, 2),
            'driver_fee_per_day' => DRIVER_FEE_PER_DAY,
            
            'insurance_fee' => round($insuranceFee, 2),
            'insurance_rate' => INSURANCE_RATE * 100 . '%',
            
            'delivery_fee' => round($deliveryFee, 2),
            'delivery_distance' => $deliveryDistance,
            
            'subtotal' => round($subtotal, 2),
            
            'service_fee' => round($serviceFee, 2),
            'service_fee_rate' => SERVICE_FEE_RATE * 100 . '%',
            
            'total_amount' => round($totalAmount, 2),
            
            'effective_daily_rate' => round($totalAmount / $numberOfDays, 2)
        ]
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>