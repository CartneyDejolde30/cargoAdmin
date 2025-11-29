<?php
include 'include/db.php';

$sql = "
    SELECT cars.*, users.fullname 
    FROM cars 
    JOIN users ON users.id = cars.owner_id 
    ORDER BY cars.created_at DESC 
    LIMIT 5
";

$query = $conn->query($sql);




?>




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

    

     .table-section {
      background: white;
      border-radius: 18px;
      padding: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
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
      vertical-align: middle;
    }

    tbody tr:hover {
      background: #f8f9fa;
    }

    /* Car Thumbnail */
    .car-thumb {
      width: 80px;
      height: 60px;
      border-radius: 10px;
      object-fit: cover;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: transform 0.3s ease;
    }

    .car-thumb:hover {
      transform: scale(1.05);
    }

    /* Status Badges */
    .status-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.approved {
      background: #e8f8f0;
      color: #009944;
    }

    .status-badge.pending {
      background: #fff8e1;
      color: #f57c00;
    }

    .status-badge.rejected {
      background: #ffe8e8;
      color: #cc0000;
    }

    /* Document Buttons */
    .doc-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      margin-right: 4px;
    }

    .doc-btn.or {
      background: #e3f2fd;
      color: #1976d2;
    }

    .doc-btn.or:hover {
      background: #1976d2;
      color: white;
    }

    .doc-btn.cr {
      background: #f3e5f5;
      color: #7b1fa2;
    }

    .doc-btn.cr:hover {
      background: #7b1fa2;
      color: white;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .action-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .action-btn.approve {
      background: #e8f8f0;
      color: #009944;
    }

    .action-btn.approve:hover {
      background: #009944;
      color: white;
    }

    .action-btn.reject {
      background: #ffe8e8;
      color: #cc0000;
    }

    .action-btn.reject:hover {
      background: #cc0000;
      color: white;
    }

    .action-btn.view {
      background: #e3f2fd;
      color: #1976d2;
    }

    .action-btn.view:hover {
      background: #1976d2;
      color: white;
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

      <div class="table-section">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Owner</th>
            <th>Car Details</th>
            <th>Plate Number</th>
            <th>Status</th>
            <th>Image</th>
            <th>Documents</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if ($query->num_rows === 0) {
            echo '<tr><td colspan="8" class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>No cars found</h4>
                    <p>Try adjusting your search or filter criteria</p>
                  </td></tr>';
          }

          $num = 1;
          while($row = $query->fetch_assoc()) { ?>
          <tr>
            <td><?= $num++ ?></td>
            <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
            <td>
              <strong><?= htmlspecialchars($row['brand']) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars($row['model']) ?></small>
            </td>
            <td><strong><?= htmlspecialchars($row['plate_number']) ?></strong></td>
            <td>
              <span class="status-badge <?= $row['status'] ?>">
                <?= ucfirst($row['status']) ?>
              </span>
            </td>
            <td>
              <?php if(!empty($row['image'])) { ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" class="car-thumb" 
                     onclick="viewCarImage('<?= htmlspecialchars($row['image']) ?>')" alt="Car">
              <?php } else { ?>
                <span class="text-muted">No Image</span>
              <?php } ?>
            </td>
            <td>
              <a href="<?= htmlspecialchars($row['official_receipt']) ?>" 
                 class="doc-btn or" target="_blank" title="Official Receipt">OR</a>
              <a href="<?= htmlspecialchars($row['certificate_of_registration']) ?>" 
                 class="doc-btn cr" target="_blank" title="Certificate of Registration">CR</a>
            </td>
            <td>
              <div class="action-buttons">
                <form method="POST" action="update_car_status.php" style="display: contents;">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  
                  <?php if($row['status'] !== 'approved') { ?>
                    <button name="status" value="approved" class="action-btn approve">
                      <i class="bi bi-check-lg"></i> Approve
                    </button>
                  <?php } ?>

                  <?php if($row['status'] !== 'rejected') { ?>
                    <button type="button" 
                            class="action-btn reject rejectBtn" 
                            data-id="<?= $row['id'] ?>">
                      <i class="bi bi-x-lg"></i> Reject
                    </button>
                  <?php } ?>
                </form>
              </div>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>