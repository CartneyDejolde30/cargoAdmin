<?php
/**
 * DIAGNOSTIC SCRIPT - Check for Hidden Refund Requests
 * Compares refunds table with what's displayed in refunds.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once '../../include/db.php';

echo "<html><head><title>Hidden Refunds Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    h1 { color: #333; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }
    h2 { color: #666; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #3498db; color: white; position: sticky; top: 0; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    tr:hover { background-color: #e8f4f8; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .status { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
    .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    .danger-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
    .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Hidden Refunds Diagnostic Report</h1>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

// ============================================================================
// TEST 1: Count all refunds in database
// ============================================================================
echo "<h2>Test 1: Total Refunds in Database</h2>";
$count_query = "SELECT COUNT(*) as total FROM refunds";
$result = mysqli_query($conn, $count_query);
$total_refunds = mysqli_fetch_assoc($result)['total'];
echo "<p><strong>Total refund records in database:</strong> <span class='success'>{$total_refunds}</span></p>";

// ============================================================================
// TEST 2: Get ALL refunds with raw data
// ============================================================================
echo "<h2>Test 2: All Refund Records (Raw Data)</h2>";
$all_refunds_query = "
    SELECT 
        r.id,
        r.refund_id,
        r.booking_id,
        r.user_id,
        r.owner_id,
        r.status,
        r.refund_amount,
        r.refund_reason,
        r.created_at,
        r.processed_at
    FROM refunds r
    ORDER BY r.id DESC
";

$result = mysqli_query($conn, $all_refunds_query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Refund ID</th>";
    echo "<th>Booking ID</th>";
    echo "<th>User ID</th>";
    echo "<th>Owner ID</th>";
    echo "<th>Status</th>";
    echo "<th>Amount</th>";
    echo "<th>Reason</th>";
    echo "<th>Created</th>";
    echo "<th>Processed</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status_class = "status status-" . strtolower($row['status']);
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['refund_id']}</td>";
        echo "<td>{$row['booking_id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . ($row['owner_id'] ?: '<span class="warning">NULL</span>') . "</td>";
        echo "<td><span class='$status_class'>{$row['status']}</span></td>";
        echo "<td>₱" . number_format($row['refund_amount'], 2) . "</td>";
        echo "<td>{$row['refund_reason']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>" . ($row['processed_at'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>No refund records found in database.</p>";
}

// ============================================================================
// TEST 3: Get refunds with FULL JOINs (as in refunds.php)
// ============================================================================
echo "<h2>Test 3: Refunds with JOIN Query (As Used in refunds.php)</h2>";

$joined_query = "
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
        b.owner_id,
        b.status AS booking_status,
        b.pickup_date,
        b.return_date,
        b.total_amount AS booking_amount,
        b.vehicle_type,
        
        -- Car info
        COALESCE(c.brand, m.brand) AS car_brand,
        COALESCE(c.model, m.model) AS car_model,
        
        -- Payment info
        p.payment_method AS original_payment_method,
        p.payment_reference AS original_payment_reference
        
    FROM refunds r
    
    -- Join booking (REQUIRED)
    INNER JOIN bookings b ON r.booking_id = b.id
    
    -- Join users
    LEFT JOIN users u_renter ON r.user_id = u_renter.id
    LEFT JOIN users u_owner ON b.owner_id = u_owner.id
    
    -- Join vehicle (car or motorcycle)
    LEFT JOIN cars c ON b.car_id = c.id AND b.vehicle_type = 'car'
    LEFT JOIN motorcycles m ON b.car_id = m.id AND b.vehicle_type = 'motorcycle'
    
    -- Join payment
    LEFT JOIN payments p ON r.payment_id = p.id
    
    ORDER BY r.created_at DESC
";

$result = mysqli_query($conn, $joined_query);

if (!$result) {
    echo "<div class='danger-box'>";
    echo "<strong>❌ SQL ERROR:</strong> " . mysqli_error($conn);
    echo "</div>";
} else {
    $joined_count = mysqli_num_rows($result);
    
    if ($joined_count < $total_refunds) {
        echo "<div class='danger-box'>";
        echo "<strong>⚠️ MISMATCH DETECTED!</strong><br>";
        echo "Raw refunds count: <strong>{$total_refunds}</strong><br>";
        echo "Joined query count: <strong>{$joined_count}</strong><br>";
        echo "Missing refunds: <strong class='error'>" . ($total_refunds - $joined_count) . "</strong>";
        echo "</div>";
    } else {
        echo "<div class='info-box'>";
        echo "<strong>✓ All refunds are being retrieved:</strong> {$joined_count} records";
        echo "</div>";
    }
    
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Refund ID</th>";
    echo "<th>Renter</th>";
    echo "<th>Owner</th>";
    echo "<th>Vehicle</th>";
    echo "<th>Status</th>";
    echo "<th>Amount</th>";
    echo "<th>Created</th>";
    echo "<th>Issues</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status_class = "status status-" . strtolower($row['status']);
        $issues = [];
        
        // Check for missing data
        if (!$row['renter_name']) $issues[] = 'No renter name';
        if (!$row['owner_name']) $issues[] = 'No owner name';
        if (!$row['car_brand']) $issues[] = 'No vehicle info';
        if (!$row['booking_id']) $issues[] = 'No booking';
        
        $issue_text = empty($issues) ? '<span class="success">✓ OK</span>' : '<span class="warning">' . implode(', ', $issues) . '</span>';
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['refund_id']}</td>";
        echo "<td>" . ($row['renter_name'] ?: '<span class="warning">NULL</span>') . "<br><small>{$row['renter_email']}</small></td>";
        echo "<td>" . ($row['owner_name'] ?: '<span class="warning">NULL</span>') . "</td>";
        echo "<td>" . ($row['car_brand'] ? $row['car_brand'] . ' ' . $row['car_model'] : '<span class="warning">NULL</span>') . "</td>";
        echo "<td><span class='$status_class'>{$row['status']}</span></td>";
        echo "<td>₱" . number_format($row['refund_amount'], 2) . "</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$issue_text}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ============================================================================
// TEST 4: Check for orphaned refunds
// ============================================================================
echo "<h2>Test 4: Check for Orphaned Refunds</h2>";
echo "<p>Refunds that reference non-existent bookings, users, or vehicles:</p>";

$orphan_query = "
    SELECT 
        r.id,
        r.refund_id,
        r.booking_id,
        r.user_id,
        r.owner_id,
        b.id AS booking_exists,
        u.id AS user_exists,
        owner.id AS owner_exists
    FROM refunds r
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN users owner ON r.owner_id = owner.id
    WHERE b.id IS NULL OR u.id IS NULL
";

$result = mysqli_query($conn, $orphan_query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<div class='danger-box'><strong>⚠️ Orphaned refunds found!</strong></div>";
    echo "<table>";
    echo "<tr><th>Refund ID</th><th>Booking ID</th><th>User ID</th><th>Owner ID</th><th>Issue</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $issue = [];
        if (!$row['booking_exists']) $issue[] = "Booking {$row['booking_id']} doesn't exist";
        if (!$row['user_exists']) $issue[] = "User {$row['user_id']} doesn't exist";
        if ($row['owner_id'] && !$row['owner_exists']) $issue[] = "Owner {$row['owner_id']} doesn't exist";
        
        echo "<tr>";
        echo "<td>{$row['refund_id']}</td>";
        echo "<td>{$row['booking_id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . ($row['owner_id'] ?: 'NULL') . "</td>";
        echo "<td class='error'>" . implode(', ', $issue) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info-box'><strong>✓ No orphaned refunds found</strong> - All refunds reference valid records</div>";
}

// ============================================================================
// TEST 5: Check refunds.php WHERE clause simulation
// ============================================================================
echo "<h2>Test 5: Simulate refunds.php Filtering</h2>";

$statuses = ['all', 'pending', 'approved', 'completed', 'rejected'];

echo "<table>";
echo "<tr><th>Filter</th><th>Count</th><th>Status</th></tr>";

foreach ($statuses as $status) {
    $where = "WHERE 1=1";
    if ($status !== 'all') {
        $where .= " AND r.status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }
    
    $filter_query = "
        SELECT COUNT(*) as count
        FROM refunds r
        INNER JOIN bookings b ON r.booking_id = b.id
        $where
    ";
    
    $result = mysqli_query($conn, $filter_query);
    $count = mysqli_fetch_assoc($result)['count'];
    
    echo "<tr>";
    echo "<td><strong>" . ucfirst($status) . "</strong></td>";
    echo "<td>{$count}</td>";
    echo "<td>" . ($count > 0 ? '<span class="success">✓</span>' : '-') . "</td>";
    echo "</tr>";
}
echo "</table>";

// ============================================================================
// SUMMARY
// ============================================================================
echo "<h2>📋 Summary & Recommendations</h2>";

$summary_query = "
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN b.id IS NULL THEN 1 END) as orphaned,
        COUNT(CASE WHEN u_renter.id IS NULL THEN 1 END) as no_renter,
        COUNT(CASE WHEN b.owner_id IS NOT NULL AND u_owner.id IS NULL THEN 1 END) as no_owner
    FROM refunds r
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN users u_renter ON r.user_id = u_renter.id
    LEFT JOIN users u_owner ON b.owner_id = u_owner.id
";

$result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($result);

echo "<div class='info-box'>";
echo "<strong>Diagnostic Results:</strong><ul>";
echo "<li>Total refunds in database: <strong>{$summary['total']}</strong></li>";
echo "<li>Orphaned refunds (no booking): <strong>" . ($summary['orphaned'] > 0 ? "<span class='error'>{$summary['orphaned']}</span>" : "0") . "</strong></li>";
echo "<li>Refunds with missing renter: <strong>" . ($summary['no_renter'] > 0 ? "<span class='error'>{$summary['no_renter']}</span>" : "0") . "</strong></li>";
echo "<li>Refunds with missing owner: <strong>" . ($summary['no_owner'] > 0 ? "<span class='warning'>{$summary['no_owner']}</span>" : "0") . "</strong></li>";
echo "</ul></div>";

if ($summary['orphaned'] > 0 || $summary['no_renter'] > 0) {
    echo "<div class='danger-box'>";
    echo "<strong>⚠️ ACTION REQUIRED:</strong><br>";
    echo "Some refund records are not being displayed in refunds.php due to missing related data (bookings or users).<br>";
    echo "These records should be investigated and potentially cleaned up.";
    echo "</div>";
} else {
    echo "<div class='info-box'>";
    echo "<strong>✅ All refunds should be visible in refunds.php</strong><br>";
    echo "No data integrity issues detected.";
    echo "</div>";
}

echo "</div></body></html>";

mysqli_close($conn);
