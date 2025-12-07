<<<<<<< HEAD
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



=======
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<<<<<<< HEAD
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f8f9fa;
      min-height: 100vh;
    }

    .dashboard-wrapper {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 260px;
      background: white;
      padding: 30px 20px;
      box-shadow: 2px 0 10px rgba(0,0,0,0.05);
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 40px;
      padding: 0 10px;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      background: #1a1a1a;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
      font-weight: 800;
    }

    .logo-text {
      font-size: 20px;
      font-weight: 800;
      color: #1a1a1a;
      letter-spacing: 2px;
    }

    .menu-section {
      margin-bottom: 35px;
    }

    .menu-label {
      font-size: 11px;
      font-weight: 600;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 0 10px;
      margin-bottom: 12px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 16px;
      border-radius: 12px;
      color: #666;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 6px;
    }

    .menu-item:hover {
      background: #f5f5f5;
      color: #1a1a1a;
    }

    .menu-item.active {
      background: #1a1a1a;
      color: white;
    }

    .menu-item i {
      font-size: 18px;
      width: 20px;
      text-align: center;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 260px;
      padding: 30px 40px;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 35px;
    }

    .page-title {
      font-size: 28px;
      font-weight: 800;
      color: #1a1a1a;
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .notification-btn {
      width: 45px;
      height: 45px;
      background: white;
      border: none;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #666;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      position: relative;
    }

    .notification-btn:hover {
      background: #f5f5f5;
    }

    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 10px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .user-avatar {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid #1a1a1a;
    }

    .user-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 18px;
      padding: 25px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: #1a1a1a;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      background: #f5f5f5;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: #1a1a1a;
    }

    .stat-trend {
      font-size: 12px;
      color: #28a745;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .stat-trend.down {
      color: #dc3545;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 800;
      color: #1a1a1a;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
      font-weight: 500;
    }

    /* Filter Section */
    .filter-section {
      background: white;
      border-radius: 18px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .filter-row {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: center;
    }

    .search-box {
      flex: 1;
      min-width: 300px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 12px 45px 12px 20px;
      border: 2px solid #f0f0f0;
      border-radius: 12px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .search-box i {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
      font-size: 16px;
    }

    .filter-dropdown {
      padding: 12px 20px;
      border: 2px solid #f0f0f0;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
    }

    .filter-dropdown:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .export-btn {
      padding: 12px 24px;
      background: #1a1a1a;
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .export-btn:hover {
      background: #000;
      transform: translateY(-2px);
    }

    /* Table Section */
    .table-section {
      background: white;
      border-radius: 18px;
      padding: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 30px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .section-title {
      font-size: 20px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .table-controls {
      display: flex;
      gap: 12px;
    }

    .table-btn {
      padding: 10px 18px;
      background: #f5f5f5;
      border: none;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      color: #666;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .table-btn:hover {
      background: #e8e8e8;
      color: #1a1a1a;
    }

    .table-btn.active {
      background: #1a1a1a;
      color: white;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    thead th {
      background: #f8f9fa;
      padding: 16px;
      text-align: left;
      font-size: 12px;
      font-weight: 700;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border: none;
    }

    thead th:first-child {
      border-radius: 10px 0 0 10px;
    }

    thead th:last-child {
      border-radius: 0 10px 10px 0;
    }

    tbody td {
      padding: 18px 16px;
      border-bottom: 1px solid #f0f0f0;
      font-size: 14px;
      color: #1a1a1a;
      vertical-align: middle;
    }

    tbody tr:hover {
      background: #f8f9fa;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    .user-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar-small {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      overflow: hidden;
    }

    .user-avatar-small img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .status-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge.confirmed {
      background: #d1ecf1;
      color: #0c5460;
    }

    .status-badge.ongoing {
      background: #cfe2ff;
      color: #084298;
    }

    .status-badge.completed {
      background: #d1e7dd;
      color: #0f5132;
    }

    .status-badge.cancelled {
      background: #f8d7da;
      color: #842029;
    }

    .payment-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .payment-badge.paid {
      background: #d1e7dd;
      color: #0f5132;
    }

    .payment-badge.unpaid {
      background: #f8d7da;
      color: #842029;
    }

    .payment-badge.partial {
      background: #fff3cd;
      color: #856404;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .action-btn {
      width: 35px;
      height: 35px;
      border-radius: 8px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 16px;
    }

    .action-btn.view {
      background: #e3f2fd;
      color: #1976d2;
    }

    .action-btn.view:hover {
      background: #1976d2;
      color: white;
    }

    .action-btn.approve {
      background: #e8f5e9;
      color: #388e3c;
    }

    .action-btn.approve:hover {
      background: #388e3c;
      color: white;
    }

    .action-btn.reject {
      background: #ffebee;
      color: #d32f2f;
    }

    .action-btn.reject:hover {
      background: #d32f2f;
      color: white;
    }

    /* Modal Styles */
    .modal-content {
      border-radius: 18px;
      border: none;
      overflow: hidden;
    }

    .modal-header {
      background: #1a1a1a;
      color: white;
      padding: 25px 30px;
      border: none;
    }

    .modal-title {
      font-weight: 700;
      font-size: 20px;
    }

    .btn-close {
      filter: brightness(0) invert(1);
    }

    .modal-body {
      padding: 30px;
    }

    .detail-section {
      margin-bottom: 30px;
    }

    .detail-section:last-child {
      margin-bottom: 0;
    }

    .detail-section-title {
      font-size: 14px;
      font-weight: 700;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 15px;
    }

    .detail-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 15px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .detail-label {
      font-size: 12px;
      color: #999;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 15px;
      color: #1a1a1a;
      font-weight: 600;
    }

    .timeline {
      position: relative;
      padding-left: 30px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 8px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #e0e0e0;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 20px;
    }

    .timeline-item:last-child {
      margin-bottom: 0;
    }

    .timeline-dot {
      position: absolute;
      left: -26px;
      top: 2px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: white;
      border: 3px solid #1a1a1a;
    }

    .timeline-content {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 10px;
    }

    .timeline-title {
      font-size: 14px;
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 5px;
    }

    .timeline-time {
      font-size: 12px;
      color: #999;
    }

    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #f0f0f0;
      gap: 10px;
    }

    .modal-btn {
      padding: 12px 28px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .modal-btn.secondary {
      background: #f5f5f5;
      color: #666;
    }

    .modal-btn.secondary:hover {
      background: #e8e8e8;
    }

    .modal-btn.approve {
      background: #28a745;
      color: white;
    }

    .modal-btn.approve:hover {
      background: #218838;
    }

    .modal-btn.reject {
      background: #dc3545;
      color: white;
    }

    .modal-btn.reject:hover {
      background: #c82333;
    }

    /* Pagination */
    .pagination-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #f0f0f0;
    }

    .pagination-info {
      font-size: 14px;
      color: #666;
    }

    .pagination-controls {
      display: flex;
      gap: 8px;
    }

    .page-btn {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      background: white;
      color: #666;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .page-btn:hover {
      background: #f5f5f5;
      border-color: #1a1a1a;
    }

    .page-btn.active {
      background: #1a1a1a;
      color: white;
      border-color: #1a1a1a;
    }

    @media (max-width: 1400px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .main-content {
        margin-left: 0;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .filter-row {
        flex-direction: column;
      }

      .search-box {
        width: 100%;
      }
    }
  </style>
=======
  <link href="include/admin-styles.css" rel="stylesheet">
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
</head>
<body>

<div class="dashboard-wrapper">
<<<<<<< HEAD
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo-section">
      <div class="logo-icon">C</div>
      <div class="logo-text">CARGO</div>
    </div>

    <div class="menu-section">
      <div class="menu-label">About Car</div>
      <a href="dashboard.php" class="menu-item">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
      <a href="get_cars_admin.php" class="menu-item">
        <i class="bi bi-car-front"></i>
        <span>Car Listing</span>
      </a>
      <a href="users.php" class="menu-item">
        <i class="bi bi-person"></i>
        <span>Users Verification</span>
      </a>
      <a href="bookings.php" class="menu-item active">
        <i class="bi bi-book"></i>
        <span>Bookings</span>
      </a>
    </div>

    <div class="menu-section">
      <div class="menu-label">Report</div>
      <a href="sales-statistics.php" class="menu-item">
        <i class="bi bi-bar-chart"></i>
        <span>Sales Statistics</span>
      </a>
      <a href="car-reports.php" class="menu-item">
        <i class="bi bi-file-text"></i>
        <span>Car Reports</span>
      </a>
    </div>

    <div class="menu-section">
      <a href="settings.php" class="menu-item">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </a>
      <a href="logout.php" class="menu-item" style="color: #dc3545; margin-top: 20px;">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">Bookings Management</h1>
=======
  <?php include('include/sidebar.php'); ?>

  <main class="main-content">
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-book"></i>
        Bookings Management
      </h1>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
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

<<<<<<< HEAD
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
=======
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12%
          </div>
        </div>
        <div class="stat-value">23</div>
        <div class="stat-label">Pending Bookings</div>
        <div class="stat-detail">Requires immediate attention</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +18%
          </div>
        </div>
        <div class="stat-value">156</div>
        <div class="stat-label">Confirmed Bookings</div>
        <div class="stat-detail">Ready for pickup</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-car-front-fill"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">42</div>
        <div class="stat-label">Ongoing Rentals</div>
        <div class="stat-detail">Currently active</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +25%
          </div>
        </div>
        <div class="stat-value">₱524K</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">This month</div>
      </div>
    </div>

    <div class="quick-stats">
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #fff8e1; color: #f57c00;">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="quick-stat-value">5</div>
        <div class="quick-stat-label">Overdue Bookings</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #e8f5e9; color: #2e7d32;">
          <i class="bi bi-star"></i>
        </div>
        <div class="quick-stat-value">4.8</div>
        <div class="quick-stat-label">Average Rating</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #f3e5f5; color: #7b1fa2;">
          <i class="bi bi-arrow-repeat"></i>
        </div>
        <div class="quick-stat-value">12</div>
        <div class="quick-stat-label">Repeat Customers</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #ffebee; color: #d32f2f;">
          <i class="bi bi-x-circle"></i>
        </div>
        <div class="quick-stat-value">8</div>
        <div class="quick-stat-label">Cancelled Today</div>
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
          <option value="confirmed">Confirmed</option>
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
          <option value="">Date Range</option>
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="year">This Year</option>
        </select>
        <button class="export-btn" onclick="exportBookings()">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </div>
    </div>

>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">All Bookings</h2>
        <div class="table-controls">
<<<<<<< HEAD

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
=======
          <button class="table-btn active" onclick="filterTable('all')">All (229)</button>
          <button class="table-btn" onclick="filterTable('pending')">Pending (23)</button>
          <button class="table-btn" onclick="filterTable('ongoing')">Active (42)</button>
          <button class="table-btn" onclick="filterTable('completed')">Completed (156)</button>
        </div>
      </div>

      <div class="table-responsive">
        <table id="bookingsTable">
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
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
<<<<<<< HEAD
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

                <?php if ($row['status'] == "pending") : ?>
                <button class="action-btn approve" onclick="updateStatus(<?= $row['id'] ?>,'approved')">
                    <i class="bi bi-check-lg"></i>
                </button>

                <button class="action-btn reject" onclick="updateStatus(<?= $row['id'] ?>,'rejected')">
                    <i class="bi bi-x-lg"></i>
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
      


=======
          <tbody>
            <tr data-status="pending" data-payment="unpaid">
              <td><strong>#BK-2451</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="Juan">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Pedro+Santos&background=1a1a1a&color=fff" alt="Pedro">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Pedro Santos</span>
                    <span class="user-email">pedro@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Toyota Vios</strong><br>
                  <small style="color: #999;">Sedan • 2022 • ABC 1234</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 20 - Nov 25</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>San Francisco</strong><br>
                  <small style="color: #999;">to Butuan City</small>
                </div>
              </td>
              <td><strong>₱5,000</strong></td>
              <td><span class="status-badge pending">Pending</span></td>
              <td><span class="payment-badge unpaid">Unpaid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#bookingModal1" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn approve" title="Approve">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button class="action-btn reject" title="Reject">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="confirmed" data-payment="paid">
              <td><strong>#BK-2450</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Maria+Garcia&background=1a1a1a&color=fff" alt="Maria">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Maria Garcia</span>
                    <span class="user-email">maria@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Reyes&background=1a1a1a&color=fff" alt="Carlos">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Carlos Reyes</span>
                    <span class="user-email">carlos@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Honda City</strong><br>
                  <small style="color: #999;">Sedan • 2023 • DEF 5678</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 22 - Nov 27</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Davao City</strong><br>
                  <small style="color: #999;">to Cagayan de Oro</small>
                </div>
              </td>
              <td><strong>₱6,500</strong></td>
              <td><span class="status-badge confirmed">Confirmed</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#bookingModal2" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn delete" title="Cancel">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="ongoing" data-payment="paid">
              <td><strong>#BK-2449</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Robert+Tan&background=1a1a1a&color=fff" alt="Robert">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Robert Tan</span>
                    <span class="user-email">robert@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Anna+Lopez&background=1a1a1a&color=fff" alt="Anna">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Anna Lopez</span>
                    <span class="user-email">anna@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Mitsubishi Montero</strong><br>
                  <small style="color: #999;">SUV • 2021 • GHI 9012</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 18 - Nov 24</strong><br>
                  <small style="color: #999;">6 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Manila</strong><br>
                  <small style="color: #999;">to Baguio City</small>
                </div>
              </td>
              <td><strong>₱12,000</strong></td>
              <td><span class="status-badge ongoing">Ongoing</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn" style="background: #e3f2fd; color: #1976d2;" title="Track">
                    <i class="bi bi-geo-alt"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="completed" data-payment="paid">
              <td><strong>#BK-2448</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Lisa+Ramos&background=1a1a1a&color=fff" alt="Lisa">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Lisa Ramos</span>
                    <span class="user-email">lisa@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mark+Santos&background=1a1a1a&color=fff" alt="Mark">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mark Santos</span>
                    <span class="user-email">mark@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Toyota Fortuner</strong><br>
                  <small style="color: #999;">SUV • 2022 • JKL 3456</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 15 - Nov 20</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Cebu City</strong><br>
                  <small style="color: #999;">to Bohol</small>
                </div>
              </td>
              <td><strong>₱9,000</strong></td>
              <td><span class="status-badge completed">Completed</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn" style="background: #fff3e0; color: #ef6c00;" title="Generate Invoice">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="cancelled" data-payment="unpaid">
              <td><strong>#BK-2447</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=David+Cruz&background=1a1a1a&color=fff" alt="David">
                  </div>
                  <div class="user-info">
                    <span class="user-name">David Cruz</span>
                    <span class="user-email">david@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Sofia+Martin&background=1a1a1a&color=fff" alt="Sofia">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Sofia Martin</span>
                    <span class="user-email">sofia@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nissan Navara</strong><br>
                  <small style="color: #999;">Pickup • 2023 • MNO 7890</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 10 - Nov 12</strong><br>
                  <small style="color: #999;">2 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Iloilo City</strong><br>
                  <small style="color: #999;">to Bacolod City</small>
                </div>
              </td>
              <td><strong>₱3,500</strong></td>
              <td><span class="status-badge cancelled">Cancelled</span></td>
              <td><span class="payment-badge unpaid">Refunded</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong>1-5</strong> of <strong>229</strong> bookings
        </div>
        <div class="pagination-controls">
          <button class="page-btn"><i class="bi bi-chevron-left"></i></button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn">4</button>
          <button class="page-btn">5</button>
          <button class="page-btn"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal1" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Booking Details - #BK-2451</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="detail-section">
          <div class="detail-section-title">Booking Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Booking ID</span>
              <span class="detail-value">#BK-2451</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Booking Date</span>
              <span class="detail-value">November 15, 2025 - 10:30 AM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status</span>
              <span class="status-badge pending">Pending Approval</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Payment Status</span>
              <span class="payment-badge unpaid">Awaiting Payment</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Renter Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Full Name</span>
              <span class="detail-value">Juan Dela Cruz</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email</span>
              <span class="detail-value">juan.delacruz@email.com</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phone Number</span>
              <span class="detail-value">+63 912 345 6789</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Driver's License</span>
              <span class="detail-value">N01-12-345678</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Vehicle Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Vehicle Make & Model</span>
              <span class="detail-value">Toyota Vios</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Year</span>
              <span class="detail-value">2022</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Plate Number</span>
              <span class="detail-value">ABC 1234</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Vehicle Type</span>
              <span class="detail-value">Sedan</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Rental Details</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Pickup Date & Time</span>
              <span class="detail-value">November 20, 2025 - 9:00 AM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Drop-off Date & Time</span>
              <span class="detail-value">November 25, 2025 - 6:00 PM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Rental Duration</span>
              <span class="detail-value">5 Days</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Daily Rate</span>
              <span class="detail-value">₱1,000.00</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Payment Breakdown</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Base Rental (5 days × ₱1,000)</span>
              <span class="detail-value">₱5,000.00</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Service Fee (10%)</span>
              <span class="detail-value">₱500.00</span>
            </div>
            <div class="detail-item">
              <span class="detail-label" style="font-size: 14px; color: #1a1a1a;">Total Amount</span>
              <span class="detail-value" style="font-size: 24px; color: #1a1a1a;">₱5,500.00</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Activity Timeline</div>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <div class="timeline-title">Booking Created</div>
                <div class="timeline-time">November 15, 2025 - 10:30 AM</div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot" style="border-color: #dc3545; background: #dc3545;"></div>
              <div class="timeline-content">
                <div class="timeline-title">Pending Admin Review</div>
                <div class="timeline-time">Current Status</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-btn secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="modal-btn reject">Reject Booking</button>
        <button type="button" class="modal-btn approve">Approve Booking</button>
      </div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
    </div>
  </div>
</div>

<<<<<<< HEAD
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
=======
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter table by status
function filterTable(status) {
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  const buttons = document.querySelectorAll('.table-btn');
  
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  rows.forEach(row => {
    if (status === 'all') {
      row.style.display = '';
    } else {
      const rowStatus = row.getAttribute('data-status');
      row.style.display = rowStatus === status ? '' : 'none';
    }
  });
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function(e) {
  const status = e.target.value;
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    if (!status) {
      row.style.display = '';
    } else {
      const rowStatus = row.getAttribute('data-status');
      row.style.display = rowStatus === status ? '' : 'none';
    }
  });
});

// Payment filter
document.getElementById('paymentFilter').addEventListener('change', function(e) {
  const payment = e.target.value;
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    if (!payment) {
      row.style.display = '';
    } else {
      const rowPayment = row.getAttribute('data-payment');
      row.style.display = rowPayment === payment ? '' : 'none';
    }
  });
});

// Export bookings
function exportBookings() {
  alert('Exporting bookings report...\n\nThis will generate a CSV file with all booking data.');
  // Add actual export functionality here
}

// Notification on page load
window.addEventListener('load', function() {
  console.log('Bookings Management System loaded successfully');
});
</script>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
</body>
</html>