<?php
/**
 * Damage Reports Admin Panel
 */
session_start();
require_once 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
require_once 'include/admin_profile.php';

// Pagination
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery  = trim($_GET['search'] ?? '');

// Build WHERE
$where  = ['1=1'];
$params = [];
$types  = '';

if ($statusFilter !== 'all') {
    $where[]  = 'dr.status = ?';
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($searchQuery !== '') {
    $where[]  = '(renter.fullname LIKE ? OR owner.fullname LIKE ? OR b.id LIKE ?)';
    $like     = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

$whereClause = implode(' AND ', $where);

// Count
$countSql  = "SELECT COUNT(*) AS total FROM damage_reports dr
              JOIN bookings b ON dr.booking_id = b.id
              LEFT JOIN users renter ON dr.renter_id = renter.id
              LEFT JOIN users owner  ON dr.owner_id  = owner.id
              WHERE $whereClause";
$cStmt = $conn->prepare($countSql);
if (!empty($params)) $cStmt->bind_param($types, ...$params);
$cStmt->execute();
$totalRecords = $cStmt->get_result()->fetch_assoc()['total'];
$totalPages   = max(1, ceil($totalRecords / $perPage));

// Fetch
$sql = "SELECT
            dr.*,
            b.pickup_date, b.return_date,
            b.security_deposit_amount,
            CASE
                WHEN b.vehicle_type = 'car'        THEN CONCAT(c.brand, ' ', c.model)
                WHEN b.vehicle_type = 'motorcycle' THEN CONCAT(m.brand, ' ', m.model)
            END AS vehicle_name,
            renter.fullname AS renter_name,
            renter.email    AS renter_email,
            owner.fullname  AS owner_name,
            owner.email     AS owner_email
        FROM damage_reports dr
        JOIN bookings b        ON dr.booking_id = b.id
        LEFT JOIN cars c       ON b.car_id = c.id AND b.vehicle_type = 'car'
        LEFT JOIN motorcycles m ON b.car_id = m.id AND b.vehicle_type = 'motorcycle'
        LEFT JOIN users renter  ON dr.renter_id = renter.id
        LEFT JOIN users owner   ON dr.owner_id  = owner.id
        WHERE $whereClause
        ORDER BY dr.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types   .= 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Stats
$statsRes = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(status = 'pending')  AS pending_count,
    SUM(status = 'approved') AS approved_count,
    SUM(status = 'rejected') AS rejected_count,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN approved_amount END), 0) AS total_deducted
FROM damage_reports");
$stats = $statsRes->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damage Reports - CarGo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="include/admin-styles.css" rel="stylesheet">
    <link href="include/notifications.css" rel="stylesheet">
    <link href="include/modal-theme-standardized.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fafafa;
        }

        /* ── Modern Stats Grid ── */
        .modern-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .modern-stat-card {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 1.75rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 3px;
            background: #000;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .modern-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: #000;
        }

        .modern-stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-header-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .stat-icon-modern {
            width: 56px; height: 56px;
            background: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .modern-stat-card:hover .stat-icon-modern {
            transform: rotate(5deg) scale(1.05);
        }

        .stat-badge {
            padding: 6px 12px;
            background: #f5f5f5;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value-modern {
            font-size: 2.25rem;
            font-weight: 800;
            color: #000;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .stat-label-modern {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
        }

        .stat-sub-modern {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        /* ── Modern Filter Section ── */
        .modern-filter-section {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 1rem;
            align-items: center;
        }

        .modern-search-box {
            position: relative;
        }

        .modern-search-box i {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .modern-search-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #fafafa;
            font-family: inherit;
        }

        .modern-search-input:focus {
            outline: none;
            border-color: #000;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        .modern-filter-dropdown {
            padding: 0.875rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fafafa;
            min-width: 180px;
            font-family: inherit;
        }

        .modern-filter-dropdown:focus {
            outline: none;
            border-color: #000;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        .modern-btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
            white-space: nowrap;
        }

        .modern-btn-primary {
            background: #000;
            color: white;
        }

        .modern-btn-primary:hover {
            background: #1a1a1a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .modern-btn-secondary {
            background: white;
            color: #000;
            border: 1px solid #e5e5e5;
        }

        .modern-btn-secondary:hover {
            background: #fafafa;
            border-color: #000;
        }

        /* ── Modern Table ── */
        .modern-table-card {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
        }

        .modern-table-header {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modern-table-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #000;
        }

        .modern-table-count {
            font-size: 0.8125rem;
            color: #666;
            font-weight: 500;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .modern-table thead th {
            padding: 0.875rem 1rem;
            background: #fafafa;
            border-bottom: 1px solid #e5e5e5;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            white-space: nowrap;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s ease;
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background: #fafafa;
        }

        .modern-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* ── Badges ── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending  { background: #fef9ec; color: #92650a; border: 1px solid #fde68a; }
        .status-badge.approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .status-badge.rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .damage-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #fef2f2;
            color: #991b1b;
            margin: 2px;
        }

        /* ── Thumbnails ── */
        .thumb-grid { display: flex; gap: 5px; flex-wrap: wrap; }
        .thumb-img {
            width: 44px; height: 44px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #e5e5e5;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .thumb-img:hover {
            transform: scale(1.08);
            border-color: #000;
        }

        /* ── Action Buttons ── */
        .action-btn-modern {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            font-family: inherit;
            white-space: nowrap;
        }

        .action-btn-dark {
            background: #000;
            color: white;
        }

        .action-btn-dark:hover {
            background: #1a1a1a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .action-btn-outline {
            background: white;
            color: #000;
            border: 1px solid #e5e5e5;
        }

        .action-btn-outline:hover {
            border-color: #000;
            background: #fafafa;
        }

        /* ── Pagination ── */
        .modern-pagination {
            padding: 1.25rem 1.75rem;
            border-top: 1px solid #f5f5f5;
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .modern-page-btn {
            width: 36px; height: 36px;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            background: white;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .modern-page-btn:hover {
            border-color: #000;
            color: #000;
        }

        .modern-page-btn.active {
            background: #000;
            border-color: #000;
            color: white;
        }

        /* ── Modal detail styles ── */
        .detail-label { font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
        .detail-value { font-size: 14px; font-weight: 600; color: #000; margin-top: 4px; }

        /* ── Empty state ── */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state-icon {
            width: 72px; height: 72px;
            background: #f5f5f5;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 28px;
            color: #999;
        }

        #imgModal img { max-height: 80vh; object-fit: contain; width: 100%; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <?php include 'include/sidebar.php'; ?>

    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">
                <i class="bi bi-tools"></i>
                Damage Reports
            </h1>
            <div class="user-profile">
                <div class="notification-dropdown">
                    <button class="notification-btn" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                </div>
                <div class="user-avatar">
                    <img src="<?= $currentAdminAvatarUrl ?>"
                         alt="<?= htmlspecialchars($currentAdminName) ?>"
                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($currentAdminName) ?>&background=1a1a1a&color=fff';">
                </div>
            </div>
        </div>

        <!-- Modern Stats Grid -->
        <div class="modern-stats-grid">
            <!-- All Reports -->
            <div class="modern-stat-card">
                <div class="stat-header-modern">
                    <div class="stat-icon-modern">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <span class="stat-badge">All</span>
                </div>
                <div class="stat-value-modern"><?= number_format($stats['total']) ?></div>
                <div class="stat-label-modern">Total Reports</div>
            </div>

            <!-- Pending Review -->
            <div class="modern-stat-card">
                <div class="stat-header-modern">
                    <div class="stat-icon-modern">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <span class="stat-badge" style="background:#fef9ec;color:#92650a;">Needs Action</span>
                </div>
                <div class="stat-value-modern"><?= number_format($stats['pending_count']) ?></div>
                <div class="stat-label-modern">Pending Review</div>
            </div>

            <!-- Approved -->
            <div class="modern-stat-card">
                <div class="stat-header-modern">
                    <div class="stat-icon-modern">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <span class="stat-badge" style="background:#f0fdf4;color:#166534;">Approved</span>
                </div>
                <div class="stat-value-modern"><?= number_format($stats['approved_count']) ?></div>
                <div class="stat-label-modern">Approved &amp; Deducted</div>
                <div class="stat-sub-modern">₱<?= number_format($stats['total_deducted'], 2) ?> total deducted</div>
            </div>

            <!-- Rejected -->
            <div class="modern-stat-card">
                <div class="stat-header-modern">
                    <div class="stat-icon-modern">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <span class="stat-badge" style="background:#fef2f2;color:#991b1b;">Closed</span>
                </div>
                <div class="stat-value-modern"><?= number_format($stats['rejected_count']) ?></div>
                <div class="stat-label-modern">Rejected</div>
            </div>
        </div>

        <!-- Modern Filter Section -->
        <div class="modern-filter-section">
            <form method="GET">
                <div class="filter-grid">
                    <div class="modern-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="modern-search-input"
                               placeholder="Search by renter, owner, or booking ID..."
                               value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <select name="status" class="modern-filter-dropdown">
                        <option value="all"     <?= $statusFilter === 'all'      ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending"  <?= $statusFilter === 'pending'  ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <button type="submit" class="modern-btn modern-btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="damage_reports.php" class="modern-btn modern-btn-secondary" style="text-decoration:none;">
                        <i class="bi bi-x"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Modern Table Card -->
        <div class="modern-table-card">
            <div class="modern-table-header">
                <span class="modern-table-title">Damage Reports</span>
                <span class="modern-table-count">
                    <?= number_format($totalRecords) ?> report<?= $totalRecords !== 1 ? 's' : '' ?> found
                </span>
            </div>

            <?php if (empty($reports)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <div style="font-size:15px;font-weight:600;color:#333;margin-bottom:6px;">No damage reports found</div>
                <div style="font-size:13px;color:#999;">Try adjusting your filters or search query.</div>
            </div>
            <?php else: ?>

            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="padding-left:1.75rem;">Report</th>
                            <th>Vehicle / Booking</th>
                            <th>Renter</th>
                            <th>Owner</th>
                            <th>Photos</th>
                            <th>Est. Cost</th>
                            <th>Status</th>
                            <th>Date Filed</th>
                            <th style="padding-right:1.75rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $r): ?>
                    <?php
                        $dtypes = json_decode($r['damage_types'] ?? '[]', true) ?: [];
                        $imgs   = array_filter([$r['image_1'], $r['image_2'], $r['image_3'], $r['image_4']]);
                        $statusClass = match($r['status']) {
                            'approved' => 'approved',
                            'rejected' => 'rejected',
                            default    => 'pending',
                        };
                    ?>
                    <tr>
                        <td style="padding-left:1.75rem;">
                            <div style="font-weight:700;color:#000;margin-bottom:4px;">
                                #DR-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div>
                                <?php foreach ($dtypes as $t): ?>
                                    <span class="damage-tag"><?= htmlspecialchars($t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;color:#000;"><?= htmlspecialchars($r['vehicle_name'] ?? 'N/A') ?></div>
                            <div style="font-size:11px;color:#999;margin-top:2px;">#BK-<?= str_pad($r['booking_id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($r['renter_name'] ?? 'N/A') ?></div>
                            <div style="font-size:11px;color:#999;"><?= htmlspecialchars($r['renter_email'] ?? '') ?></div>
                        </td>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($r['owner_name'] ?? 'N/A') ?></div>
                            <div style="font-size:11px;color:#999;"><?= htmlspecialchars($r['owner_email'] ?? '') ?></div>
                        </td>
                        <td>
                            <div class="thumb-grid">
                                <?php foreach ($imgs as $img): ?>
                                    <img src="<?= htmlspecialchars($img) ?>"
                                         class="thumb-img"
                                         onclick="showImg('<?= htmlspecialchars($img) ?>')"
                                         alt="damage photo">
                                <?php endforeach; ?>
                                <?php if (empty($imgs)): ?>
                                    <span style="color:#ccc;font-size:20px;">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700;color:#000;">₱<?= number_format((float)$r['estimated_cost'], 2) ?></div>
                            <?php if ($r['status'] === 'approved' && $r['approved_amount'] > 0): ?>
                            <div style="font-size:11px;color:#166534;margin-top:2px;font-weight:600;">
                                ₱<?= number_format((float)$r['approved_amount'], 2) ?> approved
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $statusClass ?>">
                                <?php if ($statusClass === 'pending'): ?>
                                    <i class="bi bi-clock"></i>
                                <?php elseif ($statusClass === 'approved'): ?>
                                    <i class="bi bi-check-circle-fill"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill"></i>
                                <?php endif; ?>
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td style="color:#666;font-size:12px;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                        <td style="padding-right:1.75rem;">
                            <?php if ($r['status'] === 'pending'): ?>
                            <button class="action-btn-modern action-btn-dark"
                                    onclick="openReview(<?= htmlspecialchars(json_encode($r)) ?>)">
                                <i class="bi bi-clipboard-check"></i> Review
                            </button>
                            <?php else: ?>
                            <button class="action-btn-modern action-btn-outline"
                                    onclick="openReview(<?= htmlspecialchars(json_encode($r)) ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="modern-pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="modern-page-btn <?= $i === $page ? 'active' : '' ?>"
                   href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($searchQuery) ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ═══════════════════════════════════════════════
     Review / View Modal
════════════════════════════════════════════════ -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="modalTitle">Damage Report</h2>
                    <p class="modal-subtitle" id="modalSubtitle">Review damage claim details</p>
                </div>
                <button type="button" class="modal-close-btn" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body" style="padding:2rem 2.5rem;">

                <!-- Status banner (non-pending) -->
                <div id="statusBanner" class="alert mb-4" style="display:none;"></div>

                <!-- Info grid -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="detail-label">Booking</div>
                        <div class="detail-value" id="mBooking">—</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="detail-label">Vehicle</div>
                        <div class="detail-value" id="mVehicle">—</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="detail-label">Owner</div>
                        <div class="detail-value" id="mOwner">—</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="detail-label">Renter</div>
                        <div class="detail-value" id="mRenter">—</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="detail-label">Estimated Cost</div>
                        <div class="detail-value" id="mCost">—</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="detail-label">Security Deposit</div>
                        <div class="detail-value" id="mDeposit">—</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Damage Types</div>
                        <div id="mTypes" class="mt-1">—</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Description</div>
                        <div class="detail-value mt-1" id="mDesc" style="white-space:pre-wrap;font-weight:400;color:#333;">—</div>
                    </div>
                </div>

                <!-- Photos -->
                <div id="mPhotosSection" style="display:none;" class="mb-4">
                    <div class="detail-label mb-2">Evidence Photos</div>
                    <div id="mPhotos" class="d-flex gap-2 flex-wrap"></div>
                </div>

                <!-- Existing admin notes (read-only when already reviewed) -->
                <div id="existingNotes" style="display:none;" class="mb-3">
                    <div class="detail-label">Admin Notes</div>
                    <div class="detail-value mt-1" id="mAdminNotes" style="font-weight:400;color:#333;"></div>
                </div>

                <!-- Review form (only for pending) -->
                <div id="reviewForm">
                    <hr style="border-color:#f0f0f0;margin:1.5rem 0;">
                    <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#666;margin-bottom:1.25rem;">Admin Decision</div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Approved Deduction Amount (₱)</label>
                        <input type="number" id="approvedAmount" class="form-control" min="0" step="0.01"
                               placeholder="Leave 0 to reject without deduction">
                        <div class="form-text">Max available from security deposit: <strong id="maxDeductible">₱0.00</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Admin Notes <span style="color:#999;font-weight:400;">(optional)</span></label>
                        <textarea id="adminNotes" class="form-control" rows="3"
                                  placeholder="Notes visible to both owner and renter..."></textarea>
                    </div>
                    <input type="hidden" id="activeReportId" value="">
                </div>

            </div>
            <div class="modal-footer" id="modalFooter" style="padding:1.5rem 2.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="btnReject" onclick="submitReview('reject')">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </button>
                <button type="button" class="btn btn-dark" id="btnApprove" onclick="submitReview('approve')">
                    <i class="bi bi-check-circle me-1"></i> Approve &amp; Deduct
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image lightbox -->
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-body p-2">
                <img id="imgModalSrc" src="" alt="damage photo" class="rounded">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showImg(src) {
    document.getElementById('imgModalSrc').src = src;
    new bootstrap.Modal(document.getElementById('imgModal')).show();
}

function openReview(r) {
    const isPending = r.status === 'pending';

    document.getElementById('modalTitle').textContent =
        '#DR-' + String(r.id).padStart(4, '0') + ' — ' + (isPending ? 'Review' : 'Details');
    document.getElementById('modalSubtitle').textContent =
        isPending ? 'Review this damage claim and make a decision' : 'Damage claim details (already reviewed)';

    // Status banner
    const banner = document.getElementById('statusBanner');
    if (!isPending) {
        banner.style.display = '';
        if (r.status === 'approved') {
            banner.className = 'alert alert-success mb-4';
            banner.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i><strong>Approved</strong> — ₱' +
                parseFloat(r.approved_amount || 0).toFixed(2) + ' deducted from security deposit.';
        } else {
            banner.className = 'alert alert-secondary mb-4';
            banner.innerHTML = '<i class="bi bi-x-circle me-2"></i><strong>Rejected</strong> — No deduction was made.';
        }
    } else {
        banner.style.display = 'none';
    }

    // Info fields
    document.getElementById('mBooking').textContent = '#BK-' + String(r.booking_id).padStart(4, '0');
    document.getElementById('mVehicle').textContent = r.vehicle_name || '—';
    document.getElementById('mOwner').textContent   = r.owner_name  + ' <' + r.owner_email  + '>';
    document.getElementById('mRenter').textContent  = r.renter_name + ' <' + r.renter_email + '>';
    document.getElementById('mCost').textContent    = '₱' + parseFloat(r.estimated_cost || 0).toFixed(2);

    const deposit = parseFloat(r.security_deposit_amount || 0);
    document.getElementById('mDeposit').textContent       = '₱' + deposit.toFixed(2);
    document.getElementById('maxDeductible').textContent  = '₱' + deposit.toFixed(2);

    // Damage types
    let dtypes = [];
    try { dtypes = JSON.parse(r.damage_types || '[]'); } catch(e) {}
    document.getElementById('mTypes').innerHTML =
        dtypes.map(t => '<span class="damage-tag">' + escHtml(t) + '</span>').join('') || '—';

    document.getElementById('mDesc').textContent = r.description || '—';

    // Photos
    const photos = [r.image_1, r.image_2, r.image_3, r.image_4].filter(Boolean);
    const photosDiv = document.getElementById('mPhotos');
    photosDiv.innerHTML = '';
    if (photos.length > 0) {
        photos.forEach(src => {
            const img = document.createElement('img');
            img.src = src;
            img.className = 'thumb-img';
            img.style.width = '90px';
            img.style.height = '90px';
            img.onclick = () => showImg(src);
            photosDiv.appendChild(img);
        });
        document.getElementById('mPhotosSection').style.display = '';
    } else {
        document.getElementById('mPhotosSection').style.display = 'none';
    }

    // Admin notes
    const existingNotes = document.getElementById('existingNotes');
    if (!isPending && r.admin_notes) {
        existingNotes.style.display = '';
        document.getElementById('mAdminNotes').textContent = r.admin_notes;
    } else {
        existingNotes.style.display = 'none';
    }

    // Show/hide review form & footer buttons
    document.getElementById('reviewForm').style.display  = isPending ? '' : 'none';
    document.getElementById('btnReject').style.display   = isPending ? '' : 'none';
    document.getElementById('btnApprove').style.display  = isPending ? '' : 'none';
    document.getElementById('approvedAmount').value      = '';
    document.getElementById('adminNotes').value          = '';
    document.getElementById('activeReportId').value      = r.id;

    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function submitReview(action) {
    const reportId    = document.getElementById('activeReportId').value;
    const approvedAmt = parseFloat(document.getElementById('approvedAmount').value || '0');
    const adminNotes  = document.getElementById('adminNotes').value.trim();

    if (action === 'approve' && (isNaN(approvedAmt) || approvedAmt <= 0)) {
        alert('Enter a valid approved amount greater than 0.');
        return;
    }

    const btn = action === 'approve'
        ? document.getElementById('btnApprove')
        : document.getElementById('btnReject');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    const body = new URLSearchParams({
        report_id:       reportId,
        action:          action,
        approved_amount: approvedAmt.toString(),
        admin_notes:     adminNotes,
    });

    fetch('api/damage_reports/review_damage_report.php', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                location.reload();
            } else {
                alert(data.message || 'Failed to process report.');
                btn.disabled = false;
                btn.innerHTML = action === 'approve'
                    ? '<i class="bi bi-check-circle me-1"></i> Approve & Deduct'
                    : '<i class="bi bi-x-circle me-1"></i> Reject';
            }
        })
        .catch(() => {
            alert('Network error. Please try again.');
            btn.disabled = false;
        });
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
