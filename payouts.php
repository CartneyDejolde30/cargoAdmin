<?php
/**
 * ============================================================================
 * PAYOUTS MANAGEMENT - CarGo Admin
 * Manage payouts to car owners
 * ============================================================================
 */

session_start();
include "include/db.php";

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   PAYOUT STATISTICS
   ========================================================= */

// Pending payouts (escrow released but payout not completed)
$pendingPayouts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM bookings 
     WHERE escrow_status='released_to_owner' AND payout_status IN ('pending', 'processing')"
))['c'];

// Total pending amount
$pendingAmount = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(owner_payout), 0) AS total 
     FROM bookings 
     WHERE escrow_status='released_to_owner' AND payout_status IN ('pending', 'processing')"
))['total'];

// Completed payouts this month
$completedThisMonth = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM payouts 
     WHERE status='completed' 
     AND MONTH(processed_at) = MONTH(NOW()) 
     AND YEAR(processed_at) = YEAR(NOW())"
))['c'];

// Total paid this month
$paidThisMonth = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(net_amount), 0) AS total 
     FROM payouts 
     WHERE status='completed' 
     AND MONTH(processed_at) = MONTH(NOW()) 
     AND YEAR(processed_at) = YEAR(NOW())"
))['total'];

/* =========================================================
   PAGINATION & FILTERS
   ========================================================= */
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
$bookingFocus = isset($_GET['booking_id']) 
    ? intval($_GET['booking_id']) 
    : null;
if ($bookingFocus) {
    $statusFilter = 'pending';
}


/* =========================================================
   BUILD WHERE CLAUSE
   ========================================================= */
$where = " WHERE 1 ";
if ($bookingFocus) {
    $where .= " AND b.id = $bookingFocus ";
}


switch($statusFilter) {
    case 'pending':
    $where .= " 
        AND b.escrow_status IN ('held', 'released_to_owner')
        AND b.payout_status IN ('pending', 'processing') 
    ";
    break;
    case 'completed':
        $where .= " AND p.status = 'completed' ";
        break;
    case 'processing':
        $where .= " AND p.status = 'processing' ";
        break;
    case 'failed':
        $where .= " AND p.status = 'failed' ";
        break;
    case 'all':
        // Show all payouts
        break;
}

/* =========================================================
   MAIN QUERY - PENDING & COMPLETED PAYOUTS
   ========================================================= */
if ($statusFilter == 'pending') {
    // Show bookings ready for payout
    $sql = "
        SELECT 
            b.id AS booking_id,
            b.owner_id,
            b.owner_payout,
            b.platform_fee,
            b.total_amount,
            b.escrow_status,
            b.payout_status,
            b.escrow_released_at,
            b.return_date,
            b.pickup_date,
            
            -- Owner info
            u.fullname AS owner_name,
            u.email AS owner_email,
            u.gcash_number,
            u.gcash_name,
            
            -- Car info
            COALESCE(c.brand, m.brand) AS brand,
            COALESCE(c.model, m.model) AS model,
            COALESCE(c.car_year, m.motorcycle_year) AS vehicle_year,
            b.vehicle_type,
            
            -- Renter info
            u2.fullname AS renter_name
            
        FROM bookings b
        JOIN users u ON b.owner_id = u.id
        LEFT JOIN users u2 ON b.user_id = u2.id
        LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
        LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
        $where
        ORDER BY b.escrow_released_at DESC
        LIMIT $limit OFFSET $offset
    ";
} else {
    // Show payout records
    $sql = "
        SELECT 
            p.id AS payout_id,
            p.booking_id,
            p.owner_id,
            p.amount,
            p.platform_fee,
            p.net_amount,
            p.payout_method,
            p.payout_account,
            p.status,
            p.scheduled_at,
            p.processed_at,
            p.completion_reference,
            p.transfer_proof,
            p.created_at,
            
            -- Booking info
            b.pickup_date,
            b.return_date,
            b.vehicle_type,
            
            -- Owner info
            u.fullname AS owner_name,
            u.email AS owner_email,
            u.gcash_number,
            u.gcash_name,
            
            -- Car info
            COALESCE(c.brand, m.brand) AS brand,
            COALESCE(c.model, m.model) AS model,
            COALESCE(c.car_year, m.motorcycle_year) AS vehicle_year
            
        FROM payouts p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u ON p.owner_id = u.id
        LEFT JOIN cars c ON b.vehicle_type = 'car' AND b.car_id = c.id
        LEFT JOIN motorcycles m ON b.vehicle_type = 'motorcycle' AND b.car_id = m.id
        $where
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL ERROR: " . mysqli_error($conn));
}

// Count for pagination
$countSql = "SELECT COUNT(*) AS total FROM (" . str_replace("LIMIT $limit OFFSET $offset", "", $sql) . ") AS count_table";
$countRes = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = max(1, ceil($totalRows / $limit));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payouts Management - CarGo Admin</title>
  <?php
$page = basename($_SERVER['PHP_SELF']);

$favicons = [
 
  'payouts.php' => 'icons/payouts.svg',
  
];

$icon = $favicons[$page] ?? 'icons/dashboard.svg';
?>
<link rel="icon" type="image/svg+xml" href="/carGOAdmin/<?php echo $icon; ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <link href="include/notifications.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
  <?php include 'include/sidebar.php'; ?>

  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-cash-stack"></i>
        Payouts Management
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
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            <?= $pendingPayouts ?>
          </div>
        </div>
        <div class="stat-value"><?= formatCurrency($pendingAmount) ?></div>
        <div class="stat-label">Pending Payouts</div>
        <div class="stat-detail"><?= $pendingPayouts ?> owner<?= $pendingPayouts != 1 ? 's' : '' ?> waiting</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15%
          </div>
        </div>
        <div class="stat-value"><?= $completedThisMonth ?></div>
        <div class="stat-label">Completed This Month</div>
        <div class="stat-detail"><?= formatCurrency($paidThisMonth) ?> disbursed</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-graph-up"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">
          <?php
          $avgPayout = $completedThisMonth > 0 ? $paidThisMonth / $completedThisMonth : 0;
          echo formatCurrency($avgPayout);
          ?>
        </div>
        <div class="stat-label">Avg Payout Amount</div>
        <div class="stat-detail">Per transaction</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i>
            -2%
          </div>
        </div>
        <div class="stat-value">2.5 days</div>
        <div class="stat-label">Avg Processing Time</div>
        <div class="stat-detail">From release to payout</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search by owner, booking ID, or vehicle...">
          <i class="bi bi-search"></i>
        </div>

        <select class="filter-dropdown" id="statusFilter" onchange="filterPayouts()">
          <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Payouts</option>
          <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
          <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
          <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Payouts</option>
        </select>

        <button class="add-user-btn" onclick="exportPayouts()">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">
          <?php
          switch($statusFilter) {
            case 'pending': echo 'Pending Payouts'; break;
            case 'processing': echo 'Processing Payouts'; break;
            case 'completed': echo 'Completed Payouts'; break;
            case 'failed': echo 'Failed Payouts'; break;
            default: echo 'All Payouts';
          }
          ?>
        </h2>
        <div class="table-controls">
          <a href="?status=pending" class="table-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>">
            Pending (<?= $pendingPayouts ?>)
          </a>
          <a href="?status=completed" class="table-btn <?= $statusFilter === 'completed' ? 'active' : '' ?>">
            Completed
          </a>
        </div>
      </div>

      <div class="table-responsive">
        <?php if (mysqli_num_rows($result) == 0): ?>
        <div style="padding: 60px 20px; text-center;">
          <i class="bi bi-inbox" style="font-size: 64px; color: #ddd;"></i>
          <p style="margin-top: 20px; color: #999; font-size: 16px;">No payouts found</p>
        </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Payout ID</th>
              <th>Owner</th>
              <th>Booking</th>
              <th>Vehicle</th>
              <th>Amount Breakdown</th>
              <th>GCash Account</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): 
             $isFocused = $bookingFocus && $bookingFocus == $row['booking_id'];

              if ($statusFilter == 'pending') {
                // Pending payouts from bookings table
                $bookingId = $row['booking_id'];
                $ownerPayout = floatval($row['owner_payout']);
                $platformFee = floatval($row['platform_fee']);
                $totalAmount = floatval($row['total_amount']);
                $status = 'pending';
                $date = $row['escrow_released_at'];
                $payoutId = 'BK-' . str_pad($bookingId, 4, '0', STR_PAD_LEFT);
              } else {
                // Completed/processing payouts from payouts table
                $bookingId = $row['booking_id'];
                $ownerPayout = floatval($row['net_amount']);
                $platformFee = floatval($row['platform_fee']);
                $totalAmount = floatval($row['amount']);
                $status = $row['status'];
                $date = $row['processed_at'] ?? $row['created_at'];
                $payoutId = 'PO-' . str_pad($row['payout_id'], 4, '0', STR_PAD_LEFT);
              }
              
              $ownerName = htmlspecialchars($row['owner_name']);
              $ownerEmail = htmlspecialchars($row['owner_email']);
              $gcashNumber = $row['gcash_number'] ?? 'Not set';
              $gcashName = $row['gcash_name'] ?? 'Not set';
              $vehicleName = htmlspecialchars($row['brand'] . ' ' . $row['model'] . ' ' . $row['vehicle_year']);
              
              $statusClass = [
                'pending' => 'pending',
                'processing' => 'ongoing',
                'completed' => 'verified',
                'failed' => 'cancelled'
              ][$status] ?? 'pending';
            ?>
            <tr style="<?= $isFocused ? 'background:#fff3cd; border-left:5px solid #000;' : '' ?>">
              <td>
                <strong><?= $payoutId ?></strong><br>
                <small style="color:#999;">Booking #<?= $bookingId ?></small>
              </td>
              
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($ownerName) ?>&background=1a1a1a&color=fff" alt="Owner">
                  </div>
                  <div class="user-info">
                    <span class="user-name"><?= $ownerName ?></span>
                    <span class="user-email"><?= $ownerEmail ?></span>
                  </div>
                </div>
              </td>
              
              <td>
                <strong>#BK-<?= str_pad($bookingId, 4, '0', STR_PAD_LEFT) ?></strong><br>
                <small style="color:#999;">
                  <?= date('M d', strtotime($row['pickup_date'])) ?> - 
                  <?= date('M d, Y', strtotime($row['return_date'])) ?>
                </small>
              </td>
              
              <td>
                <strong><?= $vehicleName ?></strong><br>
                <small style="color:#999;"><?= ucfirst($row['vehicle_type']) ?></small>
              </td>
              
              <td>
                <div style="font-size: 12px;">
                  <div><strong style="color: #000;">Total: <?= formatCurrency($totalAmount) ?></strong></div>
                  <div style="color: #dc3545;">Fee: <?= formatCurrency($platformFee) ?> (10%)</div>
                  <div style="color: #28a745; font-weight: 600;">Net: <?= formatCurrency($ownerPayout) ?></div>
                </div>
              </td>
              
              <td>
                <?php if ($gcashNumber != 'Not set'): ?>
                <div style="font-size: 12px;">
                  <strong><?= htmlspecialchars($gcashNumber) ?></strong><br>
                  <small style="color:#999;"><?= htmlspecialchars($gcashName) ?></small>
                </div>
                <?php else: ?>
                <span style="color: #dc3545; font-size: 12px;">⚠️ Not configured</span>
                <?php endif; ?>
              </td>
              
              <td>
                <span class="status-badge <?= $statusClass ?>">
                  <?= ucfirst($status) ?>
                </span>
              </td>
              
              <td>
                <strong><?= date('M d, Y', strtotime($date)) ?></strong><br>
                <small style="color:#999;"><?= date('h:i A', strtotime($date)) ?></small>
              </td>
              
              <td>
                <div class="action-buttons">
                  <?php if ($status == 'pending'): ?>
                  <button class="action-btn approve" 
                          onclick='openPayoutModal(<?= json_encode([
                            "booking_id" => $bookingId,
                            "owner_name" => $ownerName,
                            "owner_payout" => $ownerPayout,
                            "gcash_number" => $gcashNumber,
                            "gcash_name" => $gcashName,
                            "vehicle_name" => $vehicleName
                          ]) ?>)'
                          title="Process Payout">
                    <i class="bi bi-cash-coin"></i>
                  </button>
                  <?php endif; ?>
                  
                  <button class="action-btn view" 
                          onclick='viewPayoutDetails(<?= json_encode($row) ?>)'
                          title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
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
          of <strong><?= $totalRows ?></strong> payout<?= $totalRows != 1 ? 's' : '' ?>
        </div>
        <div class="pagination-controls">
          <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>" class="page-btn">
            <i class="bi bi-chevron-left"></i>
          </a>
          <?php endif; ?>
          
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>" 
             class="page-btn <?= $i == $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>" class="page-btn">
            <i class="bi bi-chevron-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-cash-coin"></i>
          Complete Payout
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="payoutForm" onsubmit="submitPayout(event)">
        <div class="modal-body">
          <div id="payoutInfo" class="mb-4"></div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">GCash Reference Number</label>
            <input type="text" class="form-control" id="payoutReference" name="reference" required
                   placeholder="Enter GCash transaction reference" minlength="8">
            <small class="text-muted">Enter the 13-digit reference number from GCash</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">GCash Number (Optional Override)</label>
            <input type="text" class="form-control" id="gcashNumber" name="gcash_number" 
                   placeholder="09XX XXX XXXX" pattern="^09\d{9}$">
            <small class="text-muted">Leave empty to use owner's saved GCash number</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Proof of Transfer (Optional)</label>
            <input type="file" class="form-control" id="payoutProof" name="proof" accept="image/*">
            <small class="text-muted">Upload screenshot of GCash transfer</small>
          </div>
          
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Important:</strong> Make sure you've completed the GCash transfer before submitting.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="submitPayoutBtn">
            <i class="bi bi-check-circle"></i>
            Complete Payout
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" id="detailsContent"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentBookingId = null;

function openPayoutModal(payoutData) {
  currentBookingId = payoutData.booking_id;
  
  const infoHtml = `
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
      <h6 class="mb-3">Payout Details</h6>
      <div class="row">
        <div class="col-6">
          <small class="text-muted">Owner</small>
          <div class="fw-bold">${payoutData.owner_name}</div>
        </div>
        <div class="col-6">
          <small class="text-muted">Vehicle</small>
          <div class="fw-bold">${payoutData.vehicle_name}</div>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-6">
          <small class="text-muted">GCash Number</small>
          <div class="fw-bold">${payoutData.gcash_number}</div>
        </div>
        <div class="col-6">
          <small class="text-muted">Account Name</small>
          <div class="fw-bold">${payoutData.gcash_name}</div>
        </div>
      </div>
      <hr>
      <div class="text-center">
        <small class="text-muted">Amount to Transfer</small>
        <h3 class="text-success mb-0">₱${parseFloat(payoutData.owner_payout).toLocaleString('en-PH', {minimumFractionDigits: 2})}</h3>
      </div>
    </div>
  `;
  
  document.getElementById('payoutInfo').innerHTML = infoHtml;
  document.getElementById('gcashNumber').value = payoutData.gcash_number;
  
  const modal = new bootstrap.Modal(document.getElementById('payoutModal'));
  modal.show();
}

function submitPayout(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  formData.append('booking_id', currentBookingId);
  
  const submitBtn = document.getElementById('submitPayoutBtn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
  
  fetch('api/payment/complete_payout.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Complete Payout';
    
    if (data.success) {
      alert('✅ ' + data.message);
      bootstrap.Modal.getInstance(document.getElementById('payoutModal')).hide();
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Complete Payout';
    alert('Network error: ' + err.message);
  });
}

function viewPayoutDetails(data) {
  // Build details modal content
  const content = `
    <div class="modal-header">
      <h5 class="modal-title">Payout Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <h6>Owner Information</h6>
      <div class="mb-3">
        <strong>Name:</strong> ${data.owner_name}<br>
        <strong>Email:</strong> ${data.owner_email}<br>
        <strong>GCash:</strong> ${data.gcash_number || 'Not set'}<br>
        <strong>Account Name:</strong> ${data.gcash_name || 'Not set'}
      </div>
      
      <h6>Booking Information</h6>
      <div class="mb-3">
        <strong>Booking ID:</strong> #BK-${String(data.booking_id).padStart(4, '0')}<br>
        <strong>Dates:</strong> ${data.pickup_date} to ${data.return_date}
      </div>
      
      <h6>Payout Breakdown</h6>
      <table class="table table-sm">
        <tr>
          <td>Total Amount</td>
          <td class="text-end"><strong>₱${parseFloat(data.total_amount || data.amount || 0).toFixed(2)}</strong></td>
        </tr>
        <tr>
          <td>Platform Fee (10%)</td>
          <td class="text-end text-danger">-₱${parseFloat(data.platform_fee || 0).toFixed(2)}</td>
        </tr>
        <tr class="table-success">
          <td><strong>Net Payout</strong></td>
          <td class="text-end"><strong>₱${parseFloat(data.owner_payout || data.net_amount || 0).toFixed(2)}</strong></td>
        </tr>
      </table>
    </div>
  `;
  
  document.getElementById('detailsContent').innerHTML = content;
  new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function filterPayouts() {
  const status = document.getElementById('statusFilter').value;
  window.location.href = `payouts.php?status=${status}`;
}

function exportPayouts() {
  const status = document.getElementById('statusFilter').value;
  window.location.href = `export_payouts.php?status=${status}`;
}
</script>
<script src="include/notifications.js"></script>
</body>
</html>

<?php
function formatCurrency($amount) {
  return '₱' . number_format($amount, 2);
}

function formatNumber($number) {
  return number_format($number);
}

$conn->close();
?>