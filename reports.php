<?php
session_start();
require_once 'include/db.php';

// ---------------------------
// AUTH CHECK
// ---------------------------
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// ---------------------------
// PAGINATION
// ---------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// ---------------------------
// FILTERS
// ---------------------------
$filterType   = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$filterPriority = $_GET['priority'] ?? 'all';
$search       = $_GET['search'] ?? '';
$sortBy       = $_GET['sort'] ?? 'created_at';
$sortOrder    = $_GET['order'] ?? 'DESC';

// Escape inputs
$filterType   = mysqli_real_escape_string($conn, $filterType);
$filterStatus = mysqli_real_escape_string($conn, $filterStatus);
$filterPriority = mysqli_real_escape_string($conn, $filterPriority);
$searchEsc    = mysqli_real_escape_string($conn, $search);
$sortBy       = mysqli_real_escape_string($conn, $sortBy);
$sortOrder    = $sortOrder === 'ASC' ? 'ASC' : 'DESC';

// ---------------------------
// MAIN QUERY
// ---------------------------
$query = "
SELECT 
    r.*,
    COALESCE(reporter.fullname, 'Unknown') AS reporter_name,
    COALESCE(reporter.email, 'N/A') AS reporter_email,
    COALESCE(reporter.phone, 'N/A') AS reporter_phone,
    admin.fullname AS reviewer_name,
    
    CASE 
       WHEN r.report_type = 'car' THEN 
    (SELECT CONCAT(brand, ' ', model, ' (', car_year, ')') FROM cars WHERE id = r.reported_id)
WHEN r.report_type = 'motorcycle' THEN 
    (SELECT CONCAT(brand, ' ', model, ' (', motorcycle_year, ')') FROM motorcycles WHERE id = r.reported_id)

        WHEN r.report_type = 'user' THEN 
            (SELECT fullname FROM users WHERE id = r.reported_id)
        WHEN r.report_type = 'booking' THEN 
            CONCAT('Booking #', r.reported_id)
        WHEN r.report_type = 'chat' THEN 
            CONCAT('Chat #', r.reported_id)
        ELSE 'Unknown'
    END AS reported_item_name,
    
    CASE 
        WHEN r.report_type = 'car' THEN 
            (SELECT owner_id FROM cars WHERE id = r.reported_id)
        WHEN r.report_type = 'motorcycle' THEN 
            (SELECT owner_id FROM motorcycles WHERE id = r.reported_id)
        WHEN r.report_type = 'user' THEN 
            r.reported_id
        ELSE NULL
    END AS reported_user_id,
    
    TIMESTAMPDIFF(HOUR, r.created_at, NOW()) AS hours_pending

FROM reports r
LEFT JOIN users reporter ON r.reporter_id = reporter.id
LEFT JOIN admin ON r.reviewed_by = admin.id
WHERE 1=1
";

// ---------------------------
// APPLY FILTERS
// ---------------------------
if ($filterType !== 'all') {
    $query .= " AND r.report_type = '$filterType' ";
}

if ($filterStatus !== 'all') {
    $query .= " AND r.status = '$filterStatus' ";
}

if ($filterPriority !== 'all') {
    $query .= " AND r.priority = '$filterPriority' ";
}

if (!empty($searchEsc)) {
    $query .= "
        AND (
            reporter.fullname LIKE '%$searchEsc%' OR
            reporter.email LIKE '%$searchEsc%' OR
            r.reason LIKE '%$searchEsc%' OR
            r.details LIKE '%$searchEsc%' OR
            r.id LIKE '%$searchEsc%'
        )
    ";
}

// Count total for pagination
$countQuery = "SELECT COUNT(*) as total FROM ($query) as count_table";
$countResult = mysqli_query($conn, $countQuery);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $perPage);

// Apply sorting and pagination
$query .= " ORDER BY r.$sortBy $sortOrder LIMIT $perPage OFFSET $offset";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL ERROR: " . mysqli_error($conn));
}

// ---------------------------
// STATS QUERY
// ---------------------------
$statsQuery = "
SELECT 
    COUNT(*) AS total,

    SUM(IF(`status` = 'pending', 1, 0)) AS pending,
    SUM(IF(`status` = 'under_review', 1, 0)) AS under_review,
    SUM(IF(`status` = 'resolved', 1, 0)) AS resolved,
    SUM(IF(`status` = 'dismissed', 1, 0)) AS dismissed,

    SUM(IF(`priority` = 'high', 1, 0)) AS `high_priority`,
    SUM(IF(`priority` = 'medium', 1, 0)) AS `medium_priority`,
    SUM(IF(`priority` = 'low', 1, 0)) AS `low_priority`,

    SUM(IF(report_type = 'car', 1, 0)) AS car_reports,
    SUM(IF(report_type = 'motorcycle', 1, 0)) AS motorcycle_reports,
    SUM(IF(report_type = 'user', 1, 0)) AS user_reports,
    SUM(IF(report_type = 'booking', 1, 0)) AS booking_reports,
    SUM(IF(report_type = 'chat', 1, 0)) AS chat_reports,

    SUM(
        IF(
            `status` = 'pending' 
            AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 48,
            1,
            0
        )
    ) AS overdue
FROM reports
";




$statsResult = mysqli_query($conn, $statsQuery);
if (!$statsResult) {
    die("STATS SQL ERROR: " . mysqli_error($conn) . "<br><pre>$statsQuery</pre>");
}

$stats = mysqli_fetch_assoc($statsResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - CarGo Admin</title>
    <?php
$page = basename($_SERVER['PHP_SELF']);

$favicons = [
 
  'reports.php' => 'icons/reports.svg',
 
];

$icon = $favicons[$page] ?? 'icons/dashboard.svg';
?>
<link rel="icon" type="image/svg+xml" href="/carGOAdmin/<?php echo $icon; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="include/admin-styles.css" rel="stylesheet">
    <link href="include/notifications.css" rel="stylesheet">
    <style>
        .priority-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-high { background: #fee; color: #d00; }
        .priority-medium { background: #ffeaa7; color: #d63031; }
        .priority-low { background: #dfe6e9; color: #636e72; }
        .overdue-badge { background: #ff6b6b; color: white; }
        .bulk-actions { display: none; margin-bottom: 20px; }
        .bulk-actions.active { display: block; }
        .report-timeline { max-height: 200px; overflow-y: auto; }
        .timeline-item { 
            padding: 10px;
            border-left: 3px solid #e9ecef;
            margin-bottom: 10px;
        }
        .export-btn { margin-left: 10px; }
    </style>
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
    <div class="notification-dropdown">
        <button class="notification-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notification-badge"></span>
        </button>
    </div>
    <div class="user-avatar">
        <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
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
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
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
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa8231 0%, #f5576c 100%); color: white;">
                        <i class="bi bi-eye"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['under_review']; ?></div>
                <div class="stat-label">Under Review</div>
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

        <!-- Bulk Actions (Hidden by default) -->
        <div class="bulk-actions" id="bulkActions">
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span><strong id="selectedCount">0</strong> reports selected</span>
                <div>
                    <button class="btn btn-sm btn-success" onclick="bulkAction('under_review')">
                        <i class="bi bi-eye"></i> Mark Under Review
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="bulkAction('resolved')">
                        <i class="bi bi-check"></i> Resolve
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('dismissed')">
                        <i class="bi bi-x"></i> Dismiss
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="type" class="filter-select">
                    <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All Types</option>
                    <option value="car" <?= $filterType === 'car' ? 'selected' : '' ?>>
                        Car (<?= $stats['car_reports'] ?>)
                    </option>
                    <option value="motorcycle" <?= $filterType === 'motorcycle' ? 'selected' : '' ?>>
                        Motorcycle (<?= $stats['motorcycle_reports'] ?>)
                    </option>
                    <option value="user" <?= $filterType === 'user' ? 'selected' : '' ?>>
                        User (<?= $stats['user_reports'] ?>)
                    </option>
                    <option value="booking" <?= $filterType === 'booking' ? 'selected' : '' ?>>
                        Booking (<?= $stats['booking_reports'] ?>)
                    </option>
                    <option value="chat" <?= $filterType === 'chat' ? 'selected' : '' ?>>
                        Chat (<?= $stats['chat_reports'] ?>)
                    </option>
                </select>

                <select name="status" class="filter-select">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="under_review" <?= $filterStatus === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                    <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="dismissed" <?= $filterStatus === 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
                </select>

                <select name="priority" class="filter-select">
                    <option value="all" <?= $filterPriority === 'all' ? 'selected' : '' ?>>All Priority</option>
                    <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>High Priority</option>
                    <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                    <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Low Priority</option>
                </select>

                <button type="submit" class="search-btn">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="manage_reports.php" class="reset-btn">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
                <button type="button" class="export-btn btn btn-secondary btn-sm" onclick="exportReports()">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </form>
        </div>

        <!-- Reports Table -->
        <div class="table-section">
            <div class="section-header d-flex justify-content-between align-items-center">
                <h3 class="section-title">
                    Reports List 
                    <small class="text-muted">(<?php echo number_format($totalRecords); ?> total)</small>
                </h3>
                <div>
                    <label>
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> Select All
                    </label>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()"></th>
                            <th>ID</th>
                            <th>Priority</th>
                            <th>Type</th>
                            <th>Reported Item</th>
                            <th>Reporter</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $isOverdue = $row['status'] === 'pending' && $row['hours_pending'] > 48;
                                $rowClass = $isOverdue ? 'table-danger' : ($row['status'] === 'pending' ? 'table-warning' : '');
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <input type="checkbox" class="report-checkbox" value="<?= $row['id'] ?>">
                                    </td>
                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                    <td>
                                        <?php
                                        $priority = $row['priority'] ?? 'medium';
                                        $priorityClass = "priority-$priority";
                                        ?>
                                        <span class="priority-badge <?= $priorityClass ?>">
                                            <?= ucfirst($priority) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="role-badge" style="<?php
                                            echo match($row['report_type']) {
                                                'car' => 'background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);',
                                                'motorcycle' => 'background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);',
                                                'user' => 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);',
                                                'booking' => 'background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);',
                                                'chat' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);',
                                                default => 'background: #6c757d;'
                                            };
                                        ?>">
                                            <?php echo strtoupper($row['report_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['reported_item_name'] ?? 'N/A'); ?></strong>
                                        <?php if ($isOverdue): ?>
                                            <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($row['reporter_name']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($row['reporter_email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['reason']); ?></strong>
                                        <br><small><?php echo substr(htmlspecialchars($row['details']), 0, 50); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo match($row['status']) {
                                                'resolved' => 'approved',
                                                'pending' => 'pending',
                                                'under_review' => 'verified',
                                                'dismissed' => 'rejected',
                                                default => 'pending'
                                            };
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $hours = $row['hours_pending'];
                                        if ($hours < 24) {
                                            echo $hours . 'h ago';
                                        } else {
                                            echo floor($hours / 24) . 'd ago';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view" onclick="viewReport(<?php echo $row['id']; ?>)" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <button class="action-btn" style="background: #ffc107;" onclick="updateStatus(<?php echo $row['id']; ?>, 'under_review')" title="Mark Under Review">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($row['status'] === 'pending' || $row['status'] === 'under_review'): ?>
                                                <button class="action-btn approve" onclick="resolveReport(<?php echo $row['id']; ?>)" title="Resolve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button class="action-btn reject" onclick="dismissReport(<?php echo $row['id']; ?>)" title="Dismiss">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="action-btn" style="background: #6c757d;" onclick="updatePriority(<?php echo $row['id']; ?>)" title="Change Priority">
                                                <i class="bi bi-flag"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">Previous</a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Enhanced View Report Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = window.location.origin + '/carGOAdmin/';



// Checkbox selection
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const selectAll = document.getElementById('selectAll').checked || document.getElementById('selectAllHeader').checked;
    checkboxes.forEach(cb => cb.checked = selectAll);
    updateBulkActions();
}

document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.report-checkbox');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
});

function updateBulkActions() {
    const selected = document.querySelectorAll('.report-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selected.length > 0) {
        bulkActions.classList.add('active');
        selectedCount.textContent = selected.length;
    } else {
        bulkActions.classList.remove('active');
    }
}

function clearSelection() {
    document.querySelectorAll('.report-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAllHeader').checked = false;
    updateBulkActions();
}

function bulkAction(action) {
    const selected = Array.from(document.querySelectorAll('.report-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    const confirmMsg = `Are you sure you want to mark ${selected.length} reports as "${action.replace('_', ' ')}"?`;
    if (!confirm(confirmMsg)) return;
    
    fetch('api/bulk_update_reports.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ report_ids: selected, status: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully updated ${data.updated} reports`);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function viewReport(reportId) {
    const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    
    fetch(`api/get_report_details.php?id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reportDetailsContent').innerHTML = formatReportDetails(data.report);
                modal.show();
            } else {
                alert('Error loading report details');
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
}

function updateStatus(reportId, status) {
    fetch('api/update_report_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `report_id=${reportId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Report marked as ${status.replace('_', ' ')}`);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function resolveReport(reportId) {
    const action = prompt('What action was taken to resolve this report?');
    if (!action) return;
    
    fetch('api/update_report_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `report_id=${reportId}&status=resolved&notes=${encodeURIComponent(action)}`
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

function dismissReport(reportId) {
    const reason = prompt('Reason for dismissing this report:');
    if (!reason) return;
    
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

function updatePriority(reportId) {
    const priority = prompt('Set priority (high/medium/low):');
    if (!priority || !['high', 'medium', 'low'].includes(priority.toLowerCase())) {
        alert('Invalid priority');
        return;
    }
    
    fetch('api/update_report_priority.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `report_id=${reportId}&priority=${priority.toLowerCase()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function exportReports() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'api/export_reports.php?' + params.toString();
}

function formatReportDetails(report) {
    const statusClass = {
        'pending': 'warning',
        'under_review': 'info',
        'resolved': 'success',
        'dismissed': 'secondary'
    }[report.status] || 'secondary';

    const priorityClass = {
        'high': 'danger',
        'medium': 'warning',
        'low': 'secondary'
    }[report.priority] || 'secondary';

   const imageSection = report.image_path
    ? `
    <div class="card mb-3">
        <div class="card-header"><strong>Attached Image</strong></div>
        <div class="card-body text-center">
            <img 
                src="${BASE_URL}${report.image_path}"
                class="img-fluid rounded shadow"
                style="max-height: 350px; cursor: zoom-in;"
                onclick="openImageViewer('${BASE_URL}${report.image_path}')"
                alt="Report Image"
                onerror="this.onerror=null;this.src='https://via.placeholder.com/400x250?text=Image+Not+Found';"
            >
            <p class="mt-2 text-muted">Click image to view full size</p>
        </div>
    </div>
    `
    : `
    <div class="card mb-3">
        <div class="card-header"><strong>Attached Image</strong></div>
        <div class="card-body text-center text-muted">
            No image attached to this report
        </div>
    </div>
    `;


    return `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header"><strong>Report Information</strong></div>
                    <div class="card-body">
                        <p><strong>Report ID:</strong> #${report.id}</p>
                        <p><strong>Type:</strong> <span class="badge bg-primary">${report.report_type}</span></p>
                        <p><strong>Priority:</strong> <span class="badge bg-${priorityClass}">${report.priority || 'medium'}</span></p>
                        <p><strong>Status:</strong> <span class="badge bg-${statusClass}">${report.status}</span></p>
                        <p><strong>Reason:</strong> ${report.reason}</p>
                        <p><strong>Details:</strong><br>${report.details}</p>
                        <p><strong>Created:</strong> ${report.created_at}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header"><strong>Reporter Information</strong></div>
                    <div class="card-body">
                        <p><strong>Name:</strong> ${report.reporter_name}</p>
                        <p><strong>Email:</strong> ${report.reporter_email}</p>
                        <p><strong>Phone:</strong> ${report.reporter_phone || 'N/A'}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Reported Item</strong></div>
                    <div class="card-body">
                        <p><strong>Name:</strong> ${report.reported_item_name}</p>
                        <p><strong>ID:</strong> ${report.reported_id}</p>
                    </div>
                </div>

                ${imageSection}

                ${report.reviewer_name ? `
                <div class="card mb-3">
                    <div class="card-header"><strong>Review Information</strong></div>
                    <div class="card-body">
                        <p><strong>Reviewed by:</strong> ${report.reviewer_name}</p>
                        <p><strong>Review date:</strong> ${report.reviewed_at || 'N/A'}</p>
                        <p><strong>Notes:</strong> ${report.admin_notes || 'None'}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        </div>

        ${report.timeline && report.timeline.length ? `
        <div class="card">
            <div class="card-header"><strong>Activity Timeline</strong></div>
            <div class="card-body report-timeline">
                ${report.timeline.map(item => `
                    <div class="timeline-item">
                        <strong>${item.action}</strong> by ${item.performed_by} 
                        <br><small class="text-muted">${item.created_at}</small>
                        ${item.notes ? `<br><small>${item.notes}</small>` : ''}
                    </div>
                `).join('')}
            </div>
        </div>
        ` : ''}
    `;
}


function openImageViewer(src) {
    const modalHtml = `
        <div class="modal fade" id="imageViewerModal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <button class="btn btn-light ms-auto" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body d-flex justify-content-center align-items-center">
                        <img src="${src}" class="img-fluid rounded shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
    modal.show();

    document.getElementById('imageViewerModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

</script>
<script src="include/notifications.js"></script>
</body>
</html>

<?php $conn->close(); ?>