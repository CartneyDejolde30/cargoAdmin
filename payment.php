<?php
require_once "include/db.php";

// Get payment statistics
$pendingVerification = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS c FROM payments WHERE payment_status='pending'"
))['c'];

$escrowed = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(amount) AS total FROM escrow WHERE status='held'"
))['total'] ?? 0;

$pendingPayouts = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS c FROM payouts WHERE status IN ('pending', 'processing')"
))['c'];

$completedPayouts = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(net_amount) AS total FROM payouts WHERE status='completed'"
))['total'] ?? 0;

// Pagination
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$statusFilter = isset($_GET["status"]) ? trim($_GET["status"]) : "";

// Build WHERE clause
$where = " WHERE 1 ";
if ($statusFilter !== "" && $statusFilter !== "all") {
    $where .= " AND p.payment_status = '" . mysqli_real_escape_string($conn, $statusFilter) . "' ";
}

// Main payment query
$sql = "SELECT 
    p.*,
    b.id as booking_id,
    b.status as booking_status,
    b.payment_status as booking_payment_status,
    b.escrow_status,
    b.payout_status,
    b.platform_fee,
    b.owner_payout,
    u1.fullname AS renter_name,
    u2.fullname AS owner_name,
    c.brand, c.model
FROM payments p
INNER JOIN bookings b ON p.booking_id = b.id
INNER JOIN users u1 ON p.user_id = u1.id
INNER JOIN users u2 ON b.owner_id = u2.id
INNER JOIN cars c ON b.car_id = c.id
$where
ORDER BY p.created_at DESC
LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $sql);

// Count query
$countSql = "SELECT COUNT(*) AS total FROM payments p $where";
$countRes = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = max(1, ceil($totalRows / $limit));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
  <?php include 'include/sidebar.php' ?>

  <main class="main-content">
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-credit-card"></i>
        Payment Management
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge"><?= $pendingVerification ?></span>
        </button>
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
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Urgent
          </div>
        </div>
        <div class="stat-value"><?= $pendingVerification ?></div>
        <div class="stat-label">Pending Verification</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Secured
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($escrowed, 2) ?></div>
        <div class="stat-label">Funds in Escrow</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Processing
          </div>
        </div>
        <div class="stat-value"><?= $pendingPayouts ?></div>
        <div class="stat-label">Pending Payouts</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +18%
          </div>
        </div>
        <div class="stat-value">₱<?= number_format($completedPayouts, 2) ?></div>
        <div class="stat-label">Total Payouts</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="search-box">
          <input type="text" placeholder="Search by reference, customer, or car...">
          <i class="bi bi-search"></i>
        </div>
        <select class="filter-dropdown" id="statusFilter" onchange="filterPayments()">
          <option value="">All Payments</option>
          <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
          <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
          <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
        <button class="export-btn">
          <i class="bi bi-download"></i>
          Export
        </button>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Payment Transactions</h2>
        <div class="table-controls">
          <a href="payments.php" class="table-btn <?= ($statusFilter == "") ? 'active' : '' ?>">
            All (<?= $totalRows ?>)
          </a>
          <a href="payments.php?status=pending" class="table-btn <?= ($statusFilter == "pending") ? 'active' : '' ?>">
            Pending (<?= $pendingVerification ?>)
          </a>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Payment ID</th>
              <th>Booking</th>
              <th>Renter</th>
              <th>Owner</th>
              <th>Amount</th>
              <th>Fee Split</th>
              <th>Payment Status</th>
              <th>Escrow</th>
              <th>Payout</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
<?php while ($row = mysqli_fetch_assoc($result)): 
    $paymentId = "#PAY-" . str_pad($row['id'], 4, "0", STR_PAD_LEFT);
    $bookingId = "#BK-" . str_pad($row['booking_id'], 4, "0", STR_PAD_LEFT);
?>
            <tr>
              <td><strong><?= $paymentId ?></strong><br>
                  <small style="color:#999;"><?= date("M d, Y", strtotime($row['created_at'])) ?></small>
              </td>
              <td><strong><?= $bookingId ?></strong><br>
                  <small style="color:#999;"><?= $row['brand'] . " " . $row['model'] ?></small>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['renter_name']) ?>&background=1a1a1a&color=fff">
                  </div>
                  <span><?= $row['renter_name'] ?></span>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['owner_name']) ?>&background=1a1a1a&color=fff">
                  </div>
                  <span><?= $row['owner_name'] ?></span>
                </div>
              </td>
              <td><strong>₱<?= number_format($row['amount'], 2) ?></strong><br>
                  <small style="color:#999;"><?= $row['payment_method'] ?></small>
              </td>
              <td>
                <div style="font-size: 11px;">
                  <div>Platform: <strong>₱<?= number_format($row['platform_fee'], 2) ?></strong></div>
                  <div>Owner: <strong>₱<?= number_format($row['owner_payout'], 2) ?></strong></div>
                </div>
              </td>
              <td>
                <?php
                $statusClass = [
                    "pending" => "pending",
                    "verified" => "confirmed",
                    "failed" => "cancelled"
                ][$row["payment_status"]];
                ?>
                <span class="status-badge <?= $statusClass ?>">
                    <?= ucfirst($row['payment_status']) ?>
                </span>
              </td>
              <td>
                <?php
                $escrowClass = [
                    "pending" => "pending",
                    "held" => "confirmed",
                    "released" => "approved"
                ][$row["escrow_status"]] ?? "pending";
                ?>
                <span class="status-badge <?= $escrowClass ?>">
                    <?= ucfirst($row['escrow_status']) ?>
                </span>
              </td>
              <td>
                <?php
                $payoutClass = [
                    "pending" => "pending",
                    "processing" => "pending",
                    "completed" => "approved",
                    "failed" => "cancelled"
                ][$row["payout_status"]];
                ?>
                <span class="status-badge <?= $payoutClass ?>">
                    <?= ucfirst($row['payout_status']) ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" onclick="viewPaymentDetails(<?= $row['id'] ?>)">
                    <i class="bi bi-eye"></i>
                  </button>
                  <?php if ($row['payment_status'] === 'pending'): ?>
                    <button class="action-btn approve" onclick="verifyPayment(<?= $row['id'] ?>, 'verify')">
                      <i class="bi bi-check"></i>
                    </button>
                    <button class="action-btn reject" onclick="verifyPayment(<?= $row['id'] ?>, 'reject')">
                      <i class="bi bi-x"></i>
                    </button>
                  <?php endif; ?>
                  <?php if ($row['escrow_status'] === 'held' && $row['booking_status'] === 'completed'): ?>
                    <button class="action-btn approve" onclick="releaseEscrow(<?= $row['booking_id'] ?>)">
                      <i class="bi bi-unlock"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
<?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong><?= $offset + 1 ?></strong> - <strong><?= min($offset + $limit, $totalRows) ?></strong>
          of <strong><?= $totalRows ?></strong> payments
        </div>
        <div class="pagination-controls">
          <a href="?page=<?= max(1, $page - 1) ?>" class="page-btn">
            <i class="bi bi-chevron-left"></i>
          </a>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= ($i == $page) ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
          <a href="?page=<?= min($totalPages, $page + 1) ?>" class="page-btn">
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterPayments() {
    const status = document.getElementById('statusFilter').value;
    window.location.href = `payments.php?status=${status}`;
}

function verifyPayment(paymentId, action) {
    const message = action === 'verify' ? 
        'Are you sure you want to verify this payment?' : 
        'Are you sure you want to reject this payment?';
    
    if (!confirm(message)) return;

    fetch('api/verify_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `payment_id=${paymentId}&action=${action}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(err => alert('Error: ' + err));
}

function releaseEscrow(bookingId) {
    if (!confirm('Release escrow and process payout to owner?')) return;

    fetch('api/release_escrow.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `booking_id=${bookingId}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(err => alert('Error: ' + err));
}

function viewPaymentDetails(paymentId) {
    // Implement modal view for payment details
    alert('View payment details for ID: ' + paymentId);
}
</script>
</body>
</html>