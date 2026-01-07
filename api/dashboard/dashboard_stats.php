<?php
// ========================================
// api/dashboard/dashboard_stats.php
// ========================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

// Get stats
$stats = [
    // Cars
    'total_cars' => 0,
    'approved_cars' => 0,
    'pending_cars' => 0,
    'rented_cars' => 0,
    
    // Bookings
    'total_bookings' => 0,
    'pending_requests' => 0,
    'active_bookings' => 0,
    
    // Income
    'total_income' => 0,
    'monthly_income' => 0,
    'weekly_income' => 0,
    'today_income' => 0,
    
    // Notifications
    'unread_notifications' => 0,
    'unread_messages' => 0
];

// Total cars
$sql = "SELECT COUNT(*) as count FROM cars WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['total_cars'] = $stmt->get_result()->fetch_assoc()['count'];

// Approved cars
$sql = "SELECT COUNT(*) as count FROM cars WHERE owner_id = ? AND status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['approved_cars'] = $stmt->get_result()->fetch_assoc()['count'];

// Pending cars
$sql = "SELECT COUNT(*) as count FROM cars WHERE owner_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['pending_cars'] = $stmt->get_result()->fetch_assoc()['count'];

// Total bookings
$sql = "SELECT COUNT(*) as count FROM bookings WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['total_bookings'] = $stmt->get_result()->fetch_assoc()['count'];

// Pending requests
$sql = "SELECT COUNT(*) as count FROM bookings WHERE owner_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['pending_requests'] = $stmt->get_result()->fetch_assoc()['count'];

// Active bookings
$sql = "SELECT COUNT(*) as count FROM bookings 
        WHERE owner_id = ? AND status = 'approved' 
        AND pickup_date <= CURDATE() AND return_date >= CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['active_bookings'] = $stmt->get_result()->fetch_assoc()['count'];

// Total income (completed bookings)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings WHERE owner_id = ? AND status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['total_income'] = floatval($stmt->get_result()->fetch_assoc()['total']);

// Monthly income
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings WHERE owner_id = ? AND status = 'completed'
        AND MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['monthly_income'] = floatval($stmt->get_result()->fetch_assoc()['total']);

// Weekly income
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings WHERE owner_id = ? AND status = 'completed'
        AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['weekly_income'] = floatval($stmt->get_result()->fetch_assoc()['total']);

// Today income
$sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings WHERE owner_id = ? AND status = 'completed'
        AND DATE(created_at) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$stats['today_income'] = floatval($stmt->get_result()->fetch_assoc()['total']);

echo json_encode([
    'success' => true,
    'stats' => $stats
]);

$stmt->close();
$conn->close();
?>