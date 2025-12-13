<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once "include/db.php";


$countPending = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS c FROM bookings WHERE status='pending'"
))['c'];

$countActive = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS c FROM bookings WHERE status='approved'"
))['c'];


// Count Pending
$pending = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='pending'")
)['c'];

// Count Confirmed / Approved
$confirmed = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='approved'")
)['c'];

// Count Ongoing
$ongoing = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='ongoing'")
)['c'];

// Count Cancelled / Rejected
$cancelled = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='rejected'")
)['c'];

// Pagination
$limit = 10;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$statusFilter  = isset($_GET["status"]) ? trim($_GET["status"]) : "";
$paymentFilter = isset($_GET["payment"]) ? trim($_GET["payment"]) : "";
$search        = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// Escape search
$searchEsc = mysqli_real_escape_string($conn, $search);

// -----------------------------
// BUILD WHERE CLAUSE
// -----------------------------
$where = " WHERE 1 ";

if ($statusFilter !== "" && $statusFilter !== "all") {
    $where .= " AND b.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "' ";
}

if ($search !== "") {
    $where .= "
        AND (
            u1.fullname LIKE '%$searchEsc%' OR
            u2.fullname LIKE '%$searchEsc%' OR
            c.brand LIKE '%$searchEsc%' OR
            c.model LIKE '%$searchEsc%'
        )
    ";
}

// -----------------------------
// MAIN BOOKING QUERY
// -----------------------------
$sql = "
SELECT 
    b.*,
    c.brand, c.model, c.car_year, c.daily_rate,
    u1.fullname AS renter_name,
    u2.fullname AS owner_name
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u1 ON b.user_id = u1.id
LEFT JOIN users u2 ON b.owner_id = u2.id
$where
ORDER BY b.created_at DESC
LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL ERROR: " . mysqli_error($conn) . "<br>Query: <pre>$sql</pre>");
}

// -----------------------------
// COUNT QUERY (FOR PAGINATION)
// SAME WHERE FILTERS
// -----------------------------
$countSql = "
SELECT COUNT(*) AS total
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u1 ON b.user_id = u1.id
LEFT JOIN users u2 ON b.owner_id = u2.id
$where
";

$countRes = mysqli_query($conn, $countSql);

if (!$countRes) {
    die("COUNT SQL ERROR: " . mysqli_error($conn) . "<br>Query: <pre>$countSql</pre>");
}

$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = max(1, ceil($totalRows / $limit));
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
  <!-- Sidebar -->
  <?php include 'include/sidebar.php' ?>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">Bookings Management</h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">7</span>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">

  <!-- Pending -->
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon">
        <i class="bi bi-clock-history"></i>
      </div>
      <div class="stat-trend">
        <i class="bi bi-arrow-up"></i>
        +12%
      </div>
    </div>
    <div class="stat-value"><?= $pending ?></div>
    <div class="stat-label">Pending Bookings</div>
  </div>

  <!-- Confirmed -->
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon">
        <i class="bi bi-check-circle"></i>
      </div>
      <div class="stat-trend">
        <i class="bi bi-arrow-up"></i>
        +18%
      </div>
    </div>
    <div class="stat-value"><?= $confirmed ?></div>
    <div class="stat-label">Confirmed Bookings</div>
  </div>

  <!-- Ongoing -->
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon">
        <i class="bi bi-car-front-fill"></i>
      </div>
      <div class="stat-trend">
        <i class="bi bi-arrow-up"></i>
        +8%
      </div>
    </div>
    <div class="stat-value"><?= $ongoing ?></div>
    <div class="stat-label">Ongoing Rentals</div>
  </div>

  <!-- Cancelled -->
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon">
        <i class="bi bi-x-circle"></i>
      </div>
      <div class="stat-trend down">
        <i class="bi bi-arrow-down"></i>
        -5%
      </div>
    </div>
    <div class="stat-value"><?= $cancelled ?></div>
    <div class="stat-label">Cancelled Bookings</div>
  </div>

</div>

    <div class="filter-section">
  <div class="filter-row">

    <div class="search-box">
      <input type="text" id="searchInput" placeholder="Search by renter, owner, or car type...">
      <i class="bi bi-search"></i>
    </div>

    <select class="filter-dropdown" id="statusFilter">
      <option value="">All Status</option>
      <option value="pending">Pending</option>
      <option value="approved">Confirmed</option>
      <option value="ongoing">Ongoing</option>
      <option value="completed">Completed</option>
      <option value="cancelled">Cancelled</option>
    </select>

    <select class="filter-dropdown" id="paymentFilter">
      <option value="">Payment Status</option>
      <option value="paid">Paid</option>
      <option value="unpaid">Unpaid</option>
      <option value="partial">Partial</option>
    </select>

    <select class="filter-dropdown" id="dateFilter">
      <option value="">This Month</option>
      <option value="last_month">Last Month</option>
      <option value="3_months">Last 3 Months</option>
      <option value="year">This Year</option>
    </select>

    <button class="export-btn" onclick="exportBookings()">
      <i class="bi bi-download"></i>
      Export
    </button>

  </div>
</div>

    <!-- Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">All Bookings</h2>
        <div class="table-controls">

    <!-- ALL -->
    <a href="bookings.php" class="table-btn <?= ($statusFilter == "" || $statusFilter == "all") ? 'active' : '' ?>">
        All (<?= $totalRows ?>)
    </a>

    <!-- PENDING -->
    <a href="bookings.php?status=pending" class="table-btn <?= ($statusFilter == "pending") ? 'active' : '' ?>">
        Pending (<?= $countPending ?>)
    </a>

    <!-- ACTIVE = APPROVED / ONGOING -->
    <a href="bookings.php?status=approved" class="table-btn <?= ($statusFilter == "approved") ? 'active' : '' ?>">
        Active (<?= $countActive ?>)
    </a>

</div>

      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Renter</th>
              <th>Owner</th>
              <th>Car Details</th>
              <th>Rental Period</th>
              <th>Location</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="bookingsTableBody">
<?php 
if (mysqli_num_rows($result) == 0) { ?>
    <tr>
        <td colspan="10" class="text-center py-4">
            <strong>No bookings found.</strong>
        </td>
    </tr>
<?php }

while ($row = mysqli_fetch_assoc($result)): 
    $bookingId = "#BK-" . str_pad($row['id'], 4, "0", STR_PAD_LEFT);
    $renter = $row['renter_name'];
    $owner  = $row['owner_name'];
    $car    = $row['brand'] . " " . $row['model'];
    $dateRange = date("M d", strtotime($row['pickup_date'])) . 
                 " - " . 
                 date("M d", strtotime($row['return_date']));

    // Status color classes
    $statusClass = [
        "pending" => "pending",
        "approved" => "confirmed",
        "rejected" => "cancelled"
    ][$row["status"]];

    $paymentClass = "unpaid"; // default
?>
    <tr>
        <td><strong><?= $bookingId ?></strong></td>

        <!-- Renter -->
        <td>
            <div class="user-cell">
                <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($renter) ?>&background=1a1a1a&color=fff">
                </div>
                <span><?= $renter ?></span>
            </div>
        </td>

        <!-- Owner -->
        <td>
            <div class="user-cell">
                <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($owner) ?>&background=1a1a1a&color=fff">
                </div>
                <span><?= $owner ?></span>
            </div>
        </td>

        <!-- Car Details -->
        <td>
            <strong><?= $car ?></strong><br>
            <small style="color:#999;"><?= $row['car_year'] ?></small>
        </td>

        <!-- Rental Period -->
        <td>
            <strong><?= $dateRange ?></strong><br>
            <small style="color:#999;"><?= $row['rental_period'] ?></small>
        </td>

        <!-- Pickup Location -->
        <td>
            <strong><?= $row['pickup_date'] ?></strong><br>
            <small style="color:#999;"><?= $row['pickup_time'] ?></small>
        </td>

        <!-- Amount -->
        <td><strong>₱<?= number_format($row['total_amount'], 2) ?></strong></td>

        <!-- Status -->
        <td>
            <span class="status-badge <?= $statusClass ?>">
                <?= ucfirst($row['status']) ?>
            </span>
        </td>

        <!-- Payment -->
        <td>
            <span class="payment-badge <?= $paymentClass ?>">Unpaid</span>
        </td>

        <!-- Actions -->
        <td>
            <div class="action-buttons">

                <!-- View (Modal) -->
                <button 
                    class="action-btn view" 
                    data-id="<?= $row['id'] ?>" 
                    onclick="openBookingModal(<?= $row['id'] ?>)">
                    <i class="bi bi-eye"></i>
                </button>

               

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
        Showing 
        <strong><?= $offset + 1 ?></strong> -
        <strong><?= min($offset + $limit, $totalRows) ?></strong>
        of 
        <strong><?= $totalRows ?></strong>
        bookings
    </div>

    <div class="pagination-controls">

        <!-- Previous -->
        <a href="?page=<?= max(1, $page - 1) ?>" class="page-btn">
            <i class="bi bi-chevron-left"></i>
        </a>

        <!-- Page buttons (keep the SAME UI) -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" 
               class="page-btn <?= ($i == $page) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Next -->
        <a href="?page=<?= min($totalPages, $page + 1) ?>" class="page-btn">
            <i class="bi bi-chevron-right"></i>
        </a>

    </div>
</div>


    </div>
</div>

  </main>
</div>


<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" id="modalContent">
      


    </div>
  </div>
</div>

<!-- Booking Details Modal -->

<script>
function openBookingModal(id) {
    fetch("fetch_booking_details.php?id=" + id)
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to load modal content");
            }
            return response.text();
        })
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;
            let modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
        })
        .catch(err => {
            console.error(err);
            alert("Error loading booking details.");
        });
}
</script>

<script>
function loadBookings() {

    let search  = document.getElementById("searchInput").value;
    let status  = document.getElementById("statusFilter").value;
    let payment = document.getElementById("paymentFilter").value;
    let date    = document.getElementById("dateFilter").value;

    let params = new URLSearchParams({
        search: search,
        status: status,
        payment: payment,
        date: date
    });

    fetch("bookings_table.php?" + params.toString())
    .then(r => r.text())
    .then(html => {
        document.getElementById("bookingsTableBody").innerHTML = html;
    });
}

// Live search
document.getElementById("searchInput").addEventListener("keyup", loadBookings);

// Dropdown filters
document.getElementById("statusFilter").addEventListener("change", loadBookings);
document.getElementById("paymentFilter").addEventListener("change", loadBookings);
document.getElementById("dateFilter").addEventListener("change", loadBookings);
</script>
<script>
function exportBookings() {

    let search  = document.getElementById("searchInput").value;
    let status  = document.getElementById("statusFilter").value;
    let payment = document.getElementById("paymentFilter").value;
    let date    = document.getElementById("dateFilter").value;

    let params = new URLSearchParams({
        search: search,
        status: status,
        payment: payment,
        date: date
    });

    window.location.href = "export_bookings.php?" + params.toString();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>