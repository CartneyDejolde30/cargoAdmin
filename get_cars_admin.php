<?php
include "include/db.php";

// Get filter values
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Escape for safety
$search = $conn->real_escape_string($search);
$status = $conn->real_escape_string($status);

// Pagination settings
$limit = 5; // rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max($page, 1);
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
    $sql .= " AND (
        cars.brand LIKE '%$search%' OR
        cars.model LIKE '%$search%' OR
        cars.plate_number LIKE '%$search%' OR
        users.fullname LIKE '%$search%'
    )";
}

// Status filter
if ($status !== "all") {
    $sql .= " AND cars.status = '$status'";
}

// Count total rows for pagination
$countQuery = $conn->query($sql);
$totalRows = $countQuery->num_rows;
$totalPages = ceil($totalRows / $limit);

// Final query with sorting + limit
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
  <link href="include/admin-styles.css" rel="stylesheet">

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
                    <button name="status" value="approved" class="action-btn approve pe-5 ps-5">
                      <i class="bi bi-check-lg"></i> Approve
                    </button>
                  <?php } ?>

                  <?php if($row['status'] !== 'rejected') { ?>
                    <button type="button" 
                            class="action-btn reject rejectBtn pe-5 ps-5" 
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
        <button type="submit" name="status" value="rejected" class="btn btn-danger ">
          <i class="bi bi-x-lg"></i> Confirm Rejection
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
<!-- Image Modal -->
<div class="image-modal" id="imageModal">
  <div class="image-modal-content">
    <div class="image-modal-header">
      <button class="modal-action-btn download" onclick="downloadImage()" title="Download Image">
        <i class="bi bi-download"></i>
      </button>
      <button class="modal-action-btn close" onclick="closeImageModal()" title="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <img id="modalImage" src="" alt="Car Image">
    <div class="image-modal-footer" id="modalCaption"></div>
  </div>
</div>

<!-- Document Modal -->
<div class="doc-modal" id="docModal">
  <div class="doc-modal-content">
    <div class="doc-modal-header">
      <h3 class="doc-modal-title" id="docModalTitle">Document Viewer</h3>
      <div class="doc-modal-actions">
        <a id="docDownloadBtn" href="#" download class="doc-modal-btn download" title="Download Document">
          <i class="bi bi-download"></i>
        </a>
        <button class="doc-modal-btn close" onclick="closeDocModal()" title="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>
    <div class="doc-modal-body" id="docModalBody">
      <!-- Document content will be loaded here -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentImageUrl = '';

// Reject Modal Handler
const rejectButtons = document.querySelectorAll(".rejectBtn");
const modal = new bootstrap.Modal(document.getElementById('rejectModal'));

rejectButtons.forEach(btn => {
  btn.onclick = () => {
    document.getElementById("rejectCarId").value = btn.dataset.id;
    modal.show();
  };
});

// Car Image Viewer
function viewCarImage(imageUrl, carName) {
  const modal = document.getElementById('imageModal');
  const modalImg = document.getElementById('modalImage');
  const caption = document.getElementById('modalCaption');
  
  currentImageUrl = imageUrl;
  modal.classList.add('active');
  modalImg.src = imageUrl;
  caption.textContent = carName || 'Car Image';
  
  // Prevent body scroll when modal is open
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  const modal = document.getElementById('imageModal');
  modal.classList.remove('active');
  document.body.style.overflow = 'auto';
}

function downloadImage() {
  if (!currentImageUrl) return;
  
  // Create a temporary link element
  const link = document.createElement('a');
  link.href = currentImageUrl;
  
  // Extract filename from URL or use default
  const filename = currentImageUrl.split('/').pop() || 'car-image.jpg';
  link.download = filename;
  
  // Trigger download
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Document Viewer
function viewDocument(docUrl, docType) {
  const modal = document.getElementById('docModal');
  const modalBody = document.getElementById('docModalBody');
  const modalTitle = document.getElementById('docModalTitle');
  const downloadBtn = document.getElementById('docDownloadBtn');
  
  // Set title and download link
  modalTitle.textContent = docType;
  downloadBtn.href = docUrl;
  downloadBtn.download = docType.replace(/\s+/g, '_') + '_' + docUrl.split('/').pop();
  
  // Clear previous content
  modalBody.innerHTML = '';
  
  // Check file extension
  const extension = docUrl.split('.').pop().toLowerCase();
  
  if (extension === 'pdf') {
    // For PDF files, use iframe
    const iframe = document.createElement('iframe');
    iframe.src = docUrl;
    modalBody.appendChild(iframe);
  } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
    // For image files, use img tag
    const img = document.createElement('img');
    img.src = docUrl;
    img.alt = docType;
    modalBody.appendChild(img);
  } else {
    // For other file types, use iframe with fallback
    const iframe = document.createElement('iframe');
    iframe.src = docUrl;
    modalBody.appendChild(iframe);
  }
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeDocModal() {
  const modal = document.getElementById('docModal');
  modal.classList.remove('active');
  document.body.style.overflow = 'auto';
}

// Close image modal when clicking outside
document.getElementById('imageModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeImageModal();
  }
});

// Close document modal when clicking outside
document.getElementById('docModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeDocModal();
  }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeImageModal();
    closeDocModal();
  }
});
</script>

</body>
</html>