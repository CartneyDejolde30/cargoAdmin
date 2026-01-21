<?php
// api/dashboard/dashboard_stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../include/db.php';

$owner_id = $_GET['owner_id'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID required']);
    exit;
}

try {
    // ========================================
    // CAR STATISTICS
    // ========================================
    
    // Total Cars
    $carStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ?");
    $carStmt->bind_param("s", $owner_id);
    $carStmt->execute();
    $totalCars = $carStmt->get_result()->fetch_assoc()['total'];
    
    // Approved Cars
    $approvedStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'approved'");
    $approvedStmt->bind_param("s", $owner_id);
    $approvedStmt->execute();
    $approvedCars = $approvedStmt->get_result()->fetch_assoc()['total'];
    
    // Pending Cars
    $pendingStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'pending'");
    $pendingStmt->bind_param("s", $owner_id);
    $pendingStmt->execute();
    $pendingCars = $pendingStmt->get_result()->fetch_assoc()['total'];
    
    // Rented Cars (currently active)
    $rentedStmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'rented'");
    $rentedStmt->bind_param("s", $owner_id);
    $rentedStmt->execute();
    $rentedCars = $rentedStmt->get_result()->fetch_assoc()['total'];

    // ========================================
    // BOOKING STATISTICS
    // ========================================
    
    // Total Bookings (all statuses)
    $totalBookingsStmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE owner_id = ?");
    $totalBookingsStmt->bind_param("s", $owner_id);
    $totalBookingsStmt->execute();
    $totalBookings = $totalBookingsStmt->get_result()->fetch_assoc()['total'];
    
    // Pending Requests
    $pendingRequestsStmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE owner_id = ? AND status = 'pending'");
    $pendingRequestsStmt->bind_param("s", $owner_id);
    $pendingRequestsStmt->execute();
    $pendingRequests = $pendingRequestsStmt->get_result()->fetch_assoc()['total'];
    
    // Active Bookings (approved and currently ongoing)
    $activeBookingsStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM bookings 
        WHERE owner_id = ? 
        AND status = 'approved'
        AND pickup_date <= CURDATE()
        AND return_date >= CURDATE()
    ");
    $activeBookingsStmt->bind_param("s", $owner_id);
    $activeBookingsStmt->execute();
    $activeBookings = $activeBookingsStmt->get_result()->fetch_assoc()['total'];
    
    // Cancelled Bookings (cancelled by renter)
    $cancelledStmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE owner_id = ? AND status = 'cancelled'");
    $cancelledStmt->bind_param("s", $owner_id);
    $cancelledStmt->execute();
    $cancelledBookings = $cancelledStmt->get_result()->fetch_assoc()['total'];
    
    // Rejected Bookings (rejected by owner)
    $rejectedStmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE owner_id = ? AND status = 'rejected'");
    $rejectedStmt->bind_param("s", $owner_id);
    $rejectedStmt->execute();
    $rejectedBookings = $rejectedStmt->get_result()->fetch_assoc()['total'];

    // ========================================
    // INCOME STATISTICS (FIXED)
    // ========================================
    
    // Total Income (completed + active bookings only)
    $totalIncomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings 
        WHERE owner_id = ? 
        AND status IN ('completed', 'approved')
    ");
    $totalIncomeStmt->bind_param("s", $owner_id);
    $totalIncomeStmt->execute();
    $totalIncome = floatval($totalIncomeStmt->get_result()->fetch_assoc()['total']);
    
    // Monthly Income (current month)
    $monthlyIncomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings 
        WHERE owner_id = ? 
        AND status IN ('completed', 'approved')
        AND YEAR(created_at) = YEAR(CURDATE())
        AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $monthlyIncomeStmt->bind_param("s", $owner_id);
    $monthlyIncomeStmt->execute();
    $monthlyIncome = floatval($monthlyIncomeStmt->get_result()->fetch_assoc()['total']);
    
    // Weekly Income (last 7 days)
    $weeklyIncomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings 
        WHERE owner_id = ? 
        AND status IN ('completed', 'approved')
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $weeklyIncomeStmt->bind_param("s", $owner_id);
    $weeklyIncomeStmt->execute();
    $weeklyIncome = floatval($weeklyIncomeStmt->get_result()->fetch_assoc()['total']);
    
    // Today's Income
    $todayIncomeStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM bookings 
        WHERE owner_id = ? 
        AND status IN ('completed', 'approved')
        AND DATE(created_at) = CURDATE()
    ");
    $todayIncomeStmt->bind_param("s", $owner_id);
    $todayIncomeStmt->execute();
    $todayIncome = floatval($todayIncomeStmt->get_result()->fetch_assoc()['total']);

    // ========================================
    // NOTIFICATIONS & MESSAGES
    // ========================================
    
    // Unread Notifications
    $notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND read_status = 'unread'");
    $notifStmt->bind_param("s", $owner_id);
    $notifStmt->execute();
    $unreadNotifications = $notifStmt->get_result()->fetch_assoc()['total'];
    
    // Unread Messages (placeholder - implement based on your messaging system)
    $unreadMessages = 0;

    // ========================================
    // RETURN RESPONSE
    // ========================================
    
    echo json_encode([
        'success' => true,
        'stats' => [
            // Car Stats
            'total_cars' => intval($totalCars),
            'approved_cars' => intval($approvedCars),
            'pending_cars' => intval($pendingCars),
            'rented_cars' => intval($rentedCars),
            
            // Booking Stats
            'total_bookings' => intval($totalBookings),
            'pending_requests' => intval($pendingRequests),
            'active_bookings' => intval($activeBookings),
            'cancelled_bookings' => intval($cancelledBookings),
            'rejected_bookings' => intval($rejectedBookings),
            
            // Income Stats
            'total_income' => $totalIncome,
            'monthly_income' => $monthlyIncome,
            'weekly_income' => $weeklyIncome,
            'today_income' => $todayIncome,
            
            // Notifications
            'unread_notifications' => intval($unreadNotifications),
            'unread_messages' => intval($unreadMessages)
        ]
    ]);

    // Close all statements
    $carStmt->close();
    $approvedStmt->close();
    $pendingStmt->close();
    $rentedStmt->close();
    $totalBookingsStmt->close();
    $pendingRequestsStmt->close();
    $activeBookingsStmt->close();
    $cancelledStmt->close();
    $rejectedStmt->close();
    $totalIncomeStmt->close();
    $monthlyIncomeStmt->close();
    $weeklyIncomeStmt->close();
    $todayIncomeStmt->close();
    $notifStmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
    ]);
}

$conn->close();
?>