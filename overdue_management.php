<?php
/**
 * ============================================================================
 * OVERDUE RENTAL MANAGEMENT - CarGo Admin
 * Manage overdue rentals with late fees and automated tracking
 * ============================================================================
 */

session_start();
require_once 'include/db.php';

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/* =========================================================
   OVERDUE STATISTICS WITH ERROR HANDLING
   ========================================================= */

// Total overdue bookings
$totalOverdueQuery = "SELECT COUNT(*) as total_overdue
    FROM bookings
    WHERE status = 'approved'
    AND CONCAT(return_date, ' ', return_time) < NOW()";
$totalResult = mysqli_query($conn, $totalOverdueQuery);
if (!$totalResult) {
    error_log("Total overdue query error: " . mysqli_error($conn));
    $stats['total_overdue'] = 0;
} else {
    $stats['total_overdue'] = mysqli_fetch_assoc($totalResult)['total_overdue'];
}

// Overdue count (< 2 days)
$overdueQuery = "SELECT COUNT(*) as overdue_count
    FROM bookings
    WHERE status = 'approved'
    AND overdue_status = 'overdue'
    AND CONCAT(return_date, ' ', return_time) < NOW()";
$overdueResult = mysqli_query($conn, $overdueQuery);
if (!$overdueResult) {
    error_log("Overdue count query error: " . mysqli_error($conn));
    $stats['overdue_count'] = 0;
} else {
    $stats['overdue_count'] = mysqli_fetch_assoc($overdueResult)['overdue_count'];
}

// Severely overdue count (2+ days)
$severeQuery = "SELECT COUNT(*) as severe_count
    FROM bookings
    WHERE status = 'approved'
    AND overdue_status = 'severely_overdue'
    AND CONCAT(return_date, ' ', return_time) < NOW()";
$severeResult = mysqli_query($conn, $severeQuery);
if (!$severeResult) {
    error_log("Severe overdue query error: " . mysqli_error($conn));
    $stats['severe_count'] = 0;
} else {
    $stats['severe_count'] = mysqli_fetch_assoc($severeResult)['severe_count'];
}

// Total late fees
$lateFeeQuery = "SELECT COALESCE(SUM(late_fee_amount), 0) as total_late_fees
    FROM bookings
    WHERE status = 'approved'
    AND CONCAT(return_date, ' ', return_time) < NOW()";
$lateFeeResult = mysqli_query($conn, $lateFeeQuery);
if (!$lateFeeResult) {
    error_log("Late fee query error: " . mysqli_error($conn));
    $stats['total_late_fees'] = 0;
} else {
    $stats['total_late_fees'] = mysqli_fetch_assoc($lateFeeResult)['total_late_fees'];
}

// Collected fees
$collectedQuery = "SELECT COALESCE(SUM(late_fee_amount), 0) as collected_fees
    FROM bookings
    WHERE late_fee_charged = 1";
$collectedResult = mysqli_query($conn, $collectedQuery);
if (!$collectedResult) {
    error_log("Collected fees query error: " . mysqli_error($conn));
    $stats['collected_fees'] = 0;
} else {
    $stats['collected_fees'] = mysqli_fetch_assoc($collectedResult)['collected_fees'];
}

/* =========================================================
   FILTERS & PAGINATION
   ========================================================= */
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

$severityFilter = isset($_GET['severity']) ? trim($_GET['severity']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* =========================================================
   BUILD WHERE CLAUSE
   ========================================================= */
$where = " WHERE b.status = 'approved' AND CONCAT(b.return_date, ' ', b.return_time) < NOW() ";

// Severity filter
switch($severityFilter) {
    case 'overdue':
        $where .= " AND b.overdue_status = 'overdue' ";
        break;
    case 'severely_overdue':
        $where .= " AND b.overdue_status = 'severely_overdue' ";
        break;
    case 'all':
    default:
        // Show all overdue
        break;
}

// Search filter
if (!empty($search)) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (
        b.id LIKE '%$searchEsc%' OR
        u_renter.fullname LIKE '%$searchEsc%' OR
        u_owner.fullname LIKE '%$searchEsc%' OR
        COALESCE(c.brand, m.brand) LIKE '%$searchEsc%'
    )";
}

/* =========================================================
   MAIN QUERY - OVERDUE BOOKINGS
   ========================================================= */
$sql = "
    SELECT 
        b.id AS booking_id,
        b.user_id AS renter_id,
        b.owner_id,
        b.car_id,
        b.vehicle_type,
        b.total_amount,
        b.pickup_date,
        b.pickup_time,
        b.return_date,
        b.return_time,
        b.status,
        b.overdue_status,
        b.late_fee_amount,
        b.late_fee_charged,
        b.created_at,
        
        -- Calculate overdue duration
        TIMESTAMPDIFF(HOUR, 
            CONCAT(b.return_date, ' ', b.return_time), 
            NOW()
        ) AS hours_overdue,
        
        FLOOR(TIMESTAMPDIFF(HOUR, 
            CONCAT(b.return_date, ' ', b.return_time), 
            NOW()
        ) / 24) AS days_overdue,
        
        -- Renter info
        u_renter.fullname AS renter_name,
        u_renter.email AS renter_email,
        u_renter.phone AS renter_phone,
        
        -- Owner info
        u_owner.fullname AS owner_name,
        u_owner.email AS owner_email,
        u_owner.phone AS owner_phone,
        
        -- Vehicle info (supports both cars and motorcycles)
        COALESCE(c.brand, m.brand) AS brand,
        COALESCE(c.model, m.model) AS model,
        COALESCE(c.car_year, m.motorcycle_year) AS vehicle_year,
        COALESCE(c.plate_number, m.plate_number) AS plate_number,
        COALESCE(c.image, m.image) AS vehicle_image,
        CONCAT(COALESCE(c.brand, m.brand), ' ', COALESCE(c.model, m.model)) AS vehicle_name
        
    FROM bookings b
    JOIN users u_renter ON b.user_id = u_renter.id
    JOIN users u_owner ON b.owner_id = u_owner.id
    LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
    LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
    $where
    ORDER BY 
        CASE 
            WHEN b.overdue_status = 'severely_overdue' THEN 1
            WHEN b.overdue_status = 'overdue' THEN 2
            ELSE 3
        END,
        b.return_date ASC, b.return_time ASC
    LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Main query SQL ERROR: " . mysqli_error($conn) . "<br><br>Query: " . $sql);
}

/* =========================================================
   COUNT FOR PAGINATION
   ========================================================= */
$countSql = "SELECT COUNT(*) AS total 
FROM bookings b 
JOIN users u_renter ON b.user_id = u_renter.id
LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
$where";

$countResult = mysqli_query($conn, $countSql);
if (!$countResult) {
    die("Count query SQL ERROR: " . mysqli_error($conn));
}
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overdue Rental Management | CarGo Admin</title>
  
  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Custom Styles -->
  <link rel="stylesheet" href="include/admin-styles.css">
  
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-content {
      padding: 30px;
      margin-left: 260px;
    }
    
    /* Header */
    .page-header {
      background: white;
      padding: 25px 30px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    /* Statistics Cards */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      position: relative;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-card.warning::before { background: linear-gradient(180deg, #f6ad55 0%, #ed8936 100%); }
    .stat-card.danger::before { background: linear-gradient(180deg, #fc8181 0%, #e53e3e 100%); }
    .stat-card.success::before { background: linear-gradient(180deg, #68d391 0%, #38a169 100%); }
    
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 15px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .stat-card.warning .stat-icon { background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%); }
    .stat-card.danger .stat-icon { background: linear-gradient(135deg, #fc8181 0%, #e53e3e 100%); }
    .stat-card.success .stat-icon { background: linear-gradient(135deg, #68d391 0%, #38a169 100%); }
    
    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #2c3e50;
      margin: 5px 0;
    }
    
    .stat-label {
      font-size: 13px;
      color: #718096;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Filter Section */
    .filter-section {
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 25px;
    }
    
    .filter-tabs {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    
    .filter-tab {
      padding: 10px 20px;
      border: 2px solid #e2e8f0;
      background: white;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      color: #4a5568;
      transition: all 0.3s ease;
      text-decoration: none;
    }
    
    .filter-tab:hover {
      background: #f7fafc;
      border-color: #cbd5e0;
    }
    
    .filter-tab.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-color: #667eea;
    }
    
    .search-export-row {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .search-box {
      flex: 1;
      position: relative;
    }
    
    .search-box input {
      width: 100%;
      padding: 12px 45px 12px 15px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .search-box i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #a0aec0;
    }
    
    .btn-export {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 10px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      cursor: pointer;
    }
    
    .btn-export:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    /* Table */
    .table-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .table {
      margin: 0;
    }
    
    .table thead th {
      background: #f7fafc;
      color: #2d3748;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 18px 15px;
      border: none;
    }
    
    .table tbody td {
      padding: 18px 15px;
      vertical-align: middle;
      border-bottom: 1px solid #e2e8f0;
      font-size: 14px;
    }
    
    .table tbody tr:last-child td {
      border-bottom: none;
    }
    
    .table tbody tr:hover {
      background: #f7fafc;
    }
    
    /* Badges */
    .badge {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    
    .badge-warning {
      background: #fef3c7;
      color: #92400e;
    }
    
    .badge-danger {
      background: #fee2e2;
      color: #991b1b;
    }
    
    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    
    .btn-sm {
      padding: 8px 14px;
      font-size: 13px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .btn-sm:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-info { background: #3b82f6; color: white; }
    .btn-primary { background: #667eea; color: white; }
    .btn-success { background: #10b981; color: white; }
    
    /* Vehicle Info */
    .vehicle-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .vehicle-image {
      width: 50px;
      height: 50px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px solid #e2e8f0;
    }
    
    .vehicle-details {
      flex: 1;
    }
    
    .vehicle-name {
      font-weight: 600;
      color: #2d3748;
      margin-bottom: 3px;
    }
    
    .vehicle-plate {
      font-size: 12px;
      color: #718096;
    }
    
    /* User Info */
    .user-info {
      font-size: 14px;
    }
    
    .user-name {
      font-weight: 600;
      color: #2d3748;
      display: block;
      margin-bottom: 3px;
    }
    
    .user-contact {
      font-size: 12px;
      color: #718096;
      display: block;
    }
    
    /* Pagination */
    .pagination-wrapper {
      background: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-top: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .pagination {
      margin: 0;
    }
    
    .page-link {
      color: #667eea;
      border: 2px solid #e2e8f0;
      padding: 8px 14px;
      margin: 0 3px;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .page-link:hover {
      background: #667eea;
      color: white;
      border-color: #667eea;
    }
    
    .page-item.active .page-link {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-color: #667eea;
    }
    
    .page-item.disabled .page-link {
      background: #f7fafc;
      border-color: #e2e8f0;
      color: #cbd5e0;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #718096;
    }
    
    .empty-state i {
      font-size: 64px;
      color: #cbd5e0;
      margin-bottom: 20px;
    }
    
    .empty-state h3 {
      color: #2d3748;
      margin-bottom: 10px;
    }
    
    /* Modal Enhancements */
    .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    
    .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px 15px 0 0;
      padding: 20px 25px;
    }
    
    .modal-header .btn-close {
      filter: brightness(0) invert(1);
    }
    
    .modal-body {
      padding: 25px;
    }
    
    .modal-footer {
      padding: 20px 25px;
      border-top: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>

<?php include 'include/sidebar.php'; ?>

<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h1>
      <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b;"></i>
      Overdue Rental Management
    </h1>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-container">
    <div class="stat-card warning">
      <div class="stat-icon">
        <i class="bi bi-clock-history"></i>
      </div>
      <div class="stat-label">Total Overdue</div>
      <div class="stat-value"><?php echo number_format($stats['total_overdue']); ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="stat-label">Overdue (< 2 days)</div>
      <div class="stat-value"><?php echo number_format($stats['overdue_count']); ?></div>
    </div>
    
    <div class="stat-card danger">
      <div class="stat-icon">
        <i class="bi bi-exclamation-circle"></i>
      </div>
      <div class="stat-label">Severely Overdue</div>
      <div class="stat-value"><?php echo number_format($stats['severe_count']); ?></div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">
        <i class="bi bi-cash-stack"></i>
      </div>
      <div class="stat-label">Total Late Fees</div>
      <div class="stat-value">₱<?php echo number_format($stats['total_late_fees'], 2); ?></div>
    </div>
    
    <div class="stat-card success">
      <div class="stat-icon">
        <i class="bi bi-check-circle"></i>
      </div>
      <div class="stat-label">Collected Fees</div>
      <div class="stat-value">₱<?php echo number_format($stats['collected_fees'], 2); ?></div>
    </div>
  </div>

  <!-- Filter Section -->
  <div class="filter-section">
    <div class="filter-tabs">
      <a href="?severity=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
         class="filter-tab <?php echo $severityFilter === 'all' ? 'active' : ''; ?>">
        <i class="bi bi-list-ul"></i> All Overdue
      </a>
      <a href="?severity=overdue<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
         class="filter-tab <?php echo $severityFilter === 'overdue' ? 'active' : ''; ?>">
        <i class="bi bi-clock"></i> Overdue (< 2 days)
      </a>
      <a href="?severity=severely_overdue<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
         class="filter-tab <?php echo $severityFilter === 'severely_overdue' ? 'active' : ''; ?>">
        <i class="bi bi-exclamation-triangle"></i> Severe (2+ days)
      </a>
    </div>

    <form method="GET" action="" class="search-export-row">
      <input type="hidden" name="severity" value="<?php echo htmlspecialchars($severityFilter); ?>">
      <div class="search-box">
        <input 
          type="text" 
          name="search" 
          id="searchInput" 
          placeholder="Search by booking ID, vehicle, renter, or owner..." 
          value="<?php echo htmlspecialchars($search); ?>"
        >
        <i class="bi bi-search"></i>
      </div>
      <button type="button" class="btn-export" onclick="exportOverdue()">
        <i class="bi bi-file-earmark-spreadsheet"></i>
        Export CSV
      </button>
    </form>
  </div>

  <!-- Overdue Bookings Table -->
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Vehicle</th>
          <th>Renter</th>
          <th>Owner</th>
          <th>Return Due</th>
          <th>Overdue Duration</th>
          <th>Late Fee</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
              $vehicleImg = !empty($row['vehicle_image']) ? $row['vehicle_image'] : 'default-vehicle.png';
              $statusClass = $row['overdue_status'] === 'severely_overdue' ? 'danger' : 'warning';
              $statusText = $row['overdue_status'] === 'severely_overdue' ? 'SEVERELY OVERDUE' : 'OVERDUE';
            ?>
            <tr>
              <td><strong>#<?php echo $row['booking_id']; ?></strong></td>
              <td>
                <div class="vehicle-info">
                  <img src="<?php echo htmlspecialchars($vehicleImg); ?>" alt="Vehicle" class="vehicle-image">
                  <div class="vehicle-details">
                    <div class="vehicle-name"><?php echo htmlspecialchars($row['vehicle_name']); ?></div>
                    <div class="vehicle-plate"><?php echo htmlspecialchars($row['plate_number']); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-info">
                  <span class="user-name"><?php echo htmlspecialchars($row['renter_name']); ?></span>
                  <span class="user-contact"><?php echo htmlspecialchars($row['renter_phone']); ?></span>
                </div>
              </td>
              <td>
                <div class="user-info">
                  <span class="user-name"><?php echo htmlspecialchars($row['owner_name']); ?></span>
                  <span class="user-contact"><?php echo htmlspecialchars($row['owner_phone']); ?></span>
                </div>
              </td>
              <td>
                <strong><?php echo date('M d, Y', strtotime($row['return_date'])); ?></strong><br>
                <small class="text-muted"><?php echo date('h:i A', strtotime($row['return_time'])); ?></small>
              </td>
              <td>
                <strong style="color: #e53e3e;"><?php echo $row['days_overdue']; ?> days</strong><br>
                <small class="text-muted">(<?php echo $row['hours_overdue']; ?> hrs)</small>
              </td>
              <td>
                <strong style="color: #2d3748;">₱<?php echo number_format($row['late_fee_amount'], 2); ?></strong><br>
                <?php if ($row['late_fee_charged']): ?>
                  <span class="badge badge-success" style="font-size: 10px;">Charged</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size: 10px;">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?php echo $statusClass; ?>">
                  <i class="bi bi-exclamation-circle"></i>
                  <?php echo $statusText; ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-info" onclick="viewDetails(<?php echo $row['booking_id']; ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                  <button class="btn btn-sm btn-primary" onclick="contactRenter(<?php echo $row['booking_id']; ?>)">
                    <i class="bi bi-telephone"></i> Contact
                  </button>
                  <button class="btn btn-sm btn-success" onclick="forceComplete(<?php echo $row['booking_id']; ?>)">
                    <i class="bi bi-check-lg"></i> Complete
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="9">
              <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>No Overdue Bookings</h3>
                <p>All rentals are on time or have been completed.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination-wrapper">
    <div class="pagination-info">
      Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalRecords); ?> 
      of <?php echo number_format($totalRecords); ?> entries
    </div>
    
    <nav>
      <ul class="pagination">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $page - 1; ?>&severity=<?php echo $severityFilter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
        
        <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          
          for ($i = $startPage; $i <= $endPage; $i++):
        ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&severity=<?php echo $severityFilter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>
        
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $page + 1; ?>&severity=<?php echo $severityFilter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-info-circle"></i> Overdue Booking Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailsModalBody">
        <!-- Loaded via JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Contact Renter Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-telephone"></i> Contact Renter
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="contactModalBody">
        <!-- Loaded via JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// View booking details
function viewDetails(bookingId) {
  fetch(`api/overdue/get_booking_details.php?id=${bookingId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const booking = data.data;
        document.getElementById('detailsModalBody').innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6><i class="bi bi-calendar"></i> Booking Information</h6>
              <table class="table table-sm">
                <tr><td><strong>Booking ID:</strong></td><td>#${booking.booking_id}</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${booking.overdue_status === 'severely_overdue' ? 'danger' : 'warning'}">${booking.overdue_status.toUpperCase()}</span></td></tr>
                <tr><td><strong>Pickup:</strong></td><td>${booking.pickup_date} ${booking.pickup_time}</td></tr>
                <tr><td><strong>Return Due:</strong></td><td>${booking.return_date} ${booking.return_time}</td></tr>
                <tr><td><strong>Days Overdue:</strong></td><td><strong style="color: #e53e3e;">${booking.days_overdue} days</strong></td></tr>
                <tr><td><strong>Hours Overdue:</strong></td><td>${booking.hours_overdue} hours</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6><i class="bi bi-cash"></i> Financial Information</h6>
              <table class="table table-sm">
                <tr><td><strong>Total Amount:</strong></td><td>₱${Number(booking.total_amount).toFixed(2)}</td></tr>
                <tr><td><strong>Late Fee:</strong></td><td><strong>₱${Number(booking.late_fee_amount).toFixed(2)}</strong></td></tr>
                <tr><td><strong>Fee Status:</strong></td><td>${booking.late_fee_charged ? '<span class="badge badge-success">Charged</span>' : '<span class="badge badge-warning">Pending</span>'}</td></tr>
              </table>
            </div>
          </div>
          <hr>
          <div class="row">
            <div class="col-md-6">
              <h6><i class="bi bi-person"></i> Renter Details</h6>
              <p>
                <strong>${booking.renter_name}</strong><br>
                <i class="bi bi-envelope"></i> ${booking.renter_email}<br>
                <i class="bi bi-telephone"></i> ${booking.renter_phone}
              </p>
            </div>
            <div class="col-md-6">
              <h6><i class="bi bi-person-badge"></i> Owner Details</h6>
              <p>
                <strong>${booking.owner_name}</strong><br>
                <i class="bi bi-envelope"></i> ${booking.owner_email}<br>
                <i class="bi bi-telephone"></i> ${booking.owner_phone}
              </p>
            </div>
          </div>
          <hr>
          <h6><i class="bi bi-car-front"></i> Vehicle Information</h6>
          <p>
            <strong>${booking.vehicle_name}</strong><br>
            Plate Number: ${booking.plate_number}<br>
            Year: ${booking.vehicle_year}
          </p>
        `;
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
      } else {
        alert('Error loading booking details');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Network error occurred');
    });
}

// Contact renter
function contactRenter(bookingId) {
  fetch(`api/overdue/get_booking_details.php?id=${bookingId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const booking = data.data;
        document.getElementById('contactModalBody').innerHTML = `
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Contact the renter about their overdue booking.
          </div>
          <h6>Renter Contact Information</h6>
          <p>
            <strong>Name:</strong> ${booking.renter_name}<br>
            <strong>Email:</strong> <a href="mailto:${booking.renter_email}">${booking.renter_email}</a><br>
            <strong>Phone:</strong> <a href="tel:${booking.renter_phone}">${booking.renter_phone}</a>
          </p>
          <h6>Booking Details</h6>
          <p>
            <strong>Booking ID:</strong> #${booking.booking_id}<br>
            <strong>Vehicle:</strong> ${booking.vehicle_name}<br>
            <strong>Days Overdue:</strong> <strong style="color: #e53e3e;">${booking.days_overdue} days</strong><br>
            <strong>Late Fee:</strong> ₱${Number(booking.late_fee_amount).toFixed(2)}
          </p>
          <div class="d-grid gap-2">
            <a href="mailto:${booking.renter_email}?subject=Overdue Booking #${booking.booking_id}" class="btn btn-primary">
              <i class="bi bi-envelope"></i> Send Email
            </a>
            <a href="tel:${booking.renter_phone}" class="btn btn-success">
              <i class="bi bi-telephone"></i> Call Now
            </a>
          </div>
        `;
        new bootstrap.Modal(document.getElementById('contactModal')).show();
      } else {
        alert('Error loading renter information');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Network error occurred');
    });
}

// Force complete booking
function forceComplete(bookingId) {
  if (!confirm('Force complete this overdue booking? Late fees will be charged.')) return;
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  
  fetch('api/overdue/force_complete.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Network error occurred');
  });
}

// Export to CSV
function exportOverdue() {
  const params = new URLSearchParams(window.location.search);
  window.location.href = 'api/overdue/export_overdue.php?' + params.toString();
}

// Live search with debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('keyup', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    this.form.submit();
  }, 500);
});
</script>

<script src="include/notifications.js"></script>
</body>
</html>

<?php
$conn->close();
?>