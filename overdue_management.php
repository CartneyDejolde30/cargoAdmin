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
   PAYMENT STATUS COUNTS
   ========================================================= */
// Unpaid count
$unpaidQuery = "SELECT COUNT(*) as unpaid_count
    FROM bookings
    WHERE status = 'approved'
    AND CONCAT(return_date, ' ', return_time) < NOW()
    AND late_fee_charged = 0";
$unpaidResult = mysqli_query($conn, $unpaidQuery);
$stats['unpaid_count'] = $unpaidResult ? mysqli_fetch_assoc($unpaidResult)['unpaid_count'] : 0;

// Awaiting verification count
$awaitingQuery = "SELECT COUNT(*) as awaiting_count
    FROM bookings
    WHERE status = 'approved'
    AND CONCAT(return_date, ' ', return_time) < NOW()
    AND late_fee_charged = 0
    AND payment_status = 'pending'";
$awaitingResult = mysqli_query($conn, $awaitingQuery);
$stats['awaiting_count'] = $awaitingResult ? mysqli_fetch_assoc($awaitingResult)['awaiting_count'] : 0;

// Paid count
$paidQuery = "SELECT COUNT(*) as paid_count
    FROM bookings
    WHERE status = 'approved'
    AND CONCAT(return_date, ' ', return_time) < NOW()
    AND late_fee_charged = 1";
$paidResult = mysqli_query($conn, $paidQuery);
$stats['paid_count'] = $paidResult ? mysqli_fetch_assoc($paidResult)['paid_count'] : 0;

/* =========================================================
   FILTERS & PAGINATION
   ========================================================= */
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

$severityFilter = isset($_GET['severity']) ? trim($_GET['severity']) : 'all';
$paymentFilter = isset($_GET['payment']) ? trim($_GET['payment']) : 'all';
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

// Payment status filter
switch($paymentFilter) {
    case 'paid':
        $where .= " AND b.late_fee_charged = 1 ";
        break;
    case 'unpaid':
        $where .= " AND b.late_fee_charged = 0 ";
        break;
    case 'pending_verification':
        $where .= " AND b.late_fee_charged = 0 AND b.payment_status = 'pending' ";
        break;
    case 'all':
    default:
        // Show all
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
        b.payment_status,
        b.overdue_status,
        b.late_fee_amount,
        b.late_fee_charged,
        b.reminder_count,
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
JOIN users u_owner ON b.owner_id = u_owner.id
LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
$where";

$countResult = mysqli_query($conn, $countSql);
if (!$countResult) {
    die("Count query SQL ERROR: " . mysqli_error($conn));
}
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = max(1, ceil($totalRecords / $limit));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overdue Management - CarGo Admin</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <link href="include/notifications.css" rel="stylesheet">
  
  <style>
    /* Overdue-specific enhancements */
    .stat-card.overdue-warning::before {
      background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-card.overdue-danger::before {
      background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
    }
    
    .stat-icon.warning-icon {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .stat-icon.danger-icon {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    .stat-icon.success-icon {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .filter-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .filter-tab {
      padding: 10px 18px;
      background: #f5f5f5;
      border: none;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      color: #666;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .filter-tab:hover {
      background: #e8e8e8;
      color: #1a1a1a;
    }
    
    .filter-tab.active {
      background: #1a1a1a;
      color: white;
    }
    
    .filter-count {
      background: rgba(255, 255, 255, 0.2);
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 700;
      margin-left: 6px;
    }
    
    .filter-tab.active .filter-count {
      background: rgba(255, 255, 255, 0.25);
      color: white;
    }
    
    .overdue-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .overdue-badge.warning {
      background: #fff8e1;
      color: #f57c00;
    }
    
    .overdue-badge.danger {
      background: #ffebee;
      color: #d32f2f;
    }
    
    .fee-badge {
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      display: inline-block;
    }
    
    .fee-badge.charged {
      background: #e8f8f0;
      color: #009944;
      font-weight: 700;
    }
    
    .fee-badge.pending {
      background: #fff8e1;
      color: #f57c00;
      font-weight: 700;
    }
    
    .overdue-duration {
      font-weight: 700;
      color: #d32f2f;
      font-size: 14px;
    }
    
    .overdue-hours {
      font-size: 12px;
      color: #999;
    }
    
    .late-fee-amount {
      font-weight: 700;
      color: #1a1a1a;
      font-size: 16px;
    }
    
    /* Vehicle image override - smaller size */
    .vehicle-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .vehicle-image {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px solid #f0f0f0;
    }
    
    .vehicle-details .vehicle-name {
      font-size: 13px;
    }
    
    .vehicle-details .vehicle-plate {
      font-size: 11px;
    }
    
    /* User avatar override - smaller size */
    .user-avatar-small {
      width: 38px;
      height: 38px;
    }
    
    .user-avatar-small img {
      width: 100%;
      height: 100%;
    }
    
    /* User info text adjustments */
    .user-info .user-name {
      font-size: 13px;
    }
    
    .user-info .user-email {
      font-size: 11px;
    }
    
    .btn-view {
      background: #e3f2fd;
      color: #1976d2;
    }
    
    .btn-view:hover {
      background: #1976d2;
      color: white;
    }
    
    .btn-contact {
      background: #f3e5f5;
      color: #7b1fa2;
    }
    
    .btn-contact:hover {
      background: #7b1fa2;
      color: white;
    }
    
    .btn-complete {
      background: #e8f8f0;
      color: #009944;
    }
    
    .btn-complete:hover {
      background: #009944;
      color: white;
    }
    
    .contact-action-btn {
      width: 100%;
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s ease;
      text-decoration: none;
      cursor: pointer;
      margin-bottom: 10px;
    }
    
    .contact-action-btn.email {
      background: #e3f2fd;
      color: #1976d2;
    }
    
    .contact-action-btn.email:hover {
      background: #1976d2;
      color: white;
      transform: translateY(-2px);
    }
    
    .contact-action-btn.call {
      background: #e8f8f0;
      color: #009944;
    }
    
    .contact-action-btn.call:hover {
      background: #009944;
      color: white;
      transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
      .filter-tabs {
        flex-direction: column;
      }
      
      .filter-tab {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include 'include/sidebar.php'; ?>

  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-exclamation-triangle"></i>
        Overdue Rental Management
      </h1>
      <div class="user-profile">
        <div class="notification-dropdown">
          <button class="notification-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notification-badge"></span>
          </button>
        </div>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card overdue-warning">
        <div class="stat-header">
          <div class="stat-icon warning-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-exclamation-circle"></i>
            Active
          </div>
        </div>
        <div class="stat-value"><?= number_format($stats['total_overdue']) ?></div>
        <div class="stat-label">Total Overdue</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Recent
          </div>
        </div>
        <div class="stat-value"><?= number_format($stats['overdue_count']) ?></div>
        <div class="stat-label">Overdue (< 2 days)</div>
      </div>

      <div class="stat-card overdue-danger">
        <div class="stat-header">
          <div class="stat-icon danger-icon">
            <i class="bi bi-exclamation-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-exclamation-triangle"></i>
            Critical
          </div>
        </div>
        <div class="stat-value"><?= number_format($stats['severe_count']) ?></div>
        <div class="stat-label">Severely Overdue (2+ days)</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-currency-dollar"></i>
            Pending
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($stats['total_late_fees'], 2) ?></div>
        <div class="stat-label">Total Late Fees</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon success-icon">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Collected
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($stats['collected_fees'], 2) ?></div>
        <div class="stat-label">Collected Fees</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-tabs">
        <a href="?severity=all&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $severityFilter === 'all' ? 'active' : '' ?>">
          <i class="bi bi-list-ul"></i> All Overdue
        </a>
        <a href="?severity=overdue&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $severityFilter === 'overdue' ? 'active' : '' ?>">
          <i class="bi bi-clock"></i> Overdue (< 2 days)
        </a>
        <a href="?severity=severely_overdue&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $severityFilter === 'severely_overdue' ? 'active' : '' ?>">
          <i class="bi bi-exclamation-triangle"></i> Severe (2+ days)
        </a>
      </div>
      
      <!-- Payment Status Filter -->
      <div class="filter-tabs" style="margin-bottom: 15px;">
        <a href="?severity=<?= $severityFilter ?>&payment=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $paymentFilter === 'all' ? 'active' : '' ?>">
          <i class="bi bi-filter"></i> All Payments
          <span class="filter-count"><?= number_format($stats['total_overdue']) ?></span>
        </a>
        <a href="?severity=<?= $severityFilter ?>&payment=unpaid<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $paymentFilter === 'unpaid' ? 'active' : '' ?>">
          <i class="bi bi-x-circle"></i> Unpaid
          <span class="filter-count"><?= number_format($stats['unpaid_count']) ?></span>
        </a>
        <a href="?severity=<?= $severityFilter ?>&payment=pending_verification<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $paymentFilter === 'pending_verification' ? 'active' : '' ?>">
          <i class="bi bi-clock-history"></i> Awaiting Verification
          <span class="filter-count"><?= number_format($stats['awaiting_count']) ?></span>
        </a>
        <a href="?severity=<?= $severityFilter ?>&payment=paid<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
           class="filter-tab <?= $paymentFilter === 'paid' ? 'active' : '' ?>">
          <i class="bi bi-check-circle"></i> Paid
          <span class="filter-count"><?= number_format($stats['paid_count']) ?></span>
        </a>
      </div>

      <form method="GET" action="" class="filter-row">
        <input type="hidden" name="severity" value="<?= htmlspecialchars($severityFilter) ?>">
        <input type="hidden" name="payment" value="<?= htmlspecialchars($paymentFilter) ?>">
        <div class="search-box">
          <input 
            type="text" 
            name="search" 
            id="searchInput" 
            placeholder="Search by booking ID, vehicle, renter, or owner..." 
            value="<?= htmlspecialchars($search) ?>"
          >
          <i class="bi bi-search"></i>
        </div>
        <button type="button" class="add-user-btn" onclick="exportOverdue()">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          Export CSV
        </button>
      </form>
    </div>

    <!-- Overdue Bookings Table -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Overdue Bookings</h2>
      </div>

      <div class="table-responsive">
        <table>
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
                  <td>
                    <strong>#BK-<?= str_pad($row['booking_id'], 4, '0', STR_PAD_LEFT) ?></strong><br>
                    <small style="color:#999;"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                  </td>
                  <td>
                    <div class="vehicle-info">
                      <img src="<?= htmlspecialchars($vehicleImg) ?>" alt="Vehicle" class="vehicle-image">
                      <div class="vehicle-details">
                        <div class="vehicle-name"><?= htmlspecialchars($row['vehicle_name']) ?></div>
                        <div class="vehicle-plate"><?= htmlspecialchars($row['plate_number']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="user-cell">
                      <div class="user-avatar-small">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['renter_name']) ?>&background=1a1a1a&color=fff">
                      </div>
                      <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($row['renter_name']) ?></span>
                        <span class="user-email"><?= htmlspecialchars($row['renter_phone']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="user-cell">
                      <div class="user-avatar-small">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['owner_name']) ?>&background=1a1a1a&color=fff">
                      </div>
                      <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($row['owner_name']) ?></span>
                        <span class="user-email"><?= htmlspecialchars($row['owner_phone']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <strong><?= date('M d, Y', strtotime($row['return_date'])) ?></strong><br>
                    <small style="color:#999;"><?= date('h:i A', strtotime($row['return_time'])) ?></small>
                  </td>
                  <td>
                    <span class="overdue-duration"><?= $row['days_overdue'] ?> days</span><br>
                    <span class="overdue-hours">(<?= $row['hours_overdue'] ?> hrs)</span>
                  </td>
                  <td>
                    <span class="late-fee-amount">₱<?= number_format($row['late_fee_amount'], 2) ?></span><br>
                    <?php if ($row['late_fee_charged']): ?>
                      <span class="fee-badge charged">✓ Collected</span>
                    <?php else: ?>
                      <span class="fee-badge pending">⏳ Pending Payment</span>
                    <?php endif; ?>
                    <?php if (!$row['late_fee_charged'] && $row['payment_status'] === 'pending'): ?>
                      <br><small style="color: #f59e0b; font-size: 10px;">Payment submitted - awaiting verification</small>
                    <?php endif; ?>
                    <?php if (isset($row['reminder_count']) && $row['reminder_count'] > 0): ?>
                      <br><small style="color: #666; font-size: 10px;">🔔 Reminded <?= $row['reminder_count'] ?>x</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="overdue-badge <?= $statusClass ?>">
                      <i class="bi bi-exclamation-circle"></i>
                      <?= $statusText ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons" style="display: flex; gap: 4px; flex-wrap: wrap;">
                      <button class="action-btn btn-view" onclick="viewDetails(<?= $row['booking_id'] ?>)" title="View Details">
                        <i class="bi bi-eye"></i>
                      </button>
                      
                      <?php if (!$row['late_fee_charged']): ?>
                      <button class="action-btn" style="background: #e3f2fd; color: #1976d2;" 
                              onclick="confirmLateFee(<?= $row['booking_id'] ?>, <?= $row['late_fee_amount'] ?>)" 
                              title="Confirm Late Fee">
                        <i class="bi bi-check-circle"></i>
                      </button>
                      
                      <button class="action-btn" style="background: #fff3e0; color: #f57c00;" 
                              onclick="sendReminder(<?= $row['booking_id'] ?>)" 
                              title="Send Reminder">
                        <i class="bi bi-bell"></i>
                      </button>
                      
                      <button class="action-btn" style="background: #fce4ec; color: #c2185b;" 
                              onclick="adjustLateFee(<?= $row['booking_id'] ?>, <?= $row['late_fee_amount'] ?>)" 
                              title="Adjust Late Fee">
                        <i class="bi bi-pencil"></i>
                      </button>
                      
                      <button class="action-btn" style="background: #e8f5e9; color: #388e3c;" 
                              onclick="waiveLateFee(<?= $row['booking_id'] ?>)" 
                              title="Waive Late Fee">
                        <i class="bi bi-x-circle"></i>
                      </button>
                      <?php endif; ?>
                      
                      <button class="action-btn btn-complete" onclick="forceComplete(<?= $row['booking_id'] ?>)" title="Force Complete">
                        <i class="bi bi-check-lg"></i>
                      </button>
                      
                      <button class="action-btn btn-contact" onclick="contactRenter(<?= $row['booking_id'] ?>)" title="Contact Renter">
                        <i class="bi bi-telephone"></i>
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
      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong><?= $offset + 1 ?></strong> - <strong><?= min($offset + $limit, $totalRecords) ?></strong>
          of <strong><?= number_format($totalRecords) ?></strong> entries
        </div>
        
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&severity=<?= $severityFilter ?>&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">
            <i class="bi bi-chevron-left"></i>
          </a>
          <?php else: ?>
          <span class="page-btn disabled">
            <i class="bi bi-chevron-left"></i>
          </span>
          <?php endif; ?>
          
          <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
          ?>
            <a href="?page=<?= $i ?>&severity=<?= $severityFilter ?>&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="page-btn <?= $i === $page ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&severity=<?= $severityFilter ?>&payment=<?= $paymentFilter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">
            <i class="bi bi-chevron-right"></i>
          </a>
          <?php else: ?>
          <span class="page-btn disabled">
            <i class="bi bi-chevron-right"></i>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// View booking details
function viewDetails(bookingId) {
  fetch(`api/overdue/get_booking_details.php?id=${bookingId}`)
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => {
          console.error('Server error response:', text);
          throw new Error(`Server error: ${response.status}`);
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        const booking = data.data;
        document.getElementById('detailsModalBody').innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6><i class="bi bi-calendar"></i> Booking Information</h6>
              <table class="table table-sm">
                <tr><td><strong>Booking ID:</strong></td><td>#BK-${String(booking.booking_id).padStart(4, '0')}</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="overdue-badge ${booking.overdue_status === 'severely_overdue' ? 'danger' : 'warning'}">${booking.overdue_status.toUpperCase()}</span></td></tr>
                <tr><td><strong>Pickup:</strong></td><td>${booking.pickup_date} ${booking.pickup_time}</td></tr>
                <tr><td><strong>Return Due:</strong></td><td>${booking.return_date} ${booking.return_time}</td></tr>
                <tr><td><strong>Days Overdue:</strong></td><td><strong style="color: #d32f2f;">${booking.days_overdue} days</strong></td></tr>
                <tr><td><strong>Hours Overdue:</strong></td><td>${booking.hours_overdue} hours</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6><i class="bi bi-cash"></i> Financial Information</h6>
              <table class="table table-sm">
                <tr><td><strong>Total Amount:</strong></td><td>₱${Number(booking.total_amount).toFixed(2)}</td></tr>
                <tr><td><strong>Late Fee:</strong></td><td><strong>₱${Number(booking.late_fee_amount).toFixed(2)}</strong></td></tr>
                <tr><td><strong>Fee Status:</strong></td><td>${booking.late_fee_charged ? '<span class="fee-badge charged">Charged</span>' : '<span class="fee-badge pending">Pending</span>'}</td></tr>
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
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => {
          console.error('Server error response:', text);
          throw new Error(`Server error: ${response.status}`);
        });
      }
      return response.json();
    })
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
            <strong>Email:</strong> ${booking.renter_email}<br>
            <strong>Phone:</strong> ${booking.renter_phone}
          </p>
          <h6>Booking Details</h6>
          <p>
            <strong>Booking ID:</strong> #BK-${String(booking.booking_id).padStart(4, '0')}<br>
            <strong>Vehicle:</strong> ${booking.vehicle_name}<br>
            <strong>Days Overdue:</strong> <strong style="color: #d32f2f;">${booking.days_overdue} days</strong><br>
            <strong>Late Fee:</strong> ₱${Number(booking.late_fee_amount).toFixed(2)}
          </p>
          <div class="d-grid gap-2">
            <a href="mailto:${booking.renter_email}?subject=Overdue Booking #BK-${String(booking.booking_id).padStart(4, '0')}" class="contact-action-btn email">
              <i class="bi bi-envelope"></i> Send Email
            </a>
            <a href="tel:${booking.renter_phone}" class="contact-action-btn call">
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
  const notes = prompt('Force complete this overdue booking?\n\nEnter notes (optional):');
  if (notes === null) return; // User cancelled
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('notes', notes || 'Manually completed by admin');
  
  fetch('api/overdue/force_complete.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // Check if response is ok
    if (!response.ok) {
      return response.text().then(text => {
        console.error('Server error response:', text);
        throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
      });
    }
    return response.json();
  })
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
    alert('Error: ' + error.message);
  });
}

// Confirm Late Fee
function confirmLateFee(bookingId, currentAmount) {
  const notes = prompt('Confirm late fee of ₱' + currentAmount.toFixed(2) + '?\n\nEnter notes (optional):');
  if (notes === null) return; // User cancelled
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('late_fee_amount', currentAmount);
  formData.append('notes', notes || '');
  
  fetch('api/overdue/confirm_late_fee.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      return response.text().then(text => {
        console.error('Server error response:', text);
        throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
      });
    }
    return response.json();
  })
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
    alert('Error: ' + error.message);
  });
}

// Send Reminder
function sendReminder(bookingId) {
  if (!confirm('Send overdue reminder to the renter?')) return;
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  
  const btn = event.target.closest('button');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
  
  fetch('api/overdue/send_reminder.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      return response.text().then(text => {
        console.error('Server error response:', text);
        throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
      });
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('❌ ' + data.message);
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error: ' + error.message);
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  });
}

// Adjust Late Fee
function adjustLateFee(bookingId, currentAmount) {
  const newAmount = prompt('Adjust late fee\nCurrent: ₱' + currentAmount.toFixed(2) + '\n\nEnter new amount:', currentAmount.toFixed(2));
  if (newAmount === null) return; // User cancelled
  
  const parsedAmount = parseFloat(newAmount);
  if (isNaN(parsedAmount) || parsedAmount < 0) {
    alert('❌ Invalid amount');
    return;
  }
  
  const reason = prompt('Enter reason for adjustment:');
  if (!reason || reason.trim() === '') {
    alert('❌ Reason is required');
    return;
  }
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('new_amount', parsedAmount);
  formData.append('reason', reason);
  
  fetch('api/overdue/adjust_late_fee.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      return response.text().then(text => {
        console.error('Server error response:', text);
        throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
      });
    }
    return response.json();
  })
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
    alert('Error: ' + error.message);
  });
}

// Waive Late Fee
function waiveLateFee(bookingId) {
  const reason = prompt('Waive late fee?\n\nEnter reason for waiving:');
  if (!reason || reason.trim() === '') {
    if (reason !== null) alert('❌ Reason is required');
    return;
  }
  
  if (!confirm('Are you sure you want to waive the late fee?\nReason: ' + reason)) return;
  
  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('reason', reason);
  
  fetch('api/overdue/waive_late_fee.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      return response.text().then(text => {
        console.error('Server error response:', text);
        throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
      });
    }
    return response.json();
  })
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
    alert('Error: ' + error.message);
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