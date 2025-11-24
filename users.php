<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | CarGo Admin</title>
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
      display: flex;
      align-items: center;
      gap: 12px;
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

    /* Search Section */
    .search-section {
      background: white;
      border-radius: 18px;
      padding: 25px 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 25px;
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .search-input {
      flex: 1;
      padding: 12px 18px;
      border: 2px solid #f0f0f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .filter-select {
      padding: 12px 18px;
      border: 2px solid #f0f0f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      min-width: 150px;
    }

    .filter-select:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .search-btn {
      padding: 12px 28px;
      background: #1a1a1a;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .search-btn:hover {
      background: #000000;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Table Section */
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
    }

    tbody tr:hover {
      background: #f8f9fa;
    }

    /* Role Badges */
    .role-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .role-badge.owner {
      background: #e8f4ff;
      color: #0066cc;
    }

    .role-badge.renter {
      background: #e8f8f0;
      color: #009944;
    }

    /* Status Badges */
    .status-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.verified {
      background: #e8f8f0;
      color: #009944;
    }

    .status-badge.unverified {
      background: #ffe8e8;
      color: #cc0000;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
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

    .action-btn.suspend {
      background: #fff8e1;
      color: #f57c00;
    }

    .action-btn.suspend:hover {
      background: #f57c00;
      color: white;
    }

    .action-btn.activate {
      background: #e8f8f0;
      color: #009944;
    }

    .action-btn.activate:hover {
      background: #009944;
      color: white;
    }

    .action-btn.delete {
      background: #ffe8e8;
      color: #cc0000;
    }

    .action-btn.delete:hover {
      background: #cc0000;
      color: white;
    }

    .action-btn.edit {
      background: #e8f8f0;
      color: #009944;
    }

    .action-btn.edit:hover {
      background: #009944;
      color: white;
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
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      .search-section {
        flex-direction: column;
      }

      .filter-select {
        width: 100%;
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
      <h1 class="page-title">
        <i class="bi bi-people"></i>
        User Management
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Olivia+Deny&background=1a1a1a&color=fff" alt="User">
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <input type="text" class="search-input" placeholder="Search users...">
      <select class="filter-select">
        <option>All</option>
        <option>Owners</option>
        <option>Renters</option>
      </select>
      <button class="search-btn">Search</button>
    </div>

    <!-- Users Table -->
    <div class="table-section">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>Juan Dela Cruz</td>
            <td>juan@example.com</td>
            <td><span class="role-badge owner">Owner</span></td>
            <td><span class="status-badge verified">Verified</span></td>
            <td>
              <div class="action-buttons">
                <button class="action-btn suspend">Suspend</button>
                <button class="action-btn delete">Delete</button>
                <button class="action-btn edit">Edit</button>
              </div>
            </td>
          </tr>
          <tr>
            <td>2</td>
            <td>Maria Santos</td>
            <td>maria@example.com</td>
            <td><span class="role-badge renter">Renter</span></td>
            <td><span class="status-badge unverified">Unverified</span></td>
            <td>
              <div class="action-buttons">
                <button class="action-btn activate">Activate</button>
                <button class="action-btn delete">Delete</button>
                <button class="action-btn edit">Edit</button>
              </div>
            </td>
          </tr>
          <tr>
            <td>3</td>
            <td>Carlos Reyes</td>
            <td>carlos@example.com</td>
            <td><span class="role-badge owner">Owner</span></td>
            <td><span class="status-badge verified">Verified</span></td>
            <td>
              <div class="action-buttons">
                <button class="action-btn suspend">Suspend</button>
                <button class="action-btn delete">Delete</button>
                <button class="action-btn edit">Edit</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>