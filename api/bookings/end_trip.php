<?php
// api/end_trip.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$owner_id = $_POST['owner_id'] ?? null;

if (!$booking_id || !$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Verify ownership and that booking can be completed
// Allow completion if:
// 1. Booking is approved
// 2. Owner owns the booking
// 3. Pickup date has started (not future bookings)
// 4. REMOVED: return_date >= CURDATE() restriction (allow past due bookings to be completed)
$checkSql = "SELECT id, user_id, return_date FROM bookings 
             WHERE id = ? AND owner_id = ? 
             AND status = 'approved' 
             AND pickup_date <= CURDATE()";
             
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $booking_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Booking not found, not approved, or has not started yet'
    ]);
    exit;
}

$booking = $result->fetch_assoc();
$userId = $booking['user_id'];
$returnDateTime = $booking['return_date'];

// Check if rental was overdue and calculate late fees
$hoursOverdue = 0;
$lateFee = 0;
$isOverdue = false;

$returnTimestamp = strtotime($returnDateTime);
$currentTimestamp = time();

if ($currentTimestamp > $returnTimestamp) {
    $hoursOverdue = floor(($currentTimestamp - $returnTimestamp) / 3600);
    $isOverdue = true;
    
    // Calculate late fee (same logic as cron job)
    $lateFee = calculateLateFee($hoursOverdue);
}

// Update booking to completed with late fee info
$updateSql = "UPDATE bookings 
              SET status = 'completed', 
                  late_fee_amount = ?,
                  late_fee_charged = 1,
                  updated_at = NOW() 
              WHERE id = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("ds", $lateFee, $booking_id);

if ($stmt->execute()) {
    // Log if late fee was charged
    if ($isOverdue && $lateFee > 0) {
        $logSql = "INSERT INTO overdue_logs 
                  (booking_id, days_overdue, hours_overdue, late_fee_charged, action_taken, notes, created_at)
                  VALUES (?, ?, ?, ?, 'resolved', 'Trip completed with late fee', NOW())";
        $stmt = $conn->prepare($logSql);
        $daysOverdue = floor($hoursOverdue / 24);
        $stmt->bind_param("iiid", $booking_id, $daysOverdue, $hoursOverdue, $lateFee);
        $stmt->execute();
    }
    
    // Send notification to renter
    $notifSql = "INSERT INTO notifications (user_id, title, message, created_at) 
                 VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($notifSql);
    $title = "Trip Completed ✓";
    
    if ($isOverdue && $lateFee > 0) {
        $message = "Your rental for booking #{$booking_id} has been completed. Late fee charged: ₱" . number_format($lateFee, 2) . " (returned {$hoursOverdue} hours late).";
    } else {
        $message = "Your rental for booking #{$booking_id} has been completed. Thank you!";
    }
    
    $stmt->bind_param("sss", $userId, $title, $message);
    $stmt->execute();
    
    $response = [
        'success' => true,
        'message' => 'Trip marked as completed successfully',
        'was_overdue' => $isOverdue,
        'hours_overdue' => $hoursOverdue,
        'late_fee' => $lateFee
    ];
    
    if ($isOverdue && $lateFee > 0) {
        $response['warning'] = "Vehicle was returned {$hoursOverdue} hours late. Late fee of ₱" . number_format($lateFee, 2) . " has been added to the booking.";
    }
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to complete trip'
    ]);
}

// Helper function for late fee calculation
function calculateLateFee($hoursLate) {
    $graceHours = 2;
    
    if ($hoursLate <= $graceHours) {
        return 0;
    }
    
    $tier1Rate = 300;
    $tier2Rate = 500;
    $tier3Rate = 2000;
    
    $fee = 0;
    
    if ($hoursLate > $graceHours && $hoursLate <= 6) {
        $fee = ($hoursLate - $graceHours) * $tier1Rate;
    } elseif ($hoursLate > 6 && $hoursLate < 24) {
        $tier1Hours = 6 - $graceHours;
        $fee = ($tier1Hours * $tier1Rate) + (($hoursLate - 6) * $tier2Rate);
    } else {
        $daysLate = floor($hoursLate / 24);
        $remainingHours = $hoursLate % 24;
        
        $tier1Hours = 6 - $graceHours;
        $fee = ($tier1Hours * $tier1Rate) + (18 * $tier2Rate) + ($daysLate * $tier3Rate);
        
        if ($remainingHours > $graceHours) {
            if ($remainingHours <= 6) {
                $fee += ($remainingHours - $graceHours) * $tier1Rate;
            } else {
                $fee += ($tier1Hours * $tier1Rate) + (($remainingHours - 6) * $tier2Rate);
            }
        }
    }
    
    return round($fee, 2);
}

$stmt->close();
$conn->close();
?>