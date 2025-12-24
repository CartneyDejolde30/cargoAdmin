<?php
include "include/db.php";

// Get filter values
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$search = $conn->real_escape_string($search);
$status = $conn->real_escape_string($status);

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Base query
$sql = "
    SELECT motorcycles.*, users.fullname 
    FROM motorcycles 
    JOIN users ON users.id = motorcycles.owner_id 
    WHERE 1
";

// Search filter
if (!empty($search)) {
    $sql .= " AND (
        motorcycles.brand LIKE '%$search%' OR
        motorcycles.model LIKE '%$search%' OR
        motorcycles.plate_number LIKE '%$search%' OR
        users.fullname LIKE '%$search%'
    )";
}

// Status filter
if ($status !== "all") {
    $sql .= " AND motorcycles.status = '$status'";
}

// Count total
$countQuery = $conn->query($sql);
$totalRows = $countQuery->num_rows;
$totalPages = ceil($totalRows / $limit);

// Final query
$sql .= " ORDER BY motorcycles.created_at DESC LIMIT $limit OFFSET $offset";
$query = $conn->query($sql);

// Get stats
$statsQuery = $conn->query("
  SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
  FROM motorcycles
");
$stats = $statsQuery->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Motorcycle Management | CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  
  <style>
    /* Enhanced Animations */
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .fade-in {
      animation: fadeIn 0.5s ease-in;
    }

    /* Enhanced Stats Cards */
    .stats-grid {
      animation: slideInUp 0.6s ease-out;
    }

    .stat-card {
      position: relative;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .stat-card:hover::before {
      left: 100%;
    }

    .stat-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    /* Motorcycle-specific stat icons */
    .stat-icon.stat-total {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.stat-pending {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-icon.stat-approved {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-icon.stat-rejected {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    /* Enhanced Table */
    .table-section {
      animation: slideInUp 0.8s ease-out;
    }

    tbody tr {
      transition: all 0.3s ease;
      position: relative;
    }

    tbody tr::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 4px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    tbody tr:hover::before {
      transform: scaleY(1);
    }

    tbody tr:hover {
      background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, transparent 100%);
      transform: translateX(4px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    }

    /* Motorcycle Thumbnail */
    .motorcycle-thumb {
      width: 80px;
      height: 60px;
      border-radius: 12px;
      object-fit: cover;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      cursor: pointer;
      transition: all 0.3s ease;
      border: 3px solid #f0f0f0;
    }

    .motorcycle-thumb:hover {
      transform: scale(1.15) rotate(2deg);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
      border-color: #667eea;
    }

    /* Enhanced Status Badges */
    .status-badge {
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .status-badge::before {
      content: '';
      position: absolute;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      left: 8px;
      animation: pulse 2s infinite;
    }

    .status-badge.pending {
      background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
      color: #664d03;
    }

    .status-badge.pending::before {
      background: #ffc107;
    }

    .status-badge.approved {
      background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
      color: #0f5132;
    }

    .status-badge.approved::before {
      background: #198754;
    }

    .status-badge.rejected {
      background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
      color: #842029;
    }

    .status-badge.rejected::before {
      background: #dc3545;
    }

    /* Enhanced Action Buttons */
    .action-btn {
      position: relative;
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .action-btn::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .action-btn:hover::after {
      width: 300px;
      height: 300px;
    }

    .action-btn.approve {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    }

    .action-btn.approve:hover {
      box-shadow: 0 6px 25px rgba(79, 172, 254, 0.5);
      transform: translateY(-3px);
    }

    .action-btn.reject {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
      box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
    }

    .action-btn.reject:hover {
      box-shadow: 0 6px 25px rgba(250, 112, 154, 0.5);
      transform: translateY(-3px);
    }

    /* Enhanced Document Buttons */
    .doc-btn {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 700;
      text-decoration: none;
      display: inline-block;
      margin-right: 6px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .doc-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.4s;
    }

    .doc-btn:hover::before {
      left: 100%;
    }

    .doc-btn.or {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
    }

    .doc-btn.or:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.5);
    }

    .doc-btn.cr {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      box-shadow: 0 3px 10px rgba(240, 147, 251, 0.3);
    }

    .doc-btn.cr:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(240, 147, 251, 0.5);
    }

    /* Enhanced Search Section */
    .search-section {
      animation: slideInUp 0.7s ease-out;
      background: white;
      border-radius: 20px;
      padding: 25px 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
    }

    .search-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    }

    /* Enhanced Empty State */
    .empty-state {
      padding: 80px 20px;
      text-align: center;
    }

    .empty-state i {
      font-size: 80px;
      color: #e0e0e0;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
    }

    .empty-state h4 {
      font-size: 24px;
      font-weight: 700;
      color: #666;
      margin-bottom: 12px;
    }

    .empty-state p {
      font-size: 14px;
      color: #999;
    }

    /* Enhanced Modals */
    .modal-content {
      border-radius: 20px;
      border: none;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      padding: 20px 30px;
    }

    .modal-header.bg-danger {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }

    .modal-title {
      font-weight: 700;
      color: white;
    }

    .modal-body {
      padding: 30px;
    }

    .modal-footer {
      border: none;
      padding: 20px 30px;
      background: #f8f9fa;
    }

    /* Loading State */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.95);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .loading-overlay.active {
      display: flex;
    }

    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 5px solid #f0f0f0;
      border-top: 5px solid #667eea;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Tooltip Enhancement */
    [data-tooltip] {
      position: relative;
      cursor: pointer;
    }

    [data-tooltip]::after {
      content: attr(data-tooltip);
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%) translateY(-8px);
      padding: 8px 12px;
      background: rgba(0, 0, 0, 0.9);
      color: white;
      font-size: 12px;
      border-radius: 6px;
      white-space: nowrap;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s, transform 0.3s;
      z-index: 1000;
    }

    [data-tooltip]:hover::after {
      opacity: 1;
      transform: translateX(-50%) translateY(-4px);
    }

    /* Responsive Enhancements */
    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .stat-value {
        font-size: 2rem;
      }

      .motorcycle-thumb {
        width: 60px;
        height: 45px;
      }

      .action-btn {
        padding: 8px 12px;
        font-size: 11px;
      }
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include('include/sidebar.php'); ?>

  <main class="main-content">
    <!-- Enhanced Top Bar -->
    <div class="top-bar fade-in">
      <h1 class="page-title">
        <i class="bi bi-bicycle"></i>
        Motorcycle Management
      </h1>
      <div class="user-profile">
        <button class="notification-btn" data-tooltip="Notifications">
          <i class="bi bi-bell"></i>
          <?php if($stats['pending'] > 0): ?>
            <span class="notification-badge"><?= $stats['pending'] ?></span>
          <?php endif; ?>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=667eea&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Enhanced Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card" data-tooltip="View all motorcycles">
        <div class="stat-header">
          <div class="stat-icon stat-total">
            <i class="bi bi-bicycle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-graph-up"></i>
            100%
          </div>
        </div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Motorcycles</div>
        <div class="stat-detail">All registered motorcycles</div>
      </div>

      <div class="stat-card" data-tooltip="Motorcycles awaiting approval">
        <div class="stat-header">
          <div class="stat-icon stat-pending">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-exclamation-circle"></i>
            Review
          </div>
        </div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Approval</div>
        <div class="stat-detail">Needs your attention</div>
      </div>

      <div class="stat-card" data-tooltip="Active motorcycles">
        <div class="stat-header">
          <div class="stat-icon stat-approved">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            Active
          </div>
        </div>
        <div class="stat-value"><?= $stats['approved'] ?></div>
        <div class="stat-label">Approved</div>
        <div class="stat-detail">Ready for rental</div>
      </div>

      <div class="stat-card" data-tooltip="Rejected submissions">
        <div class="stat-header">
          <div class="stat-icon stat-rejected">
            <i class="bi bi-x-circle"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i>
            Declined
          </div>
        </div>
        <div class="stat-value"><?= $stats['rejected'] ?></div>
        <div class="stat-label">Rejected</div>
        <div class="stat-detail">Did not meet criteria</div>
      </div>
    </div>

    <!-- Enhanced Search Section -->
    <div class="search-section">
      <form method="GET" class="search-form">
        <input 
          type="text" 
          name="search" 
          class="search-input" 
          placeholder="🔍 Search by owner, brand, model, or plate number..." 
          value="<?= htmlspecialchars($search) ?>">
        
        <select name="status" class="filter-select">
          <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>📋 All Status</option>
          <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
          <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>✅ Approved</option>
          <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>❌ Rejected</option>
        </select>

        <button type="submit" class="search-btn">
          <i class="bi bi-search"></i> Search
        </button>
        
        <a href="?" class="reset-btn">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </a>
      </form>
    </div>

    <!-- Enhanced Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">
          <i class="bi bi-list-ul me-2"></i>
          Motorcycle Listings
        </h2>
        <div class="table-controls">
          <a href="?status=all" class="table-btn <?= $status === 'all' ? 'active' : '' ?>">
            All (<?= $stats['total'] ?>)
          </a>
          <a href="?status=pending" class="table-btn <?= $status === 'pending' ? 'active' : '' ?>">
            Pending (<?= $stats['pending'] ?>)
          </a>
          <a href="?status=approved" class="table-btn <?= $status === 'approved' ? 'active' : '' ?>">
            Active (<?= $stats['approved'] ?>)
          </a>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th><i class="bi bi-hash me-1"></i>#</th>
              <th><i class="bi bi-person me-1"></i>Owner</th>
              <th><i class="bi bi-bicycle me-1"></i>Motorcycle Details</th>
              <th><i class="bi bi-credit-card me-1"></i>Plate</th>
              <th><i class="bi bi-shield-check me-1"></i>Status</th>
              <th><i class="bi bi-image me-1"></i>Image</th>
              <th><i class="bi bi-file-earmark me-1"></i>Docs</th>
              <th><i class="bi bi-gear me-1"></i>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($query->num_rows === 0) {
              echo '<tr><td colspan="8" class="empty-state">
                      <i class="bi bi-bicycle"></i>
                      <h4>No motorcycles found</h4>
                      <p>Try adjusting your search or filter criteria</p>
                    </td></tr>';
            }

            $num = $offset + 1;
            while($row = $query->fetch_assoc()) { ?>
            <tr>
              <td>
                <strong class="text-primary">#<?= $num++ ?></strong>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['fullname']) ?>&background=667eea&color=fff">
                  </div>
                  <span><?= htmlspecialchars($row['fullname']) ?></span>
                </div>
              </td>
              <td>
                <strong><?= htmlspecialchars($row['brand']) ?></strong><br>
                <small class="text-muted">
                  <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($row['model']) ?> • <?= $row['motorcycle_year'] ?>
                </small><br>
                <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 10px;">
                  <i class="bi bi-speedometer me-1"></i><?= htmlspecialchars($row['engine_displacement'] ?? 'N/A') ?>
                </span>
              </td>
              <td>
                <strong style="font-family: monospace;"><?= htmlspecialchars($row['plate_number']) ?></strong>
              </td>
              <td>
                <span class="status-badge <?= $row['status'] ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
              </td>
              <td>
                <?php if(!empty($row['image'])) { ?>
                  <img src="<?= htmlspecialchars($row['image']) ?>" 
                       class="motorcycle-thumb"
                       onclick="viewCarImage('<?= htmlspecialchars($row['image']) ?>', '<?= htmlspecialchars($row['brand'].' '.$row['model']) ?>')"
                       alt="Motorcycle">
                <?php } else { ?>
                  <span class="text-muted">
                    <i class="bi bi-image-fill"></i> No Image
                  </span>
                <?php } ?>
              </td>
              <td>
                <a href="javascript:void(0)"
                   class="doc-btn or"
                   onclick="viewDocument('<?= htmlspecialchars($row['official_receipt']) ?>','Official Receipt')"
                   data-tooltip="View Official Receipt">
                   <i class="bi bi-file-earmark-text me-1"></i>OR
                </a>
                <a href="javascript:void(0)"
                   class="doc-btn cr"
                   onclick="viewDocument('<?= htmlspecialchars($row['certificate_of_registration']) ?>','Certificate of Registration')"
                   data-tooltip="View CR">
                   <i class="bi bi-file-earmark-check me-1"></i>CR
                </a>
              </td>
              <td>
                <div class="action-buttons">
                  <form method="POST" action="update_motorcycle_status.php" style="display: contents;">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    
                    <?php if($row['status'] !== 'approved') { ?>
                      <button name="status" value="approved" class="action-btn approve" data-tooltip="Approve this motorcycle">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    <?php } ?>

                    <?php if($row['status'] !== 'rejected') { ?>
                      <button type="button" 
                              class="action-btn reject rejectBtn" 
                              data-id="<?= $row['id'] ?>"
                              data-tooltip="Reject this motorcycle">
                        <i class="bi bi-x-lg"></i>
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

      <!-- Enhanced Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination-section">
        <div class="pagination-info">
          <i class="bi bi-info-circle me-2"></i>
          Showing <strong><?= $offset + 1 ?></strong> - <strong><?= min($offset + $limit, $totalRows) ?></strong> of <strong><?= $totalRows ?></strong> motorcycles
        </div>
        <div class="pagination-controls">
          <?php if($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn">
              <i class="bi bi-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php 
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          
          for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" 
               class="page-btn <?= $i == $page ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn">
              <i class="bi bi-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-spinner"></div>
</div>

<!-- Enhanced Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="update_motorcycle_status.php">
      <div class="modal-header bg-danger">
        <h5 class="modal-title">
          <i class="bi bi-x-circle-fill me-2"></i>Reject Motorcycle Listing
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="rejectCarId">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Important:</strong> The owner will be notified of this rejection.
        </div>
        <label class="form-label fw-bold">
          <i class="bi bi-pencil-square me-2"></i>Reason for Rejection
        </label>
        <textarea 
          class="form-control" 
          name="remarks" 
          placeholder="Please provide a clear reason for rejection..." 
          style="height:140px;" 
          required></textarea>
        <small class="text-muted mt-2 d-block">
          <i class="bi bi-info-circle me-1"></i>
          Be specific about what needs to be corrected or improved.
        </small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-2"></i>Cancel
        </button>
        <button type="submit" name="status" value="rejected" class="btn btn-danger">
          <i class="bi bi-send-fill me-2"></i>Submit Rejection
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Enhanced Image Modal -->
<div class="image-modal" id="imageModal">
  <div class="image-modal-content">
    <div class="image-modal-header">
      <button class="modal-action-btn download" onclick="downloadImage()" data-tooltip="Download Image">
        <i class="bi bi-download"></i>
      </button>
      <button class="modal-action-btn close" onclick="closeImageModal()" data-tooltip="Close (ESC)">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <img id="modalImage" src="" alt="Motorcycle Image">
    <div class="image-modal-footer" id="modalCaption"></div>
  </div>
</div>

<!-- Enhanced Document Modal -->
<div class="doc-modal" id="docModal">
  <div class="doc-modal-content">
    <div class="doc-modal-header">
      <h3 class="doc-modal-title" id="docModalTitle">
        <i class="bi bi-file-earmark-text me-2"></i>Document Viewer
      </h3>
      <div class="doc-modal-actions">
        <a id="docDownloadBtn" class="doc-modal-btn download" download data-tooltip="Download Document">
          <i class="bi bi-download"></i>
        </a>
        <button class="doc-modal-btn close" onclick="closeDocModal()" data-tooltip="Close (ESC)">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>
    <div class="doc-modal-body" id="docModalBody">
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading document...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  let currentImageUrl = '';

  // Reject Modal
  document.querySelectorAll(".rejectBtn").forEach(btn => {
    btn.addEventListener("click", function () {
      const input = document.getElementById("rejectCarId");
      const modalEl = document.getElementById("rejectModal");
      if (!input || !modalEl) return;
      input.value = btn.dataset.id;
      new bootstrap.Modal(modalEl).show();
    });
  });

  // Loading overlay for form submissions
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      document.getElementById('loadingOverlay').classList.add('active');
    });
  });

  // Image Modal
  window.viewCarImage = function (imageUrl, motorcycleName) {
    const modal = document.getElementById("imageModal");
    const img = document.getElementById("modalImage");
    const caption = document.getElementById("modalCaption");
    if (!modal || !img) return;
    
    currentImageUrl = imageUrl;
    img.src = imageUrl;
    caption.innerHTML = `<i class="bi bi-bicycle me-2"></i>${motorcycleName || "Motorcycle Image"}`;
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  };

  window.closeImageModal = function () {
    const modal = document.getElementById("imageModal");
    if (!modal) return;
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
  };

  window.downloadImage = function () {
    if (!currentImageUrl) return;
    const a = document.createElement("a");
    a.href = currentImageUrl;
    a.download = currentImageUrl.split("/").pop() || "motorcycle.jpg";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };

  // Document Modal
  window.viewDocument = function (docUrl, docType) {
    const modal = document.getElementById("docModal");
    const body = document.getElementById("docModalBody");
    const title = document.getElementById("docModalTitle");
    const downloadBtn = document.getElementById("docDownloadBtn");
    if (!modal || !body || !downloadBtn) return;
    
    title.innerHTML = `<i class="bi bi-file-earmark-text me-2"></i>${docType}`;
    downloadBtn.href = docUrl;
    downloadBtn.download = docUrl.split("/").pop();
    
    body.innerHTML = `
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading ${docType.toLowerCase()}...</p>
      </div>
    `;
    
    const ext = docUrl.split(".").pop().toLowerCase();
    
    if (ext === "pdf") {
      const iframe = document.createElement("iframe");
      iframe.src = docUrl;
      iframe.style.width = "100%";
      iframe.style.height = "100%";
      iframe.style.border = "none";
      iframe.onload = () => body.innerHTML = '';
      iframe.onerror = () => {
        body.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Failed to load PDF. Please download to view.
          </div>
        `;
      };
      setTimeout(() => {
        body.innerHTML = '';
        body.appendChild(iframe);
      }, 500);
    } else {
      const img = document.createElement("img");
      img.src = docUrl;
      img.style.maxWidth = "100%";
      img.style.borderRadius = "8px";
      img.onload = () => {
        setTimeout(() => {
          body.innerHTML = '';
          body.appendChild(img);
        }, 300);
      };
      img.onerror = () => {
        body.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Failed to load image.
          </div>
        `;
      };
    }
    
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  };

  window.closeDocModal = function () {
    const modal = document.getElementById("docModal");
    if (!modal) return;
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
  };

  // Click outside to close
  document.getElementById("imageModal")?.addEventListener("click", e => {
    if (e.target.id === "imageModal") closeImageModal();
  });
  
  document.getElementById("docModal")?.addEventListener("click", e => {
    if (e.target.id === "docModal") closeDocModal();
  });

  // ESC key close
  document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
      closeImageModal();
      closeDocModal();
    }
  });

  // Animate number counters
  document.querySelectorAll('.stat-value').forEach(stat => {
    const finalValue = parseInt(stat.textContent);
    let currentValue = 0;
    const increment = Math.ceil(finalValue / 30);
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= finalValue) {
        stat.textContent = finalValue;
        clearInterval(timer);
      } else {
        stat.textContent = currentValue;
      }
    }, 30);
  });
});
</script>

</body>
</html>