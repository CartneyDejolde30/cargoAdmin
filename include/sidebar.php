<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo-section">
    <div class="logo-icon">C</div>
    <div class="logo-text">CARGO</div>
  </div>

  <div class="menu-section">
    <div class="menu-label">About Car</div>
    <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>
    <a href="car-listings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'car-listings.php' ? 'active' : ''; ?>">
      <i class="bi bi-car-front"></i>
      <span>Car Listing</span>
    </a>
    <a href="users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users-verification.php' ? 'active' : ''; ?>">
      <i class="bi bi-person"></i>
      <span>Users Verification</span>
    </a>
    <a href="bookings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
      <i class="bi bi-book"></i>
      <span>Bookings</span>
    </a>
  </div>

  <div class="menu-section">
    <div class="menu-label">Report</div>
    <a href="sales-statistics.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'sales-statistics.php' ? 'active' : ''; ?>">
      <i class="bi bi-bar-chart"></i>
      <span>Sales Statistics</span>
    </a>
    <a href="car-reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'car-reports.php' ? 'active' : ''; ?>">
      <i class="bi bi-file-text"></i>
      <span>Car Reports</span>
    </a>
  </div>

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