<!-- Sidebar -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <style>
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
</style>

<aside class="sidebar">
  <div class="logo-section">
    <div class="logo-icon">C</div>
    <div class="logo-text">CARGO</div>
  </div>
  
  <!-- MAIN NAVIGATION -->
  <div class="menu-section">
    <div class="menu-label">Main Menu</div>
    <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>
    <a href="users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
      <i class="bi bi-person"></i>
      <span>Users Verification</span>
    </a>
  </div>

  <!-- VEHICLES MANAGEMENT -->
  <div class="menu-section">
    <div class="menu-label">Vehicles</div>
    <a href="get_cars_admin.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'get_cars_admin.php' ? 'active' : ''; ?>">
      <i class="bi bi-car-front"></i>
      <span>Car Listings</span>
    </a>
    <a href="get_motorcycle_admin.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'get_motorcycle_admin.php' ? 'active' : ''; ?>">
      <i class="bi bi-bicycle"></i>
      <span>Motorcycle Listings</span>
    </a>
  </div>

  <!-- TRANSACTIONS -->
  <div class="menu-section">
    <div class="menu-label">Transactions</div>
    <a href="bookings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
      <i class="bi bi-book"></i>
      <span>Bookings</span>
    </a>
    <a href="payment.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
      <i class="bi bi-credit-card"></i>
      <span>Payments</span>
    </a>
  </div>

  <!-- REPORTS -->
  <div class="menu-section">
    <div class="menu-label">Report</div>
    <a href="statistics.php" 
       class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>">
      <i class="bi bi-bar-chart"></i>
      <span>Sales Statistics</span>
    </a>
    <a href="reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
      <i class="bi bi-file-text"></i>
      <span>Car Reports</span>
    </a>
  </div>

  <!-- SYSTEM -->
  <div class="menu-section">
    <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
      <i class="bi bi-gear"></i>
      <span>Settings</span>
    </a>
    <a href="logout.php" class="menu-item logout-item">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>