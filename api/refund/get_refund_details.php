<?php
/**
 * ============================================================================
 * GET REFUND DETAILS - For admin modal view
 * ============================================================================
 */

header('Content-Type: application/json');

require_once '../../include/db.php';

$refund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($refund_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid refund ID'
    ]);
    exit;
}

// ============================================================================
// GET REFUND DETAILS
// ============================================================================

$query = "
    SELECT 
        r.*,
        
        -- Renter info
        u_renter.fullname AS renter_name,
        u_renter.email AS renter_email,
        u_renter.phone AS renter_phone,
        
        -- Owner info
        u_owner.fullname AS owner_name,
        u_owner.email AS owner_email,
        
        -- Booking info
        b.id AS booking_id,
        b.pickup_date,
        b.return_date,
        b.total_amount AS booking_amount,
        b.vehicle_type,
        
        -- Car info
        COALESCE(c.brand, m.brand) AS car_brand,
        COALESCE(c.model, m.model) AS car_model,
        COALESCE(c.car_year, m.motorcycle_year) AS car_year,
        CONCAT(
            COALESCE(c.brand, m.brand), ' ',
            COALESCE(c.model, m.model), ' ',
            COALESCE(c.car_year, m.motorcycle_year)
        ) AS car_full_name,
        
        -- Admin who processed
        admin.fullname AS processed_by_name
        
    FROM refunds r
    LEFT JOIN users u_renter ON r.user_id = u_renter.id
    LEFT JOIN users u_owner ON r.owner_id = u_owner.id
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
    LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
    LEFT JOIN admin ON r.processed_by = admin.id
    WHERE r.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $refund_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Refund not found'
    ]);
    exit;
}

$refund = $result->fetch_assoc();

// Format dates
$refund['created_at'] = date('M d, Y h:i A', strtotime($refund['created_at']));
$refund['approved_at'] = $refund['approved_at'] ? date('M d, Y h:i A', strtotime($refund['approved_at'])) : null;
$refund['completed_at'] = $refund['completed_at'] ? date('M d, Y h:i A', strtotime($refund['completed_at'])) : null;
$refund['pickup_date'] = date('M d, Y', strtotime($refund['pickup_date']));
$refund['return_date'] = date('M d, Y', strtotime($refund['return_date']));

echo json_encode([
    'success' => true,
    'refund' => $refund
]);

$conn->close();