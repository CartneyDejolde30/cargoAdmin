<?php
/**
 * ============================================================================
 * GET REFUND HISTORY - For renter's refund history screen
 * ============================================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../include/db.php';

// ============================================================================
// GET USER ID
// ============================================================================

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

// ============================================================================
// GET REFUND HISTORY
// ============================================================================

$query = "
    SELECT 
        r.id,
        r.refund_id,
        r.booking_id,
        r.refund_amount,
        r.original_amount,
        r.deduction_amount,
        r.deduction_reason,
        r.refund_method,
        r.account_number,
        r.account_name,
        r.refund_reason,
        r.reason_details,
        r.status,
        r.admin_notes,
        r.rejection_reason,
        r.refund_reference,
        r.created_at,
        r.approved_at,
        r.completed_at,
        
        -- Booking info
        CONCAT('#BK-', LPAD(b.id, 4, '0')) AS booking_reference,
        b.pickup_date,
        b.return_date,
        b.status AS booking_status,
        
        -- Car info
        COALESCE(c.brand, m.brand) AS car_brand,
        COALESCE(c.model, m.model) AS car_model,
        COALESCE(c.car_year, m.motorcycle_year) AS car_year,
        COALESCE(c.image, m.image) AS car_image,
        CONCAT(
            COALESCE(c.brand, m.brand), ' ',
            COALESCE(c.model, m.model), ' ',
            COALESCE(c.car_year, m.motorcycle_year)
        ) AS car_full_name,
        
        -- Payment info
        p.payment_method AS original_payment_method,
        p.payment_reference AS original_payment_reference,
        
        -- Calculate days since request
        DATEDIFF(NOW(), r.created_at) AS days_pending
        
    FROM refunds r
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN payments p ON r.payment_id = p.id
    LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
    LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$refunds = [];

while ($row = $result->fetch_assoc()) {
    // Format dates
    $row['created_at_formatted'] = date('M d, Y h:i A', strtotime($row['created_at']));
    $row['approved_at_formatted'] = $row['approved_at'] ? date('M d, Y h:i A', strtotime($row['approved_at'])) : null;
    $row['completed_at_formatted'] = $row['completed_at'] ? date('M d, Y h:i A', strtotime($row['completed_at'])) : null;
    
    // Calculate final refund amount
    $row['final_refund_amount'] = $row['refund_amount'] - $row['deduction_amount'];
    
    // Status badge info
    $row['status_badge'] = _getStatusBadge($row['status']);
    
    // Expected completion date (3-5 business days from approval)
    if ($row['status'] === 'approved' && !$row['completed_at']) {
        $approved_date = new DateTime($row['approved_at']);
        $approved_date->modify('+5 business days');
        $row['expected_completion'] = $approved_date->format('M d, Y');
    }
    
    $refunds[] = $row;
}

// ============================================================================
// GET STATISTICS
// ============================================================================

$stats_query = "
    SELECT 
        COUNT(*) AS total_refunds,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        SUM(CASE WHEN status = 'completed' THEN refund_amount - deduction_amount ELSE 0 END) AS total_refunded
    FROM refunds
    WHERE user_id = ?
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// ============================================================================
// SUCCESS RESPONSE
// ============================================================================

echo json_encode([
    'success' => true,
    'refunds' => $refunds,
    'statistics' => $stats
]);

$conn->close();

// ============================================================================
// HELPER FUNCTION - Get Status Badge Info
// ============================================================================

function _getStatusBadge($status) {
    $badges = [
        'pending' => [
            'label' => 'PENDING REVIEW',
            'color' => 'orange',
            'icon' => 'schedule'
        ],
        'approved' => [
            'label' => 'APPROVED',
            'color' => 'blue',
            'icon' => 'check_circle'
        ],
        'processing' => [
            'label' => 'PROCESSING',
            'color' => 'blue',
            'icon' => 'sync'
        ],
        'completed' => [
            'label' => 'COMPLETED',
            'color' => 'green',
            'icon' => 'check_circle'
        ],
        'rejected' => [
            'label' => 'REJECTED',
            'color' => 'red',
            'icon' => 'cancel'
        ],
        'cancelled' => [
            'label' => 'CANCELLED',
            'color' => 'grey',
            'icon' => 'cancel'
        ]
    ];
    
    return $badges[$status] ?? $badges['pending'];
}