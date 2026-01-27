<?php
/**
 * ============================================================================
 * ESCROW MANAGEMENT - CarGo Admin (FIXED)
 * Manage funds held in escrow between payment and payout
 * ============================================================================
 */

session_start();
include "include/db.php";

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   FIRST: CHECK IF escrow_logs TABLE EXISTS
   ========================================================= */
$checkLogsTable = mysqli_query($conn, "SHOW TABLES LIKE 'escrow_logs'");
$logsTableExists = mysqli_num_rows($checkLogsTable) > 0;

/* =========================================================
   ESCROW STATISTICS WITH ERROR HANDLING
   ========================================================= */

// Total funds currently held in escrow
$fundsQuery = "SELECT COALESCE(SUM(owner_payout), 0) AS total 
               FROM bookings 
               WHERE escrow_status = 'held' AND owner_payout > 0";
$fundsResult = mysqli_query($conn, $fundsQuery);
if (!$fundsResult) {
    error_log("Funds query error: " . mysqli_error($conn));
    $fundsInEscrow = 0;
} else {
    $fundsInEscrow = mysqli_fetch_assoc($fundsResult)['total'];
}

// Pending releases (completed bookings ready for release)
$pendingQuery = "SELECT COUNT(*) AS c FROM bookings 
                 WHERE status = 'completed' 
                 AND escrow_status = 'held'";
$pendingResult = mysqli_query($conn, $pendingQuery);
if (!$pendingResult) {
    error_log("Pending releases query error: " . mysqli_error($conn));
    $pendingReleases = 0;
} else {
    $pendingReleases = mysqli_fetch_assoc($pendingResult)['c'];
}

// Released this month
$releasedQuery = "SELECT COALESCE(SUM(owner_payout), 0) AS total 
                  FROM bookings 
                  WHERE escrow_status IN ('released_to_owner', 'released')
                  AND escrow_released_at IS NOT NULL
                  AND MONTH(escrow_released_at) = MONTH(NOW())
                  AND YEAR(escrow_released_at) = YEAR(NOW())";
$releasedResult = mysqli_query($conn, $releasedQuery);
if (!$releasedResult) {
    error_log("Released query error: " . mysqli_error($conn));
    $releasedThisMonth = 0;
} else {
    $releasedThisMonth = mysqli_fetch_assoc($releasedResult)['total'];
}

// Disputed/On Hold - Since 'on_hold' doesn't exist in enum, check escrow_hold_reason instead
$disputedQuery = "SELECT COUNT(*) AS c FROM bookings 
                  WHERE escrow_hold_reason IS NOT NULL 
                  AND escrow_hold_reason != ''
                  AND escrow_status = 'held'";
$disputedResult = mysqli_query($conn, $disputedQuery);
if (!$disputedResult) {
    error_log("Disputed query error: " . mysqli_error($conn));
    $disputedCount = 0;
} else {
    $disputedCount = mysqli_fetch_assoc($disputedResult)['c'];
}

// Average escrow duration (days between payment verification and release)
$avgQuery = "SELECT AVG(DATEDIFF(COALESCE(escrow_released_at, NOW()), 
                                  COALESCE(payment_verified_at, created_at))) AS avg_days
             FROM bookings
             WHERE escrow_status IN ('released_to_owner', 'released')
             AND escrow_released_at IS NOT NULL";
$avgResult = mysqli_query($conn, $avgQuery);
if (!$avgResult) {
    error_log("Average duration query error: " . mysqli_error($conn));
    $avgDuration = 0;
} else {
    $avgRow = mysqli_fetch_assoc($avgResult);
    $avgDuration = $avgRow['avg_days'] ?? 0;
}

/* =========================================================
   FILTERS & PAGINATION
   ========================================================= */
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'held';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* =========================================================
   BUILD WHERE CLAUSE - FIXED FOR ACTUAL ENUM VALUES
   ========================================================= */
$where = " WHERE 1 ";

// Status filter - using actual enum values from database
switch($statusFilter) {
    case 'held':
        $where .= " AND b.escrow_status = 'held' AND (b.escrow_hold_reason IS NULL OR b.escrow_hold_reason = '') ";
        break;
    case 'released':
        $where .= " AND b.escrow_status IN ('released_to_owner', 'released') ";
        break;
    case 'on_hold':
        // Since 'on_hold' is not in enum, we check for hold reason
        $where .= " AND b.escrow_status = 'held' AND b.escrow_hold_reason IS NOT NULL AND b.escrow_hold_reason != '' ";
        break;
    case 'refunded':
        $where .= " AND b.escrow_status = 'refunded' ";
        break;
    case 'pending_release':
        $where .= " AND b.escrow_status = 'held' AND b.status = 'completed' ";
        break;
    case 'all':
        $where .= " AND b.escrow_status IN ('held', 'released_to_owner', 'released', 'refunded', 'pending') ";
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
   MAIN QUERY - ESCROW TRANSACTIONS
   ========================================================= */
$sql = "
    SELECT 
        b.id AS booking_id,
        b.user_id AS renter_id,
        b.owner_id,
        b.car_id,
        b.total_amount,
        b.platform_fee,
        b.owner_payout,
        b.status AS booking_status,
        b.escrow_status,
        b.pickup_date,
        b.return_date,
        b.created_at,
        b.escrow_released_at,
        b.escrow_held_at,
        b.payment_verified_at,
        b.vehicle_type,
        b.escrow_hold_reason,
        b.escrow_hold_details,
        
        -- Calculate days in escrow
        CASE 
            WHEN b.escrow_status = 'held' THEN 
                DATEDIFF(NOW(), COALESCE(b.escrow_held_at, b.payment_verified_at, b.created_at))
            WHEN b.escrow_status IN ('released_to_owner', 'released') THEN 
                DATEDIFF(b.escrow_released_at, COALESCE(b.escrow_held_at, b.payment_verified_at, b.created_at))
            ELSE 0
        END AS days_in_escrow,
        
        -- Renter info
        u_renter.fullname AS renter_name,
        u_renter.email AS renter_email,
        u_renter.phone AS renter_phone,
        
        -- Owner info
        u_owner.fullname AS owner_name,
        u_owner.email AS owner_email,
        u_owner.gcash_number AS owner_gcash,
        u_owner.gcash_name AS owner_gcash_name,
        
        -- Vehicle info (supports both cars and motorcycles)
        COALESCE(c.brand, m.brand) AS brand,
        COALESCE(c.model, m.model) AS model,
        COALESCE(c.car_year, m.motorcycle_year) AS vehicle_year,
        COALESCE(c.plate_number, m.plate_number) AS plate_number,
        COALESCE(c.image, m.image) AS vehicle_image,
        
        -- Payment info
        p.payment_status,
        p.payment_method,
        p.payment_reference,
        p.created_at AS payment_date,
        
        -- Payout status
        b.payout_status
        
    FROM bookings b
    JOIN users u_renter ON b.user_id = u_renter.id
    JOIN users u_owner ON b.owner_id = u_owner.id
    LEFT JOIN payments p ON b.id = p.booking_id
    LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
    LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
    $where
    ORDER BY 
        CASE 
            -- Priority 1: Items on hold (have hold reason)
            WHEN b.escrow_hold_reason IS NOT NULL AND b.escrow_hold_reason != '' THEN 1
            -- Priority 2: Completed bookings ready to release
            WHEN b.escrow_status = 'held' AND b.status = 'completed' THEN 2
            -- Priority 3: Other held escrow
            WHEN b.escrow_status = 'held' THEN 3
            -- Everything else
            ELSE 4
        END,
        b.created_at DESC
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

$countRes = mysqli_query($conn, $countSql);
if (!$countRes) {
    die("Count query error: " . mysqli_error($conn));
}
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = max(1, ceil($totalRows / $limit));

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Escrow Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <link href="include/notifications.css" rel="stylesheet">
  <style>
    /* Additional escrow-specific styles */
    .escrow-timeline {
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
      margin-top: 20px;
    }
    
    .timeline-item {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e0e0e0;
    }
    
    .timeline-item:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    
    .timeline-icon {
      width: 40px;
      height: 40px;
      background: #1a1a1a;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .timeline-content {
      flex: 1;
    }
    
    .timeline-title {
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 4px;
    }
    
    .timeline-date {
      font-size: 12px;
      color: #999;
    }
    
    .escrow-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .escrow-badge.held {
      background: #fff3cd;
      color: #856404;
    }
    
    .escrow-badge.released {
      background: #d4edda;
      color: #155724;
    }
    
    .escrow-badge.on-hold {
      background: #f8d7da;
      color: #721c24;
    }
    
    .escrow-badge.refunded {
      background: #d1ecf1;
      color: #0c5460;
    }
    
    .urgency-high {
      color: #dc3545;
      font-weight: 700;
    }
    
    .urgency-medium {
      color: #ffc107;
      font-weight: 600;
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
        <i class="bi bi-shield-lock"></i>
        Escrow Management
      </h1>
      <div class="user-profile">
        <div class="notification-dropdown">
          <button class="notification-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notification-badge" style="display: none;">0</span>
          </button>
        </div>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-lock"></i>
            Secured
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($fundsInEscrow, 2) ?></div>
        <div class="stat-label">Funds in Escrow</div>
        <div class="stat-detail">Currently held</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-exclamation-circle"></i>
            Urgent
          </div>
        </div>
        <div class="stat-value"><?= $pendingReleases ?></div>
        <div class="stat-label">Pending Releases</div>
        <div class="stat-detail">Completed bookings</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12%
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($releasedThisMonth, 2) ?></div>
        <div class="stat-label">Released This Month</div>
        <div class="stat-detail">To owners</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-trend">
            <?php if ($disputedCount > 0): ?>
            <i class="bi bi-exclamation-triangle"></i>
            <?= $disputedCount ?>
            <?php else: ?>
            <i class="bi bi-check"></i>
            Clear
            <?php endif; ?>
          </div>
        </div>
        <div class="stat-value"><?= round($avgDuration, 1) ?> days</div>
        <div class="stat-label">Avg. Escrow Duration</div>
        <div class="stat-detail">Release time</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" class="filter-row">
        <div class="search-box">
          <input type="text" name="search" id="searchInput" placeholder="Search by booking, renter, owner, or vehicle..." value="<?= htmlspecialchars($search) ?>">
          <i class="bi bi-search"></i>
        </div>

        <select name="status" class="filter-dropdown" onchange="this.form.submit()">
          <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Escrow</option>
          <option value="held" <?= $statusFilter === 'held' ? 'selected' : '' ?>>Currently Held</option>
          <option value="pending_release" <?= $statusFilter === 'pending_release' ? 'selected' : '' ?>>Ready to Release</option>
          <option value="on_hold" <?= $statusFilter === 'on_hold' ? 'selected' : '' ?>>On Hold / Disputed</option>
          <option value="released" <?= $statusFilter === 'released' ? 'selected' : '' ?>>Released</option>
          <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>

        <button type="button" class="add-user-btn" onclick="exportEscrow()">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </form>
    </div>

    <!-- Escrow Table -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Escrow Transactions</h2>
        <div class="table-controls">
          <a href="?status=held" class="table-btn <?= $statusFilter === 'held' ? 'active' : '' ?>">
            Currently Held
          </a>
          <a href="?status=pending_release" class="table-btn <?= $statusFilter === 'pending_release' ? 'active' : '' ?>">
            Pending Release (<?= $pendingReleases ?>)
          </a>
          <a href="?status=on_hold" class="table-btn <?= $statusFilter === 'on_hold' ? 'active' : '' ?>">
            On Hold <?= $disputedCount > 0 ? "($disputedCount)" : '' ?>
          </a>
        </div>
      </div>

      <div class="table-responsive">
        <?php if (mysqli_num_rows($result) == 0): ?>
        <div style="padding: 60px 20px; text-center;">
          <i class="bi bi-inbox" style="font-size: 64px; color: #ddd;"></i>
          <p style="margin-top: 20px; color: #999; font-size: 16px;">No escrow transactions found</p>
        </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Renter</th>
              <th>Owner</th>
              <th>Vehicle</th>
              <th>Escrow Amount</th>
              <th>Booking Status</th>
              <th>Escrow Status</th>
              <th>Days in Escrow</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): 
              $bookingId = '#BK-' . str_pad($row['booking_id'], 4, '0', STR_PAD_LEFT);
              $vehicleName = htmlspecialchars($row['brand'] . ' ' . $row['model'] . ' ' . $row['vehicle_year']);
              $daysInEscrow = intval($row['days_in_escrow']);
              
              // Determine if this is "on hold" (has hold reason)
              $isOnHold = !empty($row['escrow_hold_reason']);
              $actualEscrowStatus = $isOnHold ? 'on_hold' : $row['escrow_status'];
              
              // Determine urgency class
              $urgencyClass = '';
              if ($actualEscrowStatus === 'held' && $row['booking_status'] === 'completed') {
                if ($daysInEscrow > 7) {
                  $urgencyClass = 'urgency-high';
                } elseif ($daysInEscrow > 3) {
                  $urgencyClass = 'urgency-medium';
                }
              }
              
              // Status badge mapping - FIXED
              $escrowStatusMap = [
                'held' => 'held',
                'released_to_owner' => 'released',
                'released' => 'released',
                'on_hold' => 'on-hold',
                'refunded' => 'refunded',
                'pending' => 'held'
              ];
              $escrowBadgeClass = $escrowStatusMap[$actualEscrowStatus] ?? 'held';
              
              $bookingStatusClass = [
                'completed' => 'verified',
                'ongoing' => 'pending',
                'approved' => 'pending',
                'cancelled' => 'rejected'
              ][$row['booking_status']] ?? 'pending';
            ?>
            <tr>
              <td>
                <strong><?= $bookingId ?></strong><br>
                <small style="color:#999;"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
              </td>

              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['renter_name']) ?>&background=1a1a1a&color=fff">
                  </div>
                  <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($row['renter_name']) ?></span>
                    <span class="user-email"><?= htmlspecialchars($row['renter_email']) ?></span>
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
                    <span class="user-email"><?= htmlspecialchars($row['owner_email']) ?></span>
                  </div>
                </div>
              </td>

              <td>
                <strong><?= $vehicleName ?></strong><br>
                <small style="color:#999;"><?= htmlspecialchars($row['plate_number']) ?></small>
              </td>

              <td>
                <div style="font-size: 14px;">
                  <strong style="color: #000; font-size: 16px;">₱<?= number_format($row['owner_payout'], 2) ?></strong><br>
                  <small style="color: #999;">
                    Total: ₱<?= number_format($row['total_amount'], 2) ?><br>
                    Fee: ₱<?= number_format($row['platform_fee'], 2) ?>
                  </small>
                </div>
              </td>

              <td>
                <span class="status-badge <?= $bookingStatusClass ?>">
                  <?= ucfirst($row['booking_status']) ?>
                </span>
                <?php if ($row['booking_status'] === 'completed' && $actualEscrowStatus === 'held'): ?>
                <br><small style="color: #28a745;">✓ Ready for release</small>
                <?php endif; ?>
              </td>

              <td>
                <span class="escrow-badge <?= $escrowBadgeClass ?>">
                  <?php
                  $statusIcons = [
                    'held' => '🔒',
                    'released_to_owner' => '✅',
                    'released' => '✅',
                    'on_hold' => '⚠️',
                    'refunded' => '↩️',
                    'pending' => '⏳'
                  ];
                  echo $statusIcons[$actualEscrowStatus] ?? '🔒';
                  ?>
                  <?= ucfirst(str_replace('_', ' ', $actualEscrowStatus)) ?>
                </span>
                <?php if ($isOnHold): ?>
                <br><small style="color: #dc3545;">Reason: <?= htmlspecialchars($row['escrow_hold_reason']) ?></small>
                <?php endif; ?>
              </td>

              <td>
                <span class="<?= $urgencyClass ?>">
                  <?= $daysInEscrow ?> day<?= $daysInEscrow != 1 ? 's' : '' ?>
                </span>
              </td>

              <td>
                <div class="action-buttons">
                  <button class="action-btn view" onclick='viewEscrowDetails(<?= json_encode($row) ?>)' title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>

                  <?php if ($actualEscrowStatus === 'held' && $row['booking_status'] === 'completed'): ?>
                  <button class="action-btn approve" onclick="releaseEscrow(<?= $row['booking_id'] ?>)" title="Release to Owner">
                    <i class="bi bi-unlock"></i>
                  </button>
                  <?php endif; ?>

                  <?php if ($actualEscrowStatus === 'held' && !$isOnHold): ?>
                  <button class="action-btn edit" onclick="holdEscrow(<?= $row['booking_id'] ?>)" title="Put On Hold">
                    <i class="bi bi-pause-circle"></i>
                  </button>
                  <button class="action-btn reject" onclick="refundEscrow(<?= $row['booking_id'] ?>)" title="Refund to Renter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </button>
                  <?php endif; ?>

                  <?php if ($isOnHold): ?>
                  <button class="action-btn approve" onclick="resumeEscrow(<?= $row['booking_id'] ?>)" title="Resume Escrow">
                    <i class="bi bi-play-circle"></i>
                  </button>
                  <button class="action-btn reject" onclick="refundEscrow(<?= $row['booking_id'] ?>)" title="Refund to Renter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong><?= $offset + 1 ?></strong> - <strong><?= min($offset + $limit, $totalRows) ?></strong>
          of <strong><?= $totalRows ?></strong> transactions
        </div>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" class="page-btn">
            <i class="bi bi-chevron-left"></i>
          </a>
          <?php endif; ?>

          <?php 
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          for ($i = $startPage; $i <= $endPage; $i++): 
          ?>
          <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" 
             class="page-btn <?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" class="page-btn">
            <i class="bi bi-chevron-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Escrow Details Modal -->
<div class="modal fade" id="escrowModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" id="escrowModalContent">
      <!-- Loaded dynamically -->
    </div>
  </div>
</div>

<!-- Hold Escrow Modal -->
<div class="modal fade" id="holdModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Put Escrow On Hold</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="hold_booking_id">
        
        <div class="mb-3">
          <label class="form-label">Reason for Hold *</label>
          <select class="form-control" id="hold_reason" required>
            <option value="">Select reason...</option>
            <option value="dispute">Dispute between parties</option>
            <option value="investigation">Under investigation</option>
            <option value="complaint">Customer complaint</option>
            <option value="damage">Vehicle damage claim</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Details *</label>
          <textarea class="form-control" id="hold_details" rows="3" required 
                    placeholder="Provide detailed reason for putting escrow on hold..."></textarea>
        </div>

        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          This will freeze the escrow funds until the issue is resolved. Both renter and owner will be notified.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" onclick="submitHold()">
          <i class="bi bi-pause-circle"></i> Put On Hold
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Refund Escrow Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Refund Escrow to Renter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="refund_booking_id">
        
        <div class="mb-3">
          <label class="form-label">Refund Reason *</label>
          <select class="form-control" id="refund_reason" required>
            <option value="">Select reason...</option>
            <option value="cancelled_by_owner">Cancelled by owner</option>
            <option value="car_unavailable">Car unavailable</option>
            <option value="booking_error">Booking error</option>
            <option value="customer_request">Customer request</option>
            <option value="dispute_resolution">Dispute resolution</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Refund Details *</label>
          <textarea class="form-control" id="refund_details" rows="3" required 
                    placeholder="Explain why the escrow is being refunded..."></textarea>
        </div>

        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle"></i>
          <strong>Warning:</strong> This will return funds to the renter and cancel the booking. This action cannot be undone.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="submitRefund()">
          <i class="bi bi-arrow-counterclockwise"></i> Process Refund
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// VIEW ESCROW DETAILS
function viewEscrowDetails(escrow) {
  const vehicleName = `${escrow.brand} ${escrow.model} ${escrow.vehicle_year}`;
  const bookingId = '#BK-' + String(escrow.booking_id).padStart(4, '0');
  
  // Determine actual escrow status
  const isOnHold = escrow.escrow_hold_reason && escrow.escrow_hold_reason.trim() !== '';
  const actualEscrowStatus = isOnHold ? 'on_hold' : escrow.escrow_status;
  
  const escrowStatusMap = {
    'held': { icon: '🔒', label: 'Held in Escrow', class: 'warning' },
    'released_to_owner': { icon: '✅', label: 'Released to Owner', class: 'success' },
    'released': { icon: '✅', label: 'Released to Owner', class: 'success' },
    'on_hold': { icon: '⚠️', label: 'On Hold', class: 'danger' },
    'refunded': { icon: '↩️', label: 'Refunded to Renter', class: 'info' }
  };
  
  const status = escrowStatusMap[actualEscrowStatus] || { icon: '❓', label: 'Unknown', class: 'secondary' };
  
  document.getElementById('escrowModalContent').innerHTML = `
    <div class="modal-header">
      <h5 class="modal-title">
        <i class="bi bi-shield-lock"></i>
        Escrow Details - ${bookingId}
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
      <!-- Status Alert -->
      <div class="alert alert-${status.class}">
        <h6 style="margin: 0;">${status.icon} ${status.label}</h6>
        <small>Days in escrow: <strong>${escrow.days_in_escrow}</strong></small>
        ${isOnHold ? `<br><small><strong>Hold Reason:</strong> ${escrow.escrow_hold_reason}</small>` : ''}
      </div>

      <!-- Escrow Amount Breakdown -->
      <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h6 style="margin-bottom: 15px;">Escrow Amount Breakdown</h6>
        <table class="table table-sm mb-0">
          <tr>
            <td>Total Booking Amount</td>
            <td class="text-end"><strong>₱${parseFloat(escrow.total_amount).toLocaleString()}</strong></td>
          </tr>
          <tr>
            <td>Platform Fee (10%)</td>
            <td class="text-end text-danger">-₱${parseFloat(escrow.platform_fee).toLocaleString()}</td>
          </tr>
          <tr class="table-success">
            <td><strong>Owner Payout (In Escrow)</strong></td>
            <td class="text-end"><strong>₱${parseFloat(escrow.owner_payout).toLocaleString()}</strong></td>
          </tr>
        </table>
      </div>

      <!-- Parties Information -->
      <div class="row mb-3">
        <div class="col-md-6">
          <h6>Renter</h6>
          <p>
            <strong>${escrow.renter_name}</strong><br>
            ${escrow.renter_email}<br>
            ${escrow.renter_phone || 'N/A'}
          </p>
        </div>
        <div class="col-md-6">
          <h6>Owner</h6>
          <p>
            <strong>${escrow.owner_name}</strong><br>
            ${escrow.owner_email}<br>
            ${escrow.owner_gcash ? `GCash: ${escrow.owner_gcash}` : 'GCash not set'}
          </p>
        </div>
      </div>

      <!-- Booking Information -->
      <h6>Booking Details</h6>
      <p>
        <strong>Vehicle:</strong> ${vehicleName}<br>
        <strong>Plate:</strong> ${escrow.plate_number}<br>
        <strong>Rental Period:</strong> ${escrow.pickup_date} to ${escrow.return_date}<br>
        <strong>Booking Status:</strong> <span class="badge bg-primary">${escrow.booking_status}</span><br>
        <strong>Payment Method:</strong> ${escrow.payment_method ? escrow.payment_method.toUpperCase() : 'N/A'}<br>
        ${escrow.payment_reference ? `<strong>Payment Ref:</strong> ${escrow.payment_reference}<br>` : ''}
      </p>

      <!-- Timeline -->
      <div class="escrow-timeline">
        <h6 style="margin-bottom: 15px;">Escrow Timeline</h6>
        
        <div class="timeline-item">
          <div class="timeline-icon">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="timeline-content">
            <div class="timeline-title">Payment Verified</div>
            <div class="timeline-date">${escrow.payment_date ? new Date(escrow.payment_date).toLocaleString() : 'N/A'}</div>
          </div>
        </div>

        <div class="timeline-item">
          <div class="timeline-icon">
            <i class="bi bi-lock"></i>
          </div>
          <div class="timeline-content">
            <div class="timeline-title">Funds Held in Escrow</div>
            <div class="timeline-date">${escrow.escrow_held_at ? new Date(escrow.escrow_held_at).toLocaleString() : new Date(escrow.created_at).toLocaleString()}</div>
          </div>
        </div>

        ${actualEscrowStatus === 'released_to_owner' || actualEscrowStatus === 'released' ? `
        <div class="timeline-item">
          <div class="timeline-icon">
            <i class="bi bi-unlock"></i>
          </div>
          <div class="timeline-content">
            <div class="timeline-title">Released to Owner</div>
            <div class="timeline-date">${escrow.escrow_released_at ? new Date(escrow.escrow_released_at).toLocaleString() : 'N/A'}</div>
          </div>
        </div>
        ` : ''}

        ${isOnHold ? `
        <div class="timeline-item">
          <div class="timeline-icon" style="background: #dc3545;">
            <i class="bi bi-pause-circle"></i>
          </div>
          <div class="timeline-content">
            <div class="timeline-title">Put On Hold</div>
            <div class="timeline-date">Currently under review</div>
            ${escrow.escrow_hold_details ? `<div style="margin-top: 8px; font-size: 13px;">${escrow.escrow_hold_details}</div>` : ''}
          </div>
        </div>
        ` : ''}
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  `;
  
  new bootstrap.Modal(document.getElementById('escrowModal')).show();
}

// RELEASE ESCROW
function releaseEscrow(bookingId) {
  if (!confirm('Release escrow funds to owner? This will schedule the payout.')) return;

  const formData = new FormData();
  formData.append('booking_id', bookingId);

  fetch('api/escrow/release_escrow.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Network error occurred');
  });
}

// HOLD ESCROW
function holdEscrow(bookingId) {
  document.getElementById('hold_booking_id').value = bookingId;
  document.getElementById('hold_reason').value = '';
  document.getElementById('hold_details').value = '';
  new bootstrap.Modal(document.getElementById('holdModal')).show();
}

function submitHold() {
  const bookingId = document.getElementById('hold_booking_id').value;
  const reason = document.getElementById('hold_reason').value;
  const details = document.getElementById('hold_details').value.trim();

  if (!reason || !details) {
    alert('Please provide both reason and details');
    return;
  }

  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('reason', reason);
  formData.append('details', details);

  fetch('api/escrow/hold_escrow.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('⚠️ ' + data.message);
      bootstrap.Modal.getInstance(document.getElementById('holdModal')).hide();
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Network error occurred');
  });
}

// REFUND ESCROW
function refundEscrow(bookingId) {
  document.getElementById('refund_booking_id').value = bookingId;
  document.getElementById('refund_reason').value = '';
  document.getElementById('refund_details').value = '';
  new bootstrap.Modal(document.getElementById('refundModal')).show();
}

function submitRefund() {
  const bookingId = document.getElementById('refund_booking_id').value;
  const reason = document.getElementById('refund_reason').value;
  const details = document.getElementById('refund_details').value.trim();

  if (!reason || !details) {
    alert('Please provide both reason and details');
    return;
  }

  if (!confirm('Are you sure you want to refund this escrow to the renter? This cannot be undone.')) {
    return;
  }

  const formData = new FormData();
  formData.append('booking_id', bookingId);
  formData.append('reason', reason);
  formData.append('details', details);

  fetch('api/escrow/refund_escrow.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      bootstrap.Modal.getInstance(document.getElementById('refundModal')).hide();
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Network error occurred');
  });
}

// RESUME ESCROW (from on_hold back to held)
function resumeEscrow(bookingId) {
  if (!confirm('Resume this escrow? It will return to normal held status.')) return;

  const formData = new FormData();
  formData.append('booking_id', bookingId);

  fetch('api/escrow/resume_escrow.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Network error occurred');
  });
}

// EXPORT
function exportEscrow() {
  const params = new URLSearchParams(window.location.search);
  window.location.href = 'api/escrow/export_escrow.php?' + params.toString();
}

// LIVE SEARCH
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