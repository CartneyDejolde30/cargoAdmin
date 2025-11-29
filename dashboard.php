<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarGo Admin Dashboard</title>
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

    .logout-item {
      color: #dc3545;
      margin-top: 20px;
    }

    .logout-item:hover {
      background: #fff5f5;
      color: #dc3545;
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
    }

    .notification-btn:hover {
      background: #f5f5f5;
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

    /* Welcome Card */
    .welcome-card {
      background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
      border-radius: 20px;
      padding: 35px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .welcome-content h2 {
      font-size: 26px;
      font-weight: 800;
      color: #1a1a1a;
      margin-bottom: 8px;
    }

    .welcome-content p {
      color: #424242;
      font-size: 14px;
      margin-bottom: 20px;
      max-width: 400px;
    }

    .welcome-btn {
      background: #1a1a1a;
      color: white;
      border: none;
      padding: 12px 28px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .welcome-btn:hover {
      background: #000000;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .welcome-illustration {
      width: 200px;
      height: auto;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 18px;
      padding: 28px;
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
      margin-bottom: 20px;
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
      color: #1a1a1a;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .stat-trend.down {
      color: #dc3545;
    }

    .stat-value {
      font-size: 36px;
      font-weight: 800;
      color: #1a1a1a;
      margin-bottom: 8px;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
      font-weight: 500;
    }

    .stat-detail {
      font-size: 13px;
      color: #999;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f0f0f0;
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

    .view-all {
      color: #1a1a1a;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .view-all:hover {
      gap: 10px;
    }

    .table-filters {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
    }

    .filter-btn {
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

    .filter-btn.active {
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
      font-size: 13px;
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
    }

    tbody tr:hover {
      background: #f8f9fa;
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

    .status-badge.available {
      background: #f0f0f0;
      color: #1a1a1a;
    }

    .status-badge.on-going {
      background: #e8e8e8;
      color: #000000;
    }

    .status-badge.completed {
      background: #d4d4d4;
      color: #1a1a1a;
    }

    @media (max-width: 1400px) {
      .stats-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 1200px) {
      .sidebar {
        width: 80px;
      }

      .sidebar .logo-text,
      .sidebar .menu-label,
      .sidebar .menu-item span {
        display: none;
      }

      .main-content {
        margin-left: 80px;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      .welcome-card {
        flex-direction: column;
        text-align: center;
      }

      .welcome-illustration {
        margin-top: 20px;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .table-section {
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include('include/sidebar.php'); ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">Dashboard Overview</h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Olivia+Deny&background=1a1a1a&color=fff" alt="User">
        </div>
      </div>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card">
      <div class="welcome-content">
        <h2>Get where you need to go<br>with our service</h2>
        <p>Connect car owners with renters. Manage your peer-to-peer platform with comprehensive coverage and the highest quality service.</p>
        <button class="welcome-btn">Start Exploring</button>
      </div>
      <svg class="welcome-illustration" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="100" cy="100" r="40" fill="#1a1a1a" opacity="0.1"/>
        <path d="M60 100 Q100 60 140 100" stroke="#1a1a1a" stroke-width="4" fill="none"/>
        <circle cx="70" cy="100" r="8" fill="#1a1a1a"/>
        <circle cx="130" cy="100" r="8" fill="#1a1a1a"/>
        <rect x="80" y="80" width="40" height="30" rx="5" fill="#1a1a1a"/>
      </svg>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12.5%
          </div>
        </div>
        <div class="stat-value">₱12,500</div>
        <div class="stat-label">Total Earnings</div>
        <div class="stat-detail">Net Profit Last Month: ₱9,200</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon">
            <i class="bi bi-calendar-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8.3%
          </div>
        </div>
        <div class="stat-value">45</div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-detail">Active: 12 | Completed: 33</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon">
            <i class="bi bi-people"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +5.2%
          </div>
        </div>
        <div class="stat-value">58</div>
        <div class="stat-label">Total Users</div>
        <div class="stat-detail">Owners: 20 | Renters: 38</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon">
            <i class="bi bi-flag"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i>
            -2.1%
          </div>
        </div>
        <div class="stat-value">3</div>
        <div class="stat-label">Reported Issues</div>
        <div class="stat-detail">Resolved: 24 | Pending: 3</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15%
          </div>
        </div>
        <div class="stat-value">3</div>
        <div class="stat-label">Pending Verification</div>
        <div class="stat-detail">Awaiting Review</div>
      </div>
    </div>

    <!-- Car Listings Table -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Car Listing</h2>
        <a href="#" class="view-all">
          You Have
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="table-filters">
        <button class="filter-btn active">All</button>
        <button class="filter-btn">Owner Name</button>
        <button class="filter-btn">Car Type</button>
        <button class="filter-btn">Car Number</button>
        <button class="filter-btn">Status</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Owner Name</th>
            <th>Car Type</th>
            <th>Car Number</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-small">
                  <img src="https://ui-avatars.com/api/?name=Liam+Johnson&background=1a1a1a&color=fff" alt="Liam Johnson">
                </div>
                <span>Liam Johnson</span>
              </div>
            </td>
            <td>Honda Brio</td>
            <td>010 MDB</td>
            <td><span class="status-badge on-going">On Going</span></td>
          </tr>
          <tr>
            <td>2</td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-small">
                  <img src="https://ui-avatars.com/api/?name=Noah+Anderson&background=1a1a1a&color=fff" alt="Noah Anderson">
                </div>
                <span>Noah Anderson</span>
              </div>
            </td>
            <td>Pagani Sport</td>
            <td>690 TCM</td>
            <td><span class="status-badge available">Available</span></td>
          </tr>
          <tr>
            <td>3</td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-small">
                  <img src="https://ui-avatars.com/api/?name=Ethan+Smith&background=1a1a1a&color=fff" alt="Ethan Smith">
                </div>
                <span>Ethan Smith</span>
              </div>
            </td>
            <td>Apple</td>
            <td>660 ECT</td>
            <td><span class="status-badge available">Available</span></td>
          </tr>
          <tr>
            <td>4</td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-small">
                  <img src="https://ui-avatars.com/api/?name=Mason+Davis&background=1a1a1a&color=fff" alt="Mason Davis">
                </div>
                <span>Mason Davis</span>
              </div>
            </td>
            <td>Aiza</td>
            <td>710 BCD</td>
            <td><span class="status-badge completed">Completed</span></td>
          </tr>
          <tr>
            <td>5</td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-small">
                  <img src="https://ui-avatars.com/api/?name=Jackson+Williams&background=1a1a1a&color=fff" alt="Jackson Williams">
                </div>
                <span>Jackson Williams</span>
              </div>
            </td>
            <td>Honda Brio</td>
            <td>324 WW1</td>
            <td><span class="status-badge available">Available</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>