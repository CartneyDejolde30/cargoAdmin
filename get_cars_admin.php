<?php
include "include/db.php";

// Get filter values
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Pagination settings
$limit = 10; // rows per page
$page = $_GET['page'] ?? 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

// Base query
$sql = "
    SELECT cars.*, users.fullname 
    FROM cars 
    JOIN users ON users.id = cars.owner_id 
    WHERE 1
";

// Search filter
if (!empty($search)) {
    $sql .= " AND (cars.brand LIKE '%$search%' 
                OR cars.model LIKE '%$search%' 
                OR cars.plate_number LIKE '%$search%' 
                OR users.fullname LIKE '%$search%')";
}

// Status filter
if ($status !== "all") {
    $sql .= " AND cars.status = '$status'";
}

// Count total rows for pagination (before applying LIMIT)
$countQuery = $conn->query($sql);
$totalRows = $countQuery->num_rows;
$totalPages = ceil($totalRows / $limit);

// Final query with pagination
$sql .= " ORDER BY cars.created_at DESC LIMIT $limit OFFSET $offset";
$query = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Approval Management | CarGo Admin</title>
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

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
    }

    .stat-label {
      font-size: 12px;
      font-weight: 600;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 800;
      color: #1a1a1a;
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      float: right;
      margin-top: -10px;
    }

    .stat-pending { background: #fff8e1; color: #f57c00; }
    .stat-approved { background: #e8f8f0; color: #009944; }
    .stat-rejected { background: #ffe8e8; color: #cc0000; }
    .stat-total { background: #e3f2fd; color: #1976d2; }

    /* Search Section */
    .search-section {
      background: white;
      border-radius: 18px;
      padding: 25px 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 25px;
    }

    .search-form {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input {
      flex: 1;
      min-width: 250px;
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

    .reset-btn {
      padding: 12px 28px;
      background: white;
      color: #666;
      border: 2px solid #f0f0f0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .reset-btn:hover {
      background: #f5f5f5;
      border-color: #e0e0e0;
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

    /* Pagination */
    .pagination-wrapper {
      margin-top: 25px;
      display: flex;
      justify-content: center;
    }

    .pagination {
      display: flex;
      gap: 8px;
      list-style: none;
      padding: 0;
    }

    .page-item .page-link {
      padding: 10px 16px;
      border: 2px solid #f0f0f0;
      border-radius: 8px;
      color: #666;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .page-item.active .page-link {
      background: #1a1a1a;
      color: white;
      border-color: #1a1a1a;
    }

    .page-item .page-link:hover {
      background: #f5f5f5;
      border-color: #e0e0e0;
    }

    .page-item.disabled .page-link {
      opacity: 0.4;
      cursor: not-allowed;
    }

    /* Modal Styles */
    .modal-content {
      border-radius: 18px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }

    .modal-header {
      border-bottom: 2px solid #f0f0f0;
      padding: 20px 24px;
    }

    .modal-title {
      font-weight: 700;
      color: #1a1a1a;
    }

    .modal-body {
      padding: 24px;
    }

    .form-control {
      border: 2px solid #f0f0f0;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 14px;
    }

    .form-control:focus {
      border-color: #1a1a1a;
      box-shadow: none;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.3;
    }

    .empty-state h4 {
      font-weight: 700;
      color: #666;
      margin-bottom: 8px;
    }

    @media (max-width: 1200px) {
      .sidebar {
        width: 80px;
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

      .search-form {
        flex-direction: column;
      }

      .search-input,
      .filter-select {
        width: 100%;
      }

      .table-section {
        overflow-x: auto;
      }

      .stats-grid {
        grid-template-columns: 1fr;
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
        <i class="bi bi-car-front-fill"></i>
        Car Approval Management
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <?php
      $statsQuery = $conn->query("
        SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
          SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM cars
      ");
      $stats = $statsQuery->fetch_assoc();
      ?>
      
      <div class="stat-card">
        <div class="stat-label">Total Cars</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-icon stat-total">
          <i class="bi bi-car-front"></i>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <div class="stat-icon stat-pending">
          <i class="bi bi-clock-history"></i>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Approved</div>
        <div class="stat-value"><?= $stats['approved'] ?></div>
        <div class="stat-icon stat-approved">
          <i class="bi bi-check-circle"></i>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?= $stats['rejected'] ?></div>
        <div class="stat-icon stat-rejected">
          <i class="bi bi-x-circle"></i>
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <form method="GET" class="search-form">
        <input 
          type="text" 
          name="search" 
          class="search-input" 
          placeholder="Search by owner, brand, model, or plate number..." 
          value="<?= htmlspecialchars($search) ?>">
        
        <select name="status" class="filter-select">
          <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
          <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>

        <button type="submit" class="search-btn">
          <i class="bi bi-search"></i> Search
        </button>
        
        <a href="?" class="reset-btn">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </a>
      </form>
    </div>

    <!-- Cars Table -->
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

          $num = $offset + 1;
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

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination-wrapper">
        <ul class="pagination">
          <!-- Previous Button -->
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>

          <!-- Page Numbers -->
          <?php 
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          
          for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>

          <!-- Next Button -->
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="update_car_status.php">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-x-circle text-danger"></i> Reject Car Listing
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="rejectCarId">
        <p class="text-muted mb-3">Provide a clear reason so the owner understands why their car was rejected.</p>
        <textarea 
          class="form-control" 
          name="remarks" 
          placeholder="Enter rejection reason..." 
          style="height:120px;" 
          required></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="status" value="rejected" class="btn btn-danger">
          <i class="bi bi-x-lg"></i> Confirm Rejection
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Reject Modal Handler
const rejectButtons = document.querySelectorAll(".rejectBtn");
const modal = new bootstrap.Modal(document.getElementById('rejectModal'));

rejectButtons.forEach(btn => {
  btn.onclick = () => {
    document.getElementById("rejectCarId").value = btn.dataset.id;
    modal.show();
  };
});

// Image Viewer (You can enhance this later)
function viewCarImage(src) {
  window.open(src, '_blank');
}
</script>

</body>
</html>