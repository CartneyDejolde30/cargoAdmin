<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</head>
<body>

<div class="dashboard-wrapper">
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

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">Users Management</h1>
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
      </div>

      <div class="stat-card">
        <div class="stat-header">
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
      </div>

      <div class="stat-card">
        <div class="stat-header">
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
      </div>

      <div class="stat-card">
        <div class="stat-header">
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
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
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
        </button>
      </div>
    </div>

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

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Phone Number</th>
              <th>Role</th>
              <th>Joined Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
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
              <td><strong>#USR-1023