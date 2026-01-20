<?php
// api/end_trip.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../include/db.php';

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

// Verify ownership and that booking is active
$checkSql = "SELECT id, user_id FROM bookings 
             WHERE id = ? AND owner_id = ? 
             AND status = 'approved' 
             AND pickup_date <= CURDATE() 
             AND return_date >= CURDATE()";
             
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $booking_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Booking not found or not active'
    ]);
    exit;
}

$booking = $result->fetch_assoc();
$userId = $booking['user_id'];

// Update booking to completed
$updateSql = "UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("s", $booking_id);

if ($stmt->execute()) {
    // Send notification to renter
    $notifSql = "INSERT INTO notifications (user_id, title, message, created_at) 
                 VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($notifSql);
    $title = "Trip Completed ✓";
    $message = "Your rental for booking #{$booking_id} has been completed. Thank you!";
    $stmt->bind_param("sss", $userId, $title, $message);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Trip marked as completed successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to complete trip'
    ]);
}

$stmt->close();
$conn->close();
?>