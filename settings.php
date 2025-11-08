<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - CarGo Admin</title>
  
  
  <style>
    body {
      background-color: #f1f1f1;
      font-family: 'Poppins', sans-serif;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .settings-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-top: 30px;
    }

    footer {
      background-color: #6c757d;
      color: #fff;
      padding: 15px 0;
      text-align: center;
      width: 100%;
      margin-top: auto;
    }

    footer a {
      color: #fff;
      transition: color 0.2s ease;
    }

    footer a:hover {
      color: #dcdcdc;
    }
  </style>
</head>
<body>

<?php include "include/header.php"; ?>

<div class="container flex-grow-1">
  <h3 class="fw-bold mt-5 mb-4">Settings</h3>

  <div class="row g-4">
    <!-- Profile Settings -->
    <div class="col-md-6">
      <div class="settings-card">
        <h5><i class="bi bi-person-circle me-2"></i>Profile Information</h5>
        <form>
          <div class="mb-3">
            <label for="adminName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="adminName" placeholder="Admin Name" value="Cartney Dejolde">
          </div>
          <div class="mb-3">
            <label for="adminEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="adminEmail" placeholder="Email" value="cart@cargo.com">
          </div>
          <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-6">
      <div class="settings-card">
        <h5><i class="bi bi-key-fill me-2"></i>Change Password</h5>
        <form>
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="currentPassword" placeholder="Current Password">
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input type="password" class="form-control" id="newPassword" placeholder="New Password">
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm New Password">
          </div>
          <button type="submit" class="btn btn-warning">Change Password</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Optional: System Settings -->
  <div class="row g-4 mt-3">
    <div class="col-md-12">
      <div class="settings-card">
        <h5><i class="bi bi-gear-fill me-2"></i>System Settings</h5>
        <form>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="notificationsCheck" checked>
            <label class="form-check-label" for="notificationsCheck">Enable Email Notifications</label>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="maintenanceCheck">
            <label class="form-check-label" for="maintenanceCheck">Enable Maintenance Mode</label>
          </div>
          <button type="submit" class="btn btn-success">Save Settings</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include "include/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
