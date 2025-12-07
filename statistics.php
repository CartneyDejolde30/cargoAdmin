<<<<<<< HEAD
=======
<?php
// Include database connection and session check if needed
// session_start();
// require_once 'config.php';
?>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
  <title>User Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

    /* Stats Grid */
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

    .add-user-btn {
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

    .add-user-btn:hover {
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
      width: 42px;
      height: 42px;
      border-radius: 10px;
      overflow: hidden;
      border: 2px solid #f0f0f0;
    }

    .user-avatar-small img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .user-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .user-name {
      font-weight: 600;
      color: #1a1a1a;
    }

    .user-email {
      font-size: 12px;
      color: #999;
    }

    .role-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .role-badge.owner {
      background: #e3f2fd;
      color: #1976d2;
    }

    .role-badge.renter {
      background: #e8f5e9;
      color: #388e3c;
    }

    .role-badge.both {
      background: #f3e5f5;
      color: #7b1fa2;
    }

    .status-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.verified {
      background: #d1e7dd;
      color: #0f5132;
    }

    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge.suspended {
      background: #f8d7da;
      color: #842029;
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

    .action-btn.verify {
      background: #e8f5e9;
      color: #388e3c;
    }

    .action-btn.verify:hover {
      background: #388e3c;
      color: white;
    }

    .action-btn.suspend {
      background: #fff3cd;
      color: #f57c00;
    }

    .action-btn.suspend:hover {
      background: #f57c00;
      color: white;
    }

    .action-btn.delete {
      background: #ffebee;
      color: #d32f2f;
    }

    .action-btn.delete:hover {
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

    .documents-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .document-card {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .document-card:hover {
      background: #e9ecef;
    }

    .document-icon {
      width: 45px;
      height: 45px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #1a1a1a;
    }

    .document-info {
      flex: 1;
    }

    .document-title {
      font-size: 13px;
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 3px;
    }

    .document-status {
      font-size: 11px;
      color: #666;
    }

    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .activity-item {
      display: flex;
      gap: 12px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .activity-content {
      flex: 1;
    }

    .activity-title {
      font-size: 14px;
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 3px;
    }

    .activity-time {
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

    .modal-btn.verify {
      background: #28a745;
      color: white;
    }

    .modal-btn.verify:hover {
      background: #218838;
    }

    .modal-btn.suspend {
      background: #f57c00;
      color: white;
    }

    .modal-btn.suspend:hover {
      background: #ef6c00;
    }

    .modal-btn.delete {
      background: #dc3545;
      color: white;
    }

    .modal-btn.delete:hover {
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
  <title>Sales Statistics - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
      <a href="users.php" class="menu-item active">
        <i class="bi bi-person"></i>
        <span>Users Verification</span>
      </a>
      <a href="bookings.php" class="menu-item">
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
=======
  <!-- Include Sidebar -->
  <?php include 'include/sidebar.php'; ?>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
<<<<<<< HEAD
      <h1 class="page-title">Users Management</h1>
=======
      <h1 class="page-title">
        <i class="bi bi-bar-chart"></i>
        Sales Statistics
      </h1>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">3</span>
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
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-people"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15%
          </div>
        </div>
        <div class="stat-value">142</div>
        <div class="stat-label">Total Users</div>
=======
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12.5%
          </div>
        </div>
        <div class="stat-value">₱524,000</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">This month: ₱145,000</div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">98</div>
        <div class="stat-label">Verified Users</div>
=======
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8.3%
          </div>
        </div>
        <div class="stat-value">156</div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-detail">This month: 42 bookings</div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12
          </div>
        </div>
        <div class="stat-value">31</div>
        <div class="stat-label">Pending Verification</div>
=======
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-graph-up"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15.2%
          </div>
        </div>
        <div class="stat-value">₱3,359</div>
        <div class="stat-label">Average Booking Value</div>
        <div class="stat-detail">Up from ₱2,915</div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-x-circle"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i>
            -3
          </div>
        </div>
        <div class="stat-value">13</div>
        <div class="stat-label">Suspended Accounts</div>
=======
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-star"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +0.3
          </div>
        </div>
        <div class="stat-value">4.8</div>
        <div class="stat-label">Average Rating</div>
        <div class="stat-detail">Based on 234 reviews</div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
<<<<<<< HEAD
        <div class="search-box">
          <input type="text" placeholder="Search by name, email, or phone number...">
          <i class="bi bi-search"></i>
        </div>
        <select class="filter-dropdown">
          <option>All Roles</option>
          <option>Car Owners</option>
          <option>Renters</option>
          <option>Both</option>
        </select>
        <select class="filter-dropdown">
          <option>All Status</option>
          <option>Verified</option>
          <option>Pending</option>
          <option>Suspended</option>
        </select>
        <select class="filter-dropdown">
          <option>Registration Date</option>
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Last 90 Days</option>
        </select>
        <button class="add-user-btn">
          <i class="bi bi-person-plus"></i>
          Add User
=======
        <select class="filter-dropdown">
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Last 90 Days</option>
          <option>This Year</option>
          <option>All Time</option>
        </select>
        <select class="filter-dropdown">
          <option>All Cars</option>
          <option>Sedan</option>
          <option>SUV</option>
          <option>Van</option>
          <option>Pickup</option>
        </select>
        <select class="filter-dropdown">
          <option>All Locations</option>
          <option>Midsayap</option>
          <option>Butuan</option>
          <option>Manila</option>
        </select>
        <button class="add-user-btn">
          <i class="bi bi-download"></i>
          Export Report
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
        </button>
      </div>
    </div>

<<<<<<< HEAD
    <!-- Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">All Users</h2>
        <div class="table-controls">
          <button class="table-btn active">All (142)</button>
          <button class="table-btn">Pending (31)</button>
          <button class="table-btn">Verified (98)</button>
        </div>
      </div>

=======
    <!-- Revenue Chart Section -->
    <div class="table-section" style="margin-bottom: 30px;">
      <div class="section-header">
        <h2 class="section-title">Revenue Overview</h2>
        <div class="table-controls">
          <button class="table-btn active">Daily</button>
          <button class="table-btn">Weekly</button>
          <button class="table-btn">Monthly</button>
        </div>
      </div>
      <div style="padding: 20px;">
        <canvas id="revenueChart" style="max-height: 400px;"></canvas>
      </div>
    </div>

    <!-- Top Performing Cars -->
    <div class="table-section" style="margin-bottom: 30px;">
      <div class="section-header">
        <h2 class="section-title">Top Performing Cars</h2>
        <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
<<<<<<< HEAD
              <th>#</th>
              <th>User</th>
              <th>Phone Number</th>
              <th>Role</th>
              <th>Joined Date</th>
              <th>Status</th>
              <th>Actions</th>
=======
              <th>Rank</th>
              <th>Car</th>
              <th>Total Bookings</th>
              <th>Revenue</th>
              <th>Avg. Rating</th>
              <th>Trend</th>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
            </tr>
          </thead>
          <tbody>
            <tr>
<<<<<<< HEAD
              <td><strong>#USR-1024</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="Juan">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan.delacruz@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 912 345 6789</td>
              <td><span class="role-badge owner">Car Owner</span></td>
              <td>Jan 15, 2024</td>
              <td><span class="status-badge verified">Verified</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#userModal1" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Suspend Account">
                    <i class="bi bi-ban"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1023</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=1a1a1a&color=fff" alt="Maria">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Maria Santos</span>
                    <span class="user-email">maria.santos@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 917 234 5678</td>
              <td><span class="role-badge renter">Renter</span></td>
              <td>Feb 20, 2024</td>
              <td><span class="status-badge pending">Pending</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#userModal2" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn verify" title="Verify User">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1022</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Reyes&background=1a1a1a&color=fff" alt="Carlos">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Carlos Reyes</span>
                    <span class="user-email">carlos.reyes@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 918 345 6789</td>
              <td><span class="role-badge both">Owner & Renter</span></td>
              <td>Mar 10, 2024</td>
              <td><span class="status-badge verified">Verified</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Suspend Account">
                    <i class="bi bi-ban"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1021</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Ana+Lopez&background=1a1a1a&color=fff" alt="Ana">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Ana Lopez</span>
                    <span class="user-email">ana.lopez@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 919 456 7890</td>
              <td><span class="role-badge owner">Car Owner</span></td>
              <td>Apr 05, 2024</td>
              <td><span class="status-badge pending">Pending</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn verify" title="Verify User">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1020</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Robert+Tan&background=1a1a1a&color=fff" alt="Robert">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Robert Tan</span>
                    <span class="user-email">robert.tan@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 920 567 8901</td>
              <td><span class="role-badge renter">Renter</span></td>
              <td>May 12, 2024</td>
              <td><span class="status-badge suspended">Suspended</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn verify" title="Reactivate Account">
                    <i class="bi bi-arrow-clockwise"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1019</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Lisa+Garcia&background=1a1a1a&color=fff" alt="Lisa">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Lisa Garcia</span>
                    <span class="user-email">lisa.garcia@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 921 678 9012</td>
              <td><span class="role-badge owner">Car Owner</span></td>
              <td>Jun 18, 2024</td>
              <td><span class="status-badge verified">Verified</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Suspend Account">
                    <i class="bi bi-ban"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
=======
              <td><strong>#1</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Vios&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Vios 2022</span>
                    <span class="user-email">ABC-1234 • Sedan</span>
                  </div>
                </div>
              </td>
              <td><strong>42</strong></td>
              <td><strong>₱84,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.9</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +18%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#2</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Honda+City&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Honda City 2023</span>
                    <span class="user-email">DEF-5678 • Sedan</span>
                  </div>
                </div>
              </td>
              <td><strong>38</strong></td>
              <td><strong>₱76,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.8</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +12%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#3</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mitsubishi+Xpander&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mitsubishi Xpander 2021</span>
                    <span class="user-email">GHI-9012 • MPV</span>
                  </div>
                </div>
              </td>
              <td><strong>35</strong></td>
              <td><strong>₱70,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.7</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +8%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#4</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Fortuner&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Fortuner 2022</span>
                    <span class="user-email">JKL-3456 • SUV</span>
                  </div>
                </div>
              </td>
              <td><strong>28</strong></td>
              <td><strong>₱84,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.9</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +15%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#5</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Suzuki+Ertiga&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Suzuki Ertiga 2020</span>
                    <span class="user-email">MNO-7890 • MPV</span>
                  </div>
                </div>
              </td>
              <td><strong>25</strong></td>
              <td><strong>₱50,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.6</span>
              </td>
              <td>
                <div class="stat-trend down">
                  <i class="bi bi-arrow-down"></i>
                  -3%
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Recent Transactions</h2>
        <a href="bookings.php" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Car</th>
              <th>Duration</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>#BK-1045</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan@email.com</span>
                  </div>
                </div>
              </td>
              <td>Toyota Vios 2022</td>
              <td>3 days</td>
              <td><strong>₱3,000</strong></td>
              <td><span class="status-badge verified">Completed</span></td>
              <td>Dec 5, 2025</td>
            </tr>
            <tr>
              <td><strong>#BK-1044</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Maria Santos</span>
                    <span class="user-email">maria@email.com</span>
                  </div>
                </div>
              </td>
              <td>Honda City 2023</td>
              <td>5 days</td>
              <td><strong>₱6,000</strong></td>
              <td><span class="status-badge pending">Ongoing</span></td>
              <td>Dec 4, 2025</td>
            </tr>
            <tr>
              <td><strong>#BK-1043</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Reyes&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Carlos Reyes</span>
                    <span class="user-email">carlos@email.com</span>
                  </div>
                </div>
              </td>
              <td>Mitsubishi Xpander</td>
              <td>2 days</td>
              <td><strong>₱2,400</strong></td>
              <td><span class="status-badge verified">Completed</span></td>
              <td>Dec 3, 2025</td>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-section">
        <div class="pagination-info">
<<<<<<< HEAD
          Showing <strong>1-6</strong> of <strong>142</strong> users
=======
          Showing <strong>1-3</strong> of <strong>156</strong> transactions
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
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

<<<<<<< HEAD
<!-- User Details Modal -->
<div class="modal fade" id="userModal1" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Details - Juan Dela Cruz</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Personal Information -->
        <div class="detail-section">
          <div class="detail-section-title">Personal Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">User ID</span>
              <span class="detail-value">#USR-1024</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Full Name</span>
              <span class="detail-value">Juan Dela Cruz</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email Address</span>
              <span class="detail-value">juan.delacruz@email.com</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phone Number</span>
              <span class="detail-value">+63 912 345 6789</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Birth</span>
              <span class="detail-value">March 15, 1990</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Gender</span>
              <span class="detail-value">Male</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Address</span>
              <span class="detail-value">123 Main St, San Francisco, Agusan del Sur</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Registration Date</span>
              <span class="detail-value">January 15, 2024</span>
            </div>
          </div>
        </div>

        <!-- Account Information -->
        <div class="detail-section">
          <div class="detail-section-title">Account Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Account Role</span>
              <span class="role-badge owner">Car Owner</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Verification Status</span>
              <span class="status-badge verified">Verified</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Cars Listed</span>
              <span class="detail-value">3 Vehicles</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Bookings</span>
              <span class="detail-value">24 Completed</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Average Rating</span>
              <span class="detail-value">4.8 ⭐ (45 reviews)</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Earnings</span>
              <span class="detail-value">₱45,000.00</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Last Login</span>
              <span class="detail-value">November 28, 2025 - 2:30 PM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Account Status</span>
              <span class="status-badge verified">Active</span>
            </div>
          </div>
        </div>

        <!-- Verification Documents -->
        <div class="detail-section">
          <div class="detail-section-title">Verification Documents</div>
          <div class="documents-grid">
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-file-earmark-person"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Government ID</div>
                <div class="document-status">✓ Verified</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-credit-card"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Driver's License</div>
                <div class="document-status">✓ Verified - N01-12-345678</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-camera"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Selfie Verification</div>
                <div class="document-status">✓ Verified</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-house"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Proof of Address</div>
                <div class="document-status">✓ Verified</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bank Details -->
        <div class="detail-section">
          <div class="detail-section-title">Payment Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Bank Name</span>
              <span class="detail-value">BDO Unibank</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Account Number</span>
              <span class="detail-value">•••• •••• 1234</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Account Name</span>
              <span class="detail-value">Juan Dela Cruz</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Payment Method</span>
              <span class="detail-value">GCash, Bank Transfer</span>
            </div>
          </div>
        </div>

        <!-- Listed Vehicles -->
        <div class="detail-section">
          <div class="detail-section-title">Listed Vehicles (3)</div>
          <div class="activity-list">
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-car-front"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">Toyota Vios 2022 - ABC 1234</div>
                <div class="activity-time">Listed: Jan 20, 2024 • Status: Active • ₱1,000/day</div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-car-front"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">Honda City 2023 - DEF 5678</div>
                <div class="activity-time">Listed: Feb 10, 2024 • Status: Rented • ₱1,200/day</div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-car-front"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">Mitsubishi Mirage 2021 - GHI 9012</div>
                <div class="activity-time">Listed: Mar 05, 2024 • Status: Active • ₱850/day</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="detail-section">
          <div class="detail-section-title">Recent Activity</div>
          <div class="activity-list">
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-check-circle" style="color: #28a745;"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">Booking Completed - Toyota Vios</div>
                <div class="activity-time">November 25, 2025 - Earned ₱5,000</div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-calendar-check" style="color: #1976d2;"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">New Booking Received - Honda City</div>
                <div class="activity-time">November 22, 2025</div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">
                <i class="bi bi-star" style="color: #f57c00;"></i>
              </div>
              <div class="activity-content">
                <div class="activity-title">Received 5-Star Review</div>
                <div class="activity-time">November 20, 2025</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-btn secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="modal-btn suspend">Suspend Account</button>
        <button type="button" class="modal-btn delete">Delete User</button>
      </div>
    </div>
  </div>
</div>

<!-- User Details Modal 2 (Pending User) -->
<div class="modal fade" id="userModal2" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Details - Maria Santos (Pending Verification)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Personal Information -->
        <div class="detail-section">
          <div class="detail-section-title">Personal Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">User ID</span>
              <span class="detail-value">#USR-1023</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Full Name</span>
              <span class="detail-value">Maria Santos</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email Address</span>
              <span class="detail-value">maria.santos@email.com</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phone Number</span>
              <span class="detail-value">+63 917 234 5678</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Date of Birth</span>
              <span class="detail-value">June 22, 1995</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Gender</span>
              <span class="detail-value">Female</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Address</span>
              <span class="detail-value">456 Park Avenue, Butuan City</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Registration Date</span>
              <span class="detail-value">February 20, 2024</span>
            </div>
          </div>
        </div>

        <!-- Account Information -->
        <div class="detail-section">
          <div class="detail-section-title">Account Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Account Role</span>
              <span class="role-badge renter">Renter</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Verification Status</span>
              <span class="status-badge pending">Pending Review</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Bookings</span>
              <span class="detail-value">0 (New User)</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Documents Submitted</span>
              <span class="detail-value">4 of 4</span>
            </div>
          </div>
        </div>

        <!-- Verification Documents -->
        <div class="detail-section">
          <div class="detail-section-title">Verification Documents - Awaiting Review</div>
          <div class="documents-grid">
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-file-earmark-person"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Government ID</div>
                <div class="document-status">⏳ Pending Review</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-credit-card"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Driver's License</div>
                <div class="document-status">⏳ Pending - N02-22-987654</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-camera"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Selfie Verification</div>
                <div class="document-status">⏳ Pending Review</div>
              </div>
            </div>
            <div class="document-card">
              <div class="document-icon">
                <i class="bi bi-house"></i>
              </div>
              <div class="document-info">
                <div class="document-title">Proof of Address</div>
                <div class="document-status">⏳ Pending Review</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-btn secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="modal-btn delete">Reject Application</button>
        <button type="button" class="modal-btn verify">Approve & Verify</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
=======
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: 'Revenue',
      data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
      borderColor: '#1a1a1a',
      backgroundColor: 'rgba(26, 26, 26, 0.1)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointRadius: 6,
      pointBackgroundColor: '#1a1a1a',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointHoverRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleColor: '#fff',
        bodyColor: '#fff',
        padding: 12,
        cornerRadius: 8,
        displayColors: false,
        callbacks: {
          label: function(context) {
            return '₱' + context.parsed.y.toLocaleString();
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return '₱' + (value / 1000) + 'k';
          },
          font: {
            size: 12,
            weight: '600'
          }
        },
        grid: {
          color: '#f0f0f0'
        }
      },
      x: {
        ticks: {
          font: {
            size: 12,
            weight: '600'
          }
        },
        grid: {
          display: false
        }
      }
    }
  }
});
</script>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
</body>
</html>