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
  
  <!-- Include shared CSS -->
  <link href="include/admin-styles.css" rel="stylesheet">
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
          View All
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>

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
                <img src="<?= htmlspecialchars($row['image']) ?>" 
                     class="car-thumb" 
                     onclick="viewCarImage('<?= htmlspecialchars($row['image']) ?>', '<?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?>')" 
                     alt="Car">
              <?php } else { ?>
                <span class="text-muted">No Image</span>
              <?php } ?>
            </td>
            <td>
              <a href="javascript:void(0)" 
                 onclick="viewDocument('<?= htmlspecialchars($row['official_receipt']) ?>', 'Official Receipt')"
                 class="doc-btn or" title="Official Receipt">OR</a>
              <a href="javascript:void(0)" 
                 onclick="viewDocument('<?= htmlspecialchars($row['certificate_of_registration']) ?>', 'Certificate of Registration')"
                 class="doc-btn cr" title="Certificate of Registration">CR</a>
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
  </main>
</div>

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