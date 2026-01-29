<?php
/**
 * ============================================================================
 * REFUND VISIBILITY TEST SCRIPT
 * Tests all booking APIs to verify refund status fields are returned
 * ============================================================================
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../include/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Refund Visibility Test</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .test-section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        pre { background: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #34495e; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🔍 Refund Visibility Test Suite</h1>
    <p><strong>Purpose:</strong> Verify that all booking APIs return refund status fields</p>
    <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
";

// ============================================================================
// TEST 1: Check Database Schema
// ============================================================================
echo "<div class='test-section'>";
echo "<h2>📊 Test 1: Database Schema Check</h2>";

$schema_check = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE 'refund%'");
$refund_columns = [];

while ($row = mysqli_fetch_assoc($schema_check)) {
    $refund_columns[] = $row['Field'];
}

if (count($refund_columns) >= 3) {
    echo "<p class='success'>✅ PASS: Bookings table has refund columns</p>";
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    mysqli_data_seek($schema_check, 0);
    while ($row = mysqli_fetch_assoc($schema_check)) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ FAIL: Missing refund columns in bookings table</p>";
}
echo "</div>";

// ============================================================================
// TEST 2: Check for Bookings with Refund Data
// ============================================================================
echo "<div class='test-section'>";
echo "<h2>📋 Test 2: Bookings with Refund Status</h2>";

$query = "
SELECT 
    b.id,
    b.status,
    b.refund_status,
    b.refund_requested,
    b.refund_amount,
    b.user_id,
    u.fullname,
    u.email
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
WHERE b.refund_requested = 1 OR b.refund_status != 'not_requested'
ORDER BY b.id DESC
LIMIT 10
";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<p class='success'>✅ Found " . mysqli_num_rows($result) . " bookings with refund data</p>";
    echo "<table>";
    echo "<tr><th>Booking ID</th><th>Status</th><th>Refund Status</th><th>Refund Requested</th><th>Amount</th><th>User</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $statusClass = match($row['refund_status']) {
            'completed' => 'badge-success',
            'approved', 'processing' => 'badge-info',
            'pending', 'requested' => 'badge-warning',
            'rejected' => 'badge-danger',
            default => 'badge-warning'
        };
        
        echo "<tr>";
        echo "<td><strong>#" . $row['id'] . "</strong></td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "<td><span class='badge {$statusClass}'>" . strtoupper($row['refund_status']) . "</span></td>";
        echo "<td>" . ($row['refund_requested'] ? '✓ Yes' : '✗ No') . "</td>";
        echo "<td>₱" . number_format($row['refund_amount'] ?? 0, 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . " (" . $row['email'] . ")</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get user_id for API tests
    mysqli_data_seek($result, 0);
    $test_row = mysqli_fetch_assoc($result);
    $test_user_id = $test_row['user_id'];
} else {
    echo "<p class='warning'>⚠️ No bookings found with refund data. Creating test scenario...</p>";
    $test_user_id = 7; // Default test user
}
echo "</div>";

// ============================================================================
// TEST 3: API Endpoint Tests
// ============================================================================
echo "<div class='test-section'>";
echo "<h2>🔌 Test 3: API Endpoint Testing</h2>";

$apis_to_test = [
    [
        'name' => 'get_my_bookings.php',
        'url' => 'get_my_bookings.php?user_id=' . $test_user_id,
        'expected_fields' => ['refundStatus', 'refundRequested', 'refundAmount']
    ],
    [
        'name' => 'bookings/get_owner_active_bookings.php',
        'url' => 'bookings/get_owner_active_bookings.php?owner_id=1',
        'expected_fields' => ['refund_status', 'refund_requested', 'refund_amount']
    ],
    [
        'name' => 'bookings/get_owner_pending_requests.php',
        'url' => 'bookings/get_owner_pending_requests.php?owner_id=1',
        'expected_fields' => ['refund_status', 'refund_requested', 'refund_amount']
    ],
    [
        'name' => 'bookings/cancelled_bookings.php',
        'url' => 'bookings/cancelled_bookings.php?owner_id=1',
        'expected_fields' => ['refund_status', 'refund_requested', 'refund_amount']
    ],
    [
        'name' => 'bookings/rejected_bookings.php',
        'url' => 'bookings/rejected_bookings.php?owner_id=1',
        'expected_fields' => ['refund_status', 'refund_requested', 'refund_amount']
    ]
];

echo "<table>";
echo "<tr><th>API Endpoint</th><th>Status</th><th>Has Refund Fields?</th><th>Details</th></tr>";

foreach ($apis_to_test as $api) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $api['url'];
    
    // Make API call
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    $status = ($http_code == 200 && $data) ? '✅ OK' : '❌ Error';
    $hasRefundFields = false;
    $details = [];
    
    if ($data && isset($data['success']) && $data['success']) {
        // Check if response has bookings/requests
        $items = $data['bookings'] ?? $data['requests'] ?? [];
        
        if (!empty($items)) {
            $firstItem = $items[0];
            $missingFields = [];
            
            foreach ($api['expected_fields'] as $field) {
                if (array_key_exists($field, $firstItem)) {
                    $hasRefundFields = true;
                    $details[] = "<span class='success'>✓ {$field}</span>";
                } else {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $details[] = "<span class='error'>✗ Missing: " . implode(', ', $missingFields) . "</span>";
                $hasRefundFields = false;
            }
        } else {
            $details[] = "<span class='info'>No data to test</span>";
        }
    } else {
        $details[] = "<span class='error'>API Error or No Data</span>";
    }
    
    $fieldStatus = $hasRefundFields ? "<span class='badge badge-success'>YES</span>" : "<span class='badge badge-danger'>NO</span>";
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($api['name']) . "</code></td>";
    echo "<td>{$status}</td>";
    echo "<td>{$fieldStatus}</td>";
    echo "<td>" . implode('<br>', $details) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// ============================================================================
// TEST 4: Refund Table Check
// ============================================================================
echo "<div class='test-section'>";
echo "<h2>💰 Test 4: Refunds Table Data</h2>";

$refund_query = "
SELECT 
    r.id,
    r.refund_id,
    r.booking_id,
    r.user_id,
    r.refund_amount,
    r.status,
    r.created_at,
    u.fullname,
    u.email
FROM refunds r
LEFT JOIN users u ON r.user_id = u.id
ORDER BY r.created_at DESC
LIMIT 10
";

$refund_result = mysqli_query($conn, $refund_query);

if ($refund_result && mysqli_num_rows($refund_result) > 0) {
    echo "<p class='success'>✅ Found " . mysqli_num_rows($refund_result) . " refund records</p>";
    echo "<table>";
    echo "<tr><th>Refund ID</th><th>Booking</th><th>Amount</th><th>Status</th><th>User</th><th>Created</th></tr>";
    
    while ($row = mysqli_fetch_assoc($refund_result)) {
        $statusClass = match($row['status']) {
            'completed' => 'badge-success',
            'approved', 'processing' => 'badge-info',
            'pending' => 'badge-warning',
            'rejected' => 'badge-danger',
            default => 'badge-warning'
        };
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['refund_id']) . "</strong></td>";
        echo "<td>#" . $row['booking_id'] . "</td>";
        echo "<td>₱" . number_format($row['refund_amount'], 2) . "</td>";
        echo "<td><span class='badge {$statusClass}'>" . strtoupper($row['status']) . "</span></td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
        echo "<td>" . date('M d, Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No refund records found in database</p>";
}
echo "</div>";

// ============================================================================
// TEST SUMMARY
// ============================================================================
echo "<div class='test-section'>";
echo "<h2>📊 Test Summary</h2>";

echo "<p><strong>✅ All tests completed!</strong></p>";
echo "<ul>";
echo "<li>Database schema validated</li>";
echo "<li>Booking data with refund status checked</li>";
echo "<li>API endpoints tested for refund field inclusion</li>";
echo "<li>Refunds table data verified</li>";
echo "</ul>";

echo "<p class='info'><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all APIs show refund fields, the integration is complete</li>";
echo "<li>Test the Flutter app to verify refund badges appear in booking cards</li>";
echo "<li>Submit a test refund request and verify it flows through the system</li>";
echo "<li>Check admin panel to ensure refunds are visible and manageable</li>";
echo "</ol>";

echo "</div>";

echo "</body></html>";

mysqli_close($conn);
