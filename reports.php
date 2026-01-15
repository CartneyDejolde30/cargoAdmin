<?php
session_start();
require_once 'include/db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "
    SELECT 
        r.*,
        reporter.fullname as reporter_name,
        reporter.email as reporter_email,
        admin.fullname as reviewer_name,
        CASE 
            WHEN r.report_type = 'car' THEN (SELECT CONCAT(brand, ' ', model) FROM cars WHERE id = r.reported_id)
            WHEN r.report_type = 'user' THEN (SELECT fullname FROM users WHERE id = r.reported_id)
            WHEN r.report_type = 'booking' THEN CONCAT('Booking #', r.reported_id)
            WHEN r.report_type = 'chat' THEN CONCAT('Chat #', r.reported_id)
        END as reported_item_name
    FROM reports r
    LEFT JOIN users reporter ON r.reporter_id = reporter.id
    LEFT JOIN admin ON r.reviewed_by = admin.id
    WHERE 1=1
";

if ($filterType !== 'all') {
    $query .= " AND r.report_type = '" . mysqli_real_escape_string($conn, $filterType) . "'";
}

if ($filterStatus !== 'all') {
    $query .= " AND r.status = '" . mysqli_real_escape_string($conn, $filterStatus) . "'";
}

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (reporter.fullname LIKE '%$search%' OR r.reason LIKE '%$search%' OR r.details LIKE '%$search%')";
}

$query .= " ORDER BY r.created_at DESC LIMIT 50";

$result = $conn->query($query);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
        SUM(CASE WHEN report_type = 'car' THEN 1 ELSE 0 END) as car_reports,
        SUM(CASE WHEN report_type = 'user' THEN 1 ELSE 0 END) as user_reports,
        SUM(CASE WHEN report_type = 'booking' THEN 1 ELSE 0 END) as booking_reports,
        SUM(CASE WHEN report_type = 'chat' THEN 1 ELSE 0 END) as chat_reports
    FROM reports
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - CarGo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="include/admin-styles.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
    <?php include('include/sidebar.php'); ?>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">
                <i class="bi bi-flag"></i>
                Reports Management
            </h1>
            <div class="user-profile">
                <button class="notification-btn">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge"><?php echo $stats['pending']; ?></span>
                </button>
                <div class="user-avatar">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=1a1a1a&color=fff" alt="Admin">
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <i class="bi bi-flag"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Reports</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%); color: white;">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['dismissed']; ?></div>
                <div class="stat-label">Dismissed</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="type" class="filter-select">
                    <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="car" <?php echo $filterType === 'car' ? 'selected' : ''; ?>>Car Reports (<?php echo $stats['car_reports']; ?>)</option>
                    <option value="user" <?php echo $filterType === 'user' ? 'selected' : ''; ?>>User Reports (<?php echo $stats['user_reports']; ?>)</option>
                    <option value="booking" <?php echo $filterType === 'booking' ? 'selected' : ''; ?>>Booking Reports (<?php echo $stats['booking_reports']; ?>)</option>
                    <option value="chat" <?php echo $filterType === 'chat' ? 'selected' : ''; ?>>Chat Reports (<?php echo $stats['chat_reports']; ?>)</option>
                </select>
                <select name="status" class="filter-select">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="under_review" <?php echo $filterStatus === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                    <option value="resolved" <?php echo $filterStatus === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="dismissed" <?php echo $filterStatus === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                </select>
                <button type="submit" class="search-btn">
                    <i class="bi bi-search"></i>
                    Filter
                </button>
                <a href="manage_reports.php" class="reset-btn">
                    <i class="bi bi-arrow-clockwise"></i>
                    Reset
                </a>
            </form>
        </div>

        <!-- Reports Table -->
        <div class="table-section">
            <div class="section-header">
                <h3 class="section-title">Reports List</h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Reported Item</th>
                            <th>Reporter</th>
                            <th>Reason</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr style="<?php echo $row['status'] === 'pending' ? 'background-color: #fff3cd;' : ''; ?>">
                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                    <td>
                                        <span class="role-badge" style="<?php
                                            echo $row['report_type'] === 'car' ? 'background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);' : 
                                                 ($row['report_type'] === 'user' ? 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);' : 
                                                 ($row['report_type'] === 'booking' ? 'background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);' : 
                                                 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'));
                                        ?>">
                                            <?php echo strtoupper($row['report_type']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['reported_item_name'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($row['reporter_name']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($row['reporter_email']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td>
                                        <small><?php echo substr(htmlspecialchars($row['details']), 0, 50); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo $row['status'] === 'resolved' ? 'approved' : 
                                                 ($row['status'] === 'pending' ? 'pending' : 
                                                 ($row['status'] === 'under_review' ? 'verified' : 'rejected')); 
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view" onclick="viewReport(<?php echo $row['id']; ?>)" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($row['status'] === 'pending' || $row['status'] === 'under_review'): ?>
                                                <button class="action-btn approve" onclick="resolveReport(<?php echo $row['id']; ?>)" title="Resolve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button class="action-btn reject" onclick="dismissReport(<?php echo $row['id']; ?>)" title="Dismiss">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h4>No Reports Found</h4>
                                        <p>There are no reports matching your criteria</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- View Report Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewReport(reportId) {
    // Implement AJAX call to fetch report details
    const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    
    fetch(`api/get_report_details.php?id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reportDetailsContent').innerHTML = formatReportDetails(data.report);
                modal.show();
            }
        });
}

function resolveReport(reportId) {
    if (confirm('Mark this report as resolved?')) {
        fetch('api/update_report_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `report_id=${reportId}&status=resolved`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Report resolved successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function dismissReport(reportId) {
    const reason = prompt('Reason for dismissing this report:');
    if (reason) {
        fetch('api/update_report_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `report_id=${reportId}&status=dismissed&notes=${encodeURIComponent(reason)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Report dismissed');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function formatReportDetails(report) {
    return `
        <div class="detail-section">
            <h6><strong>Report Information</strong></h6>
            <p><strong>Type:</strong> ${report.report_type}</p>
            <p><strong>Reason:</strong> ${report.reason}</p>
            <p><strong>Details:</strong> ${report.details}</p>
            <p><strong>Status:</strong> <span class="status-badge">${report.status}</span></p>
        </div>
        <div class="detail-section">
            <h6><strong>Reporter Information</strong></h6>
            <p><strong>Name:</strong> ${report.reporter_name}</p>
            <p><strong>Email:</strong> ${report.reporter_email}</p>
            <p><strong>Reported Date:</strong> ${report.created_at}</p>
        </div>
    `;
}
</script>

</body>
</html>

<?php $conn->close(); ?>