<?php
header('Content-Type: application/json');
require_once '../config.php';

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

try {
    // Total Cars
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $total_cars = $stmt->fetch()['total'];

    // Approved Cars
    $stmt = $conn->prepare("SELECT COUNT(*) as approved FROM cars WHERE owner_id = ? AND status = 'approved'");
    $stmt->execute([$owner_id]);
    $approved_cars = $stmt->fetch()['approved'];

    // Pending Cars
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM cars WHERE owner_id = ? AND status = 'pending'");
    $stmt->execute([$owner_id]);
    $pending_cars = $stmt->fetch()['pending'];

    // Active Bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM bookings WHERE owner_id = ? AND status = 'approved'");
    $stmt->execute([$owner_id]);
    $active_bookings = $stmt->fetch()['active'];

    // Pending Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE owner_id = ? AND status = 'pending'");
    $stmt->execute([$owner_id]);
    $pending_requests = $stmt->fetch()['pending'];

    // Total Income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE owner_id = ? AND status IN ('approved', 'completed')");
    $stmt->execute([$owner_id]);
    $total_income = $stmt->fetch()['total'];

    // Monthly Income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as monthly FROM bookings WHERE owner_id = ? AND status IN ('approved', 'completed') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute([$owner_id]);
    $monthly_income = $stmt->fetch()['monthly'];

    // Weekly Income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as weekly FROM bookings WHERE owner_id = ? AND status IN ('approved', 'completed') AND YEARWEEK(created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)");
    $stmt->execute([$owner_id]);
    $weekly_income = $stmt->fetch()['weekly'];

    // Today Income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as today FROM bookings WHERE owner_id = ? AND status IN ('approved', 'completed') AND DATE(created_at) = CURRENT_DATE()");
    $stmt->execute([$owner_id]);
    $today_income = $stmt->fetch()['today'];

    // Unread Notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND read_status = 'unread'");
    $stmt->execute([$owner_id]);
    $unread_notifications = $stmt->fetch()['unread'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_cars' => $total_cars,
            'approved_cars' => $approved_cars,
            'pending_cars' => $pending_cars,
            'total_bookings' => $active_bookings + $pending_requests,
            'active_bookings' => $active_bookings,
            'pending_requests' => $pending_requests,
            'total_income' => floatval($total_income),
            'monthly_income' => floatval($monthly_income),
            'weekly_income' => floatval($weekly_income),
            'today_income' => floatval($today_income),
            'unread_notifications' => $unread_notifications,
            'unread_messages' => 0 // Implement if needed
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>