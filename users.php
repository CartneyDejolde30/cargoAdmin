<<<<<<< HEAD
=======
<?php
/**
 * ============================================================================
 * UNIFIED USER MANAGEMENT & VERIFICATION SYSTEM
 * CarGo - Agusan del Sur Car Rental Platform
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

include 'include/db.php';

// ============================================================================
// SECTION 1: HANDLE VERIFICATION ACTIONS (Approve/Reject)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['approve']) || isset($_POST['reject']))) {
    $verification_id = $_POST['id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    if (!$verification_id || !$user_id) {
        header("Location: users.php?error=invalid_request");
        exit;
    }

    $verification_id = intval($verification_id);
    $user_id = intval($user_id);

    // Get user info for notification
    $userStmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();

    if (!$user) {
        header("Location: users.php?error=user_not_found");
        exit;
    }

    // Handle Approval
    if (isset($_POST['approve'])) {
        $updateStmt = $conn->prepare("UPDATE user_verifications 
                                       SET status = 'approved', 
                                           verified_at = NOW(),
                                           updated_at = NOW() 
                                       WHERE id = ?");
        $updateStmt->bind_param("i", $verification_id);
        
        if ($updateStmt->execute()) {
            $notificationTitle = "Verification Approved ✓";
            $notificationMessage = "Congratulations! Your identity verification has been approved. You now have full access to all features.";
            
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, read_status, created_at) 
                                         VALUES (?, ?, ?, 'unread', NOW())");
            $notifStmt->bind_param("iss", $user_id, $notificationTitle, $notificationMessage);
            $notifStmt->execute();
            $notifStmt->close();
            
            header("Location: users.php?success=approved&user=" . urlencode($user['fullname']));
        } else {
            header("Location: users.php?error=approval_failed&details=" . urlencode($updateStmt->error));
        }
        
        $updateStmt->close();
        exit;
    }

    // Handle Rejection
    if (isset($_POST['reject'])) {
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            header("Location: users.php?error=rejection_reason_required");
            exit;
        }
        
        $updateStmt = $conn->prepare("UPDATE user_verifications 
                                       SET status = 'rejected', 
                                           review_notes = ?,
                                           updated_at = NOW() 
                                       WHERE id = ?");
        $updateStmt->bind_param("si", $reason, $verification_id);
        
        if ($updateStmt->execute()) {
            $notificationTitle = "Verification Rejected ✗";
            $notificationMessage = "Your identity verification was rejected.\n\nReason: " . $reason . "\n\nPlease review the requirements and resubmit your verification with the correct documents.";
            
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, read_status, created_at) 
                                         VALUES (?, ?, ?, 'unread', NOW())");
            $notifStmt->bind_param("iss", $user_id, $notificationTitle, $notificationMessage);
            $notifStmt->execute();
            $notifStmt->close();
            
            header("Location: users.php?success=rejected&user=" . urlencode($user['fullname']));
        } else {
            header("Location: users.php?error=rejection_failed&details=" . urlencode($updateStmt->error));
        }
        
        $updateStmt->close();
        exit;
    }
}

// ============================================================================
// SECTION 2: DETERMINE VIEW MODE
// ============================================================================
$viewMode = $_GET['view'] ?? 'management';

// ============================================================================
// SECTION 3: BUILD FILTER CONDITIONS (APPLIES TO BOTH VIEWS)
// ============================================================================
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? 'all';
$verificationFilter = $_GET['verification'] ?? 'all';

$whereConditions = ["1=1"];
$params = [];
$types = "";

// Search filter
if (!empty($search)) {
    $whereConditions[] = "(users.fullname LIKE ? OR users.email LIKE ? OR users.phone LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

// Role filter
if ($role !== "all") {
    $whereConditions[] = "users.role = ?";
    $params[] = $role;
    $types .= "s";
}

// Verification filter
if ($verificationFilter === 'verified') {
    $whereConditions[] = "user_verifications.status = 'approved'";
} elseif ($verificationFilter === 'pending') {
    $whereConditions[] = "user_verifications.status = 'pending'";
} elseif ($verificationFilter === 'not-verified') {
    $whereConditions[] = "user_verifications.id IS NULL";
} elseif ($verificationFilter === 'rejected') {
    $whereConditions[] = "user_verifications.status = 'rejected'";
}

$whereClause = implode(" AND ", $whereConditions);

// ============================================================================
// SECTION 4: FETCH STATISTICS (with filters applied)
// ============================================================================
$statsQuery = "
    SELECT 
        COUNT(DISTINCT users.id) as total,
        SUM(CASE WHEN user_verifications.status = 'approved' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN user_verifications.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN user_verifications.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN user_verifications.id IS NULL THEN 1 ELSE 0 END) as unverified
    FROM users
    LEFT JOIN user_verifications ON users.id = user_verifications.user_id
    WHERE {$whereClause}
";

if (!empty($params)) {
    $statsStmt = $conn->prepare($statsQuery);
    if (!empty($types)) {
        $statsStmt->bind_param($types, ...$params);
    }
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();
} else {
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
}

// ============================================================================
// SECTION 5: FETCH DATA BASED ON VIEW MODE
// ============================================================================
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total rows
$countSql = "SELECT COUNT(DISTINCT users.id) as total FROM users LEFT JOIN user_verifications ON users.id = user_verifications.user_id WHERE {$whereClause}";

if (!empty($params)) {
    $countStmt = $conn->prepare($countSql);
    if (!empty($types)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRows = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query($countSql);
    $totalRows = $countResult->fetch_assoc()['total'];
}

$totalPages = ceil($totalRows / $limit);

// Fetch data
if ($viewMode === 'overview') {
    $sql = "SELECT users.*, user_verifications.id as verification_id, user_verifications.status as verification_status,
            user_verifications.created_at as verification_date, user_verifications.verified_at, user_verifications.review_notes
            FROM users LEFT JOIN user_verifications ON users.id = user_verifications.user_id
            WHERE {$whereClause} ORDER BY users.created_at DESC";
} else {
    $sql = "SELECT users.*, user_verifications.id as verification_id, user_verifications.status as verification_status,
            user_verifications.id_type, user_verifications.first_name as ver_first_name, user_verifications.last_name as ver_last_name,
            user_verifications.mobile_number, user_verifications.gender, user_verifications.date_of_birth,
            user_verifications.id_front_photo, user_verifications.id_back_photo, user_verifications.selfie_photo,
            user_verifications.review_notes, user_verifications.created_at as verification_date
            FROM users LEFT JOIN user_verifications ON users.id = user_verifications.user_id
            WHERE {$whereClause} ORDER BY users.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
}

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$query = $stmt->get_result();

// Helper function to build URL with filters
function buildFilterUrl($baseParams = []) {
    global $search, $role, $verificationFilter;
    
    $params = $baseParams;
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    if ($role !== 'all') {
        $params['role'] = $role;
    }
    if ($verificationFilter !== 'all') {
        $params['verification'] = $verificationFilter;
    }
    
    return '?' . http_build_query($params);
}
?>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
  <title>User Management - CarGo Admin</title>
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
      position: relative;
    }

    .notification-btn:hover {
      background: #f5f5f5;
    }

    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 10px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
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

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 18px;
      padding: 25px;
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
      margin-bottom: 15px;
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
      color: #28a745;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .stat-trend.down {
      color: #dc3545;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 800;
      color: #1a1a1a;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
      font-weight: 500;
    }

    /* Filter Section */
    .filter-section {
      background: white;
      border-radius: 18px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .filter-row {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: center;
    }

    .search-box {
      flex: 1;
      min-width: 300px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 12px 45px 12px 20px;
      border: 2px solid #f0f0f0;
      border-radius: 12px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .search-box i {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
      font-size: 16px;
    }

    .filter-dropdown {
      padding: 12px 20px;
      border: 2px solid #f0f0f0;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
    }

    .filter-dropdown:focus {
      outline: none;
      border-color: #1a1a1a;
    }

    .add-user-btn {
      padding: 12px 24px;
      background: #1a1a1a;
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-user-btn:hover {
      background: #000;
      transform: translateY(-2px);
    }

    /* Table Section */
    .table-section {
      background: white;
      border-radius: 18px;
      padding: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 30px;
    }

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

    .table-controls {
      display: flex;
      gap: 12px;
    }

    .table-btn {
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

    .table-btn:hover {
      background: #e8e8e8;
      color: #1a1a1a;
    }

    .table-btn.active {
      background: #1a1a1a;
      color: white;
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
      font-size: 12px;
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

    tbody tr:last-child td {
      border-bottom: none;
    }

    .user-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar-small {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      overflow: hidden;
      border: 2px solid #f0f0f0;
    }

    .user-avatar-small img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .user-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .user-name {
      font-weight: 600;
      color: #1a1a1a;
    }

    .user-email {
      font-size: 12px;
      color: #999;
    }

    .role-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .role-badge.owner {
      background: #e3f2fd;
      color: #1976d2;
    }

    .role-badge.renter {
      background: #e8f5e9;
      color: #388e3c;
    }

    .role-badge.both {
      background: #f3e5f5;
      color: #7b1fa2;
    }

    .status-badge {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .status-badge.verified {
      background: #d1e7dd;
      color: #0f5132;
    }

    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge.suspended {
      background: #f8d7da;
      color: #842029;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .action-btn {
      width: 35px;
      height: 35px;
      border-radius: 8px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 16px;
    }

    .action-btn.view {
      background: #e3f2fd;
      color: #1976d2;
    }

    .action-btn.view:hover {
      background: #1976d2;
      color: white;
    }

    .action-btn.verify {
      background: #e8f5e9;
      color: #388e3c;
    }

    .action-btn.verify:hover {
      background: #388e3c;
      color: white;
    }

    .action-btn.suspend {
      background: #fff3cd;
      color: #f57c00;
    }

    .action-btn.suspend:hover {
      background: #f57c00;
      color: white;
    }

    .action-btn.delete {
      background: #ffebee;
      color: #d32f2f;
    }

    .action-btn.delete:hover {
      background: #d32f2f;
      color: white;
    }

    /* Modal Styles */
    .modal-content {
      border-radius: 18px;
      border: none;
      overflow: hidden;
    }

    .modal-header {
      background: #1a1a1a;
      color: white;
      padding: 25px 30px;
      border: none;
    }

    .modal-title {
      font-weight: 700;
      font-size: 20px;
    }

    .btn-close {
      filter: brightness(0) invert(1);
    }

    .modal-body {
      padding: 30px;
    }

    .detail-section {
      margin-bottom: 30px;
    }

    .detail-section:last-child {
      margin-bottom: 0;
    }

    .detail-section-title {
      font-size: 14px;
      font-weight: 700;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 15px;
    }

    .detail-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 15px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .detail-label {
      font-size: 12px;
      color: #999;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 15px;
      color: #1a1a1a;
      font-weight: 600;
    }

    .documents-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .document-card {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .document-card:hover {
      background: #e9ecef;
    }

    .document-icon {
      width: 45px;
      height: 45px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #1a1a1a;
    }

    .document-info {
      flex: 1;
    }

    .document-title {
      font-size: 13px;
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 3px;
    }

    .document-status {
      font-size: 11px;
      color: #666;
    }

    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .activity-item {
      display: flex;
      gap: 12px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .activity-content {
      flex: 1;
    }

    .activity-title {
      font-size: 14px;
      font-weight: 600;
      color: #1a1a1a;
      margin-bottom: 3px;
    }

    .activity-time {
      font-size: 12px;
      color: #999;
    }

    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #f0f0f0;
      gap: 10px;
    }

    .modal-btn {
      padding: 12px 28px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .modal-btn.secondary {
      background: #f5f5f5;
      color: #666;
    }

    .modal-btn.secondary:hover {
      background: #e8e8e8;
    }

    .modal-btn.verify {
      background: #28a745;
      color: white;
    }

    .modal-btn.verify:hover {
      background: #218838;
    }

    .modal-btn.suspend {
      background: #f57c00;
      color: white;
    }

    .modal-btn.suspend:hover {
      background: #ef6c00;
    }

    .modal-btn.delete {
      background: #dc3545;
      color: white;
    }

    .modal-btn.delete:hover {
      background: #c82333;
    }

    /* Pagination */
    .pagination-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #f0f0f0;
    }

    .pagination-info {
      font-size: 14px;
      color: #666;
    }

    .pagination-controls {
      display: flex;
      gap: 8px;
    }

    .page-btn {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      background: white;
      color: #666;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .page-btn:hover {
      background: #f5f5f5;
      border-color: #1a1a1a;
    }

    .page-btn.active {
      background: #1a1a1a;
      color: white;
      border-color: #1a1a1a;
    }

    @media (max-width: 1400px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .main-content {
        margin-left: 0;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .filter-row {
        flex-direction: column;
      }

      .search-box {
        width: 100%;
      }
=======
  <title><?= $viewMode === 'overview' ? 'User Status Overview' : 'User Management' ?> - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  
  <style>
    /* Additional inline enhancements for smooth animations */
    .fade-in {
      animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .stat-card {
      animation: slideUp 0.6s ease-out backwards;
    }
    
    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .active-filter {
      background-color: #667eea !important;
      color: white !important;
      border-color: #667eea !important;
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
<<<<<<< HEAD
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo-section">
      <div class="logo-icon">C</div>
      <div class="logo-text">CARGO</div>
    </div>

    <div class="menu-section">
      <div class="menu-label">About Car</div>
      <a href="dashboard.php" class="menu-item">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
      <a href="get_cars_admin.php" class="menu-item">
        <i class="bi bi-car-front"></i>
        <span>Car Listing</span>
      </a>
      <a href="users.php" class="menu-item active">
        <i class="bi bi-person"></i>
        <span>Users Verification</span>
      </a>
      <a href="bookings.php" class="menu-item">
        <i class="bi bi-book"></i>
        <span>Bookings</span>
      </a>
    </div>

    <div class="menu-section">
      <div class="menu-label">Report</div>
      <a href="sales-statistics.php" class="menu-item">
        <i class="bi bi-bar-chart"></i>
        <span>Sales Statistics</span>
      </a>
      <a href="car-reports.php" class="menu-item">
        <i class="bi bi-file-text"></i>
        <span>Car Reports</span>
      </a>
    </div>

    <div class="menu-section">
      <a href="settings.php" class="menu-item">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </a>
      <a href="logout.php" class="menu-item" style="color: #dc3545; margin-top: 20px;">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">Users Management</h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">3</span>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
=======
  <?php include('include/sidebar.php'); ?>

  <main class="main-content">
    
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php if ($_GET['success'] === 'approved'): ?>
          <strong>Verification Approved!</strong> User <strong><?= htmlspecialchars($_GET['user'] ?? '') ?></strong> has been successfully verified and notified.
        <?php elseif ($_GET['success'] === 'rejected'): ?>
          <strong>Verification Rejected!</strong> User <strong><?= htmlspecialchars($_GET['user'] ?? '') ?></strong> has been notified of the rejection.
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error!</strong>
        <?php
        $errorMessages = [
            'invalid_request' => 'Invalid request. Please try again.',
            'approval_failed' => 'Failed to approve verification. Please try again.',
            'rejection_failed' => 'Failed to reject verification. Please try again.',
            'rejection_reason_required' => 'Rejection reason is required.',
            'user_not_found' => 'User not found.'
        ];
        echo $errorMessages[$_GET['error']] ?? 'An error occurred. Please try again.';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Top Bar -->
    <div class="top-bar fade-in">
      <h1 class="page-title">
        <i class="bi bi-people-fill"></i>
        <?= $viewMode === 'overview' ? 'User Status Overview' : 'Users Management' ?>
      </h1>
      <div class="user-profile">
        <?php if ($viewMode === 'management'): ?>
          <a href="<?= buildFilterUrl(['view' => 'overview']) ?>" class="btn btn-sm btn-outline-primary me-2" title="Switch to Overview">
            <i class="bi bi-clipboard-data me-1"></i> Overview
          </a>
        <?php else: ?>
          <a href="<?= buildFilterUrl(['view' => 'management']) ?>" class="btn btn-sm btn-outline-primary me-2" title="Switch to Management">
            <i class="bi bi-gear-fill me-1"></i> Management
          </a>
        <?php endif; ?>
        <button class="notification-btn" title="Notifications">
          <i class="bi bi-bell"></i>
          <?php if ($stats['pending'] > 0): ?>
            <span class="notification-badge"><?= $stats['pending'] ?></span>
          <?php endif; ?>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=667eea&color=fff" alt="Admin">
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
        </div>
      </div>
    </div>

<<<<<<< HEAD
=======
    <!-- Search Section (Now appears in BOTH views) -->
    <div class="search-section fade-in" style="animation-delay: 0.15s;">
      <form method="GET" class="search-form">
        <input type="hidden" name="view" value="<?= $viewMode ?>">
        <input 
          type="text" 
          name="search" 
          class="search-input" 
          placeholder="🔍 Search by name, email, or phone number..." 
          value="<?= htmlspecialchars($search) ?>">
        
        <select name="role" class="filter-select <?= $role !== 'all' ? 'active-filter' : '' ?>">
          <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>👥 All Roles</option>
          <option value="Owner" <?= $role === 'Owner' ? 'selected' : '' ?>>🚗 Car Owners</option>
          <option value="Renter" <?= $role === 'Renter' ? 'selected' : '' ?>>👤 Renters</option>
        </select>

        <select name="verification" class="filter-select <?= $verificationFilter !== 'all' ? 'active-filter' : '' ?>">
          <option value="all" <?= $verificationFilter === 'all' ? 'selected' : '' ?>>🔐 All Verification</option>
          <option value="verified" <?= $verificationFilter === 'verified' ? 'selected' : '' ?>>✅ Verified</option>
          <option value="pending" <?= $verificationFilter === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
          <option value="rejected" <?= $verificationFilter === 'rejected' ? 'selected' : '' ?>>❌ Rejected</option>
          <option value="not-verified" <?= $verificationFilter === 'not-verified' ? 'selected' : '' ?>>⚠️ Not Verified</option>
        </select>

        <button type="submit" class="search-btn">
          <i class="bi bi-search"></i> Search
        </button>
        
        <a href="users.php?view=<?= $viewMode ?>" class="reset-btn">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </a>
      </form>
      
      <?php if (!empty($search) || $role !== 'all' || $verificationFilter !== 'all'): ?>
        <div class="alert alert-info mt-3 mb-0">
          <i class="bi bi-funnel-fill me-2"></i>
          <strong>Active Filters:</strong>
          <?php if (!empty($search)): ?>
            <span class="badge bg-primary me-1">Search: "<?= htmlspecialchars($search) ?>"</span>
          <?php endif; ?>
          <?php if ($role !== 'all'): ?>
            <span class="badge bg-primary me-1">Role: <?= htmlspecialchars($role) ?></span>
          <?php endif; ?>
          <?php if ($verificationFilter !== 'all'): ?>
            <span class="badge bg-primary me-1">Verification: <?= ucfirst(str_replace('-', ' ', $verificationFilter)) ?></span>
          <?php endif; ?>
          <span class="ms-2">| Showing <?= $totalRows ?> result<?= $totalRows != 1 ? 's' : '' ?></span>
        </div>
      <?php endif; ?>
    </div>

>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-people"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15%
          </div>
        </div>
        <div class="stat-value">142</div>
        <div class="stat-label">Total Users</div>
=======
          <div class="stat-icon stat-total">
            <i class="bi bi-people"></i>
          </div>
        </div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-detail">
          <i class="bi bi-graph-up"></i> <?= empty($search) && $role === 'all' && $verificationFilter === 'all' ? 'All registered users' : 'Filtered results' ?>
        </div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">98</div>
        <div class="stat-label">Verified Users</div>
=======
          <div class="stat-icon stat-approved">
            <i class="bi bi-shield-check"></i>
          </div>
        </div>
        <div class="stat-value"><?= $stats['verified'] ?></div>
        <div class="stat-label">Verified Users</div>
        <div class="stat-detail">
          <i class="bi bi-check-circle"></i> Identity confirmed
        </div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12
          </div>
        </div>
        <div class="stat-value">31</div>
        <div class="stat-label">Pending Verification</div>
=======
          <div class="stat-icon stat-pending">
            <i class="bi bi-clock-history"></i>
          </div>
        </div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Review</div>
        <div class="stat-detail">
          <i class="bi bi-hourglass-split"></i> Awaiting verification
        </div>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
      </div>

      <div class="stat-card">
        <div class="stat-header">
<<<<<<< HEAD
          <div class="stat-icon">
            <i class="bi bi-x-circle"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i>
            -3
          </div>
        </div>
        <div class="stat-value">13</div>
        <div class="stat-label">Suspended Accounts</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="search-box">
          <input type="text" placeholder="Search by name, email, or phone number...">
          <i class="bi bi-search"></i>
        </div>
        <select class="filter-dropdown">
          <option>All Roles</option>
          <option>Car Owners</option>
          <option>Renters</option>
          <option>Both</option>
        </select>
        <select class="filter-dropdown">
          <option>All Status</option>
          <option>Verified</option>
          <option>Pending</option>
          <option>Suspended</option>
        </select>
        <select class="filter-dropdown">
          <option>Registration Date</option>
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Last 90 Days</option>
        </select>
        <button class="add-user-btn">
          <i class="bi bi-person-plus"></i>
          Add User
        </button>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">All Users</h2>
        <div class="table-controls">
          <button class="table-btn active">All (142)</button>
          <button class="table-btn">Pending (31)</button>
          <button class="table-btn">Verified (98)</button>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Phone Number</th>
              <th>Role</th>
              <th>Joined Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>#USR-1024</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="Juan">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan.delacruz@email.com</span>
                  </div>
                </div>
              </td>
              <td>+63 912 345 6789</td>
              <td><span class="role-badge owner">Car Owner</span></td>
              <td>Jan 15, 2024</td>
              <td><span class="status-badge verified">Verified</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#userModal1" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Suspend Account">
                    <i class="bi bi-ban"></i>
                  </button>
                  <button class="action-btn delete" title="Delete User">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr>
              <td><strong>#USR-1023
=======
          <div class="stat-icon stat-rejected">
            <i class="bi bi-<?= $viewMode === 'overview' ? 'x-circle' : 'person-x' ?>"></i>
          </div>
        </div>
        <div class="stat-value"><?= $viewMode === 'overview' ? $stats['rejected'] : $stats['unverified'] ?></div>
        <div class="stat-label"><?= $viewMode === 'overview' ? 'Rejected' : 'Not Verified' ?></div>
        <div class="stat-detail">
          <i class="bi bi-info-circle"></i> <?= $viewMode === 'overview' ? 'Declined submissions' : 'No submission yet' ?>
        </div>
      </div>
    </div>

    <?php if ($viewMode === 'overview'): ?>
      
      <!-- Quick Action Buttons -->
      <div class="card mb-4 fade-in" style="animation-delay: 0.2s;">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0">
            <i class="bi bi-lightning-charge-fill me-2"></i>Quick Actions
          </h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <a href="<?= buildFilterUrl(['view' => 'management', 'verification' => 'pending']) ?>" class="btn btn-warning w-100">
                <i class="bi bi-clock-history me-2"></i>Review Pending
                <span class="badge bg-light text-warning ms-2"><?= $stats['pending'] ?></span>
              </a>
            </div>
            <div class="col-md-3">
              <a href="<?= buildFilterUrl(['view' => 'management', 'verification' => 'verified']) ?>" class="btn btn-success w-100">
                <i class="bi bi-shield-check me-2"></i>View Verified
                <span class="badge bg-light text-success ms-2"><?= $stats['verified'] ?></span>
              </a>
            </div>
            <div class="col-md-3">
              <a href="<?= buildFilterUrl(['view' => 'management', 'verification' => 'not-verified']) ?>" class="btn btn-secondary w-100">
                <i class="bi bi-person-x me-2"></i>Not Submitted
                <span class="badge bg-light text-secondary ms-2"><?= $stats['unverified'] ?></span>
              </a>
            </div>
            <div class="col-md-3">
              <a href="<?= buildFilterUrl(['view' => 'management']) ?>" class="btn btn-primary w-100">
                <i class="bi bi-gear-fill me-2"></i>Manage All Users
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- User Overview Cards -->
      <div class="card fade-in" style="animation-delay: 0.3s;">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>All Users Overview
            <span class="badge bg-light text-primary ms-2"><?= $stats['total'] ?></span>
          </h5>
        </div>
        <div class="card-body p-3">
          <?php 
          if ($query->num_rows === 0) {
            echo '<div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>No users found</h4>
                    <p>No users match your current filters. Try adjusting your search criteria.</p>
                  </div>';
          } else {
            $counter = 1;
            while ($user = $query->fetch_assoc()) {
              $statusBadge = '';
              $statusClass = '';
              $statusIcon = '';
              
              if (!$user['verification_id']) {
                $statusBadge = '<span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Not Submitted</span>';
                $statusClass = 'text-secondary';
                $statusIcon = '<i class="bi bi-person-x"></i>';
              } elseif ($user['verification_status'] === 'approved') {
                $statusBadge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>';
                $statusClass = 'text-success';
                $statusIcon = '<i class="bi bi-shield-check"></i>';
                $verifiedDate = date('M d, Y', strtotime($user['verified_at']));
                $statusBadge .= '<br><small class="text-muted mt-1 d-block"><i class="bi bi-calendar-check me-1"></i>Verified on ' . $verifiedDate . '</small>';
              } elseif ($user['verification_status'] === 'pending') {
                $statusBadge = '<span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending Review</span>';
                $statusClass = 'text-warning';
                $statusIcon = '<i class="bi bi-clock-history"></i>';
                $submitDate = date('M d, Y', strtotime($user['verification_date']));
                $statusBadge .= '<br><small class="text-muted mt-1 d-block"><i class="bi bi-upload me-1"></i>Submitted ' . $submitDate . '</small>';
              } elseif ($user['verification_status'] === 'rejected') {
                $statusBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
                $statusClass = 'text-danger';
                $statusIcon = '<i class="bi bi-x-circle"></i>';
                if (!empty($user['review_notes'])) {
                  $statusBadge .= '<br><small class="text-danger mt-1 d-block"><i class="bi bi-info-circle me-1"></i>' . htmlspecialchars(substr($user['review_notes'], 0, 50)) . '...</small>';
                }
              }
              
              $avatar = !empty($user['profile_image']) 
                ? htmlspecialchars($user['profile_image']) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname']) . '&background=random';
              ?>
              <div class="card mb-2 shadow-sm">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center <?= $statusClass ?>" 
                           style="width: 55px; height: 55px; font-size: 1.6rem;">
                        <?= $statusIcon ?>
                      </div>
                    </div>
                    <div class="col-md-1">
                      <strong class="text-primary">#<?= $counter++ ?></strong>
                      <div class="text-muted small">ID: <?= $user['id'] ?></div>
                    </div>
                    <div class="col-md-3">
                      <div class="d-flex align-items-center">
                        <img src="<?= $avatar ?>" class="rounded-circle me-2" 
                             style="width: 45px; height: 45px; object-fit: cover;">
                        <div>
                          <div class="fw-bold"><?= htmlspecialchars($user['fullname']) ?></div>
                          <div class="text-muted small">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <span class="badge bg-<?= $user['role'] === 'Owner' ? 'primary' : 'info' ?>">
                        <i class="bi bi-<?= $user['role'] === 'Owner' ? 'car-front' : 'person' ?> me-1"></i>
                        <?= htmlspecialchars($user['role']) ?>
                      </span>
                      <?php if ($user['municipality']): ?>
                        <div class="text-muted small mt-1">
                          <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($user['municipality']) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                      <div class="<?= $statusClass ?> fw-bold">
                        <?= $statusBadge ?>
                      </div>
                    </div>
                    <div class="col-md-2 text-end">
                      <?php if ($user['verification_status'] === 'pending'): ?>
                        <a href="<?= buildFilterUrl(['view' => 'management', 'highlight' => $user['verification_id']]) ?>" 
                           class="btn btn-sm btn-warning">
                          <i class="bi bi-eye me-1"></i>Review Now
                        </a>
                      <?php endif; ?>
                      <small class="text-muted d-block mt-2">
                        <i class="bi bi-calendar-plus me-1"></i><?= date('M d, Y', strtotime($user['created_at'])) ?>
                      </small>
                    </div>
                  </div>
                </div>
              </div>
              <?php
            }
          }
          ?>
        </div>
      </div>

      <!-- System Info -->
      <div class="card mt-4 fade-in" style="animation-delay: 0.4s;">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0">
            <i class="bi bi-info-circle-fill me-2"></i>System Information
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="fw-bold text-primary"><i class="bi bi-database me-2"></i>Database Status</h6>
              <ul class="list-unstyled">
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Users table: <span class="text-success fw-bold">Connected</span></li>
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Verifications table: <span class="text-success fw-bold">Connected</span></li>
                <li><i class="bi bi-file-earmark-text me-2"></i>Total records: <strong><?= $stats['total'] ?></strong> users</li>
              </ul>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Verification Statistics</h6>
              <ul class="list-unstyled">
                <li><i class="bi bi-upload me-2"></i>Submission Rate: 
                  <strong class="text-info">
                    <?= $stats['total'] > 0 ? 
                        round((($stats['verified'] + $stats['pending'] + $stats['rejected']) / $stats['total']) * 100, 1) : 0 ?>%
                  </strong>
                </li>
                <li><i class="bi bi-check-circle me-2"></i>Approval Rate: 
                  <strong class="text-success">
                    <?= ($stats['verified'] + $stats['pending'] + $stats['rejected']) > 0 ? 
                        round(($stats['verified'] / ($stats['verified'] + $stats['pending'] + $stats['rejected'])) * 100, 1) : 0 ?>%
                  </strong>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>

      <!-- Table Section -->
      <div class="table-section fade-in" style="animation-delay: 0.3s;">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th><i class="bi bi-hash me-1"></i>#</th>
                <th><i class="bi bi-person me-1"></i>User</th>
                <th><i class="bi bi-telephone me-1"></i>Phone</th>
                <th><i class="bi bi-tag me-1"></i>Role</th>
                <th><i class="bi bi-geo-alt me-1"></i>Municipality</th>
                <th><i class="bi bi-calendar me-1"></i>Joined</th>
                <th><i class="bi bi-shield-check me-1"></i>Verification</th>
                <th><i class="bi bi-gear me-1"></i>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              if ($query->num_rows === 0) {
                echo '<tr><td colspan="8" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>No users found</h4>
                        <p>Try adjusting your search or filter criteria</p>
                      </td></tr>';
              }

              $num = $offset + 1;
              while($row = $query->fetch_assoc()) { 
                $avatar = !empty($row['profile_image']) 
                  ? htmlspecialchars($row['profile_image']) 
                  : 'https://ui-avatars.com/api/?name=' . urlencode($row['fullname']) . '&background=667eea&color=fff';
                
                $verificationStatus = $row['verification_status'] ?? 'not-verified';
                $verificationLabel = $verificationStatus === 'approved' ? 'Verified' : 
                                    ($verificationStatus === 'pending' ? 'Pending' : 
                                    ($verificationStatus === 'rejected' ? 'Rejected' : 'Not Verified'));
              ?>
              <tr <?= isset($_GET['highlight']) && $_GET['highlight'] == $row['verification_id'] ? 'style="background-color: #fff3cd;"' : '' ?>>
                <td><strong class="text-primary">#<?= $num++ ?></strong></td>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-small">
                      <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($row['fullname']) ?>">
                    </div>
                    <div class="user-info">
                      <span class="user-name"><?= htmlspecialchars($row['fullname']) ?></span>
                      <span class="user-email"><?= htmlspecialchars($row['email']) ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <i class="bi bi-phone me-1"></i>
                  <?= htmlspecialchars($row['phone'] ?? 'N/A') ?>
                </td>
                <td>
                  <span class="role-badge <?= strtolower($row['role']) ?>">
                    <i class="bi bi-<?= $row['role'] === 'Owner' ? 'car-front' : 'person' ?> me-1"></i>
                    <?= htmlspecialchars($row['role']) ?>
                  </span>
                </td>
                <td>
                  <i class="bi bi-geo-alt-fill me-1 text-danger"></i>
                  <?= htmlspecialchars($row['municipality'] ?: 'N/A') ?>
                </td>
                <td>
                  <i class="bi bi-calendar-check me-1"></i>
                  <?= date('M d, Y', strtotime($row['created_at'])) ?>
                </td>
                <td>
                  <span class="verification-badge <?= $verificationStatus ?>">
                    <?= $verificationLabel ?>
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <?php if (!empty($row['verification_id'])) { ?>
                      <button class="action-btn view" title="View Verification Details" 
                              data-bs-toggle="modal" data-bs-target="#verificationModal<?= $row['verification_id'] ?>">
                        <i class="bi bi-eye-fill"></i>
                      </button>
                    <?php } else { ?>
                      <button class="action-btn view" title="No Verification Submitted" disabled>
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    <?php } ?>
                  </div>
                </td>
              </tr>

              <!-- Verification Details Modal -->
              <?php if (!empty($row['verification_id'])) { 
                $statusClass = match($verificationStatus) {
                  "approved" => "success",
                  "rejected" => "danger",
                  default => "warning"
                };
              ?>
              <div class="modal fade" id="verificationModal<?= $row['verification_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header bg-secondary text-white">
                      <h5 class="modal-title">
                        <i class="bi bi-shield-check-fill me-2"></i>Verification Details - <?= htmlspecialchars($row['fullname']) ?>
                      </h5>
                      <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <!-- User Information Grid -->
                      <div class="row mb-3">
                        <div class="col-md-4">
                          <strong><i class="bi bi-person-fill me-2 text-primary"></i>Full Name:</strong><br>
                          <span class="text-muted"><?= htmlspecialchars($row['ver_first_name'] . " " . $row['ver_last_name']) ?></span>
                        </div>
                        <div class="col-md-4">
                          <strong><i class="bi bi-envelope-fill me-2 text-primary"></i>Email:</strong><br>
                          <span class="text-muted"><?= htmlspecialchars($row['email']) ?></span>
                        </div>
                        <div class="col-md-4">
                          <strong><i class="bi bi-phone-fill me-2 text-primary"></i>Mobile:</strong><br>
                          <span class="text-muted"><?= htmlspecialchars($row['mobile_number']) ?></span>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <div class="col-md-4">
                          <strong><i class="bi bi-gender-ambiguous me-2 text-primary"></i>Gender:</strong><br>
                          <span class="text-muted"><?= htmlspecialchars($row['gender'] ?? 'N/A') ?></span>
                        </div>
                        <div class="col-md-4">
                          <strong><i class="bi bi-calendar-event me-2 text-primary"></i>Date of Birth:</strong><br>
                          <span class="text-muted"><?= $row['date_of_birth'] ? date('M d, Y', strtotime($row['date_of_birth'])) : 'N/A' ?></span>
                        </div>
                        <div class="col-md-4">
                          <strong><i class="bi bi-card-text me-2 text-primary"></i>ID Type:</strong><br>
                          <span class="badge bg-info"><?= htmlspecialchars($row['id_type']) ?></span>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <div class="col-md-6">
                          <strong><i class="bi bi-info-circle me-2 text-primary"></i>Status:</strong><br>
                          <span class="badge bg-<?= $statusClass ?> fs-6 mt-1">
                            <i class="bi bi-<?= $statusClass === 'success' ? 'check-circle' : ($statusClass === 'danger' ? 'x-circle' : 'clock') ?> me-1"></i>
                            <?= ucfirst($verificationStatus) ?>
                          </span>
                        </div>
                        <div class="col-md-6">
                          <strong><i class="bi bi-clock-history me-2 text-primary"></i>Submitted:</strong><br>
                          <span class="text-muted"><?= date('M d, Y h:i A', strtotime($row['verification_date'])) ?></span>
                        </div>
                      </div>

                      <?php if($verificationStatus == "rejected" && !empty($row["review_notes"])) { ?>
                        <div class="alert alert-danger">
                          <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Rejection Reason:</strong><br>
                          <span class="ms-4"><?= nl2br(htmlspecialchars($row["review_notes"])) ?></span>
                        </div>
                      <?php } ?>

                      <hr>

                      <h6 class="fw-bold mb-3">
                        <i class="bi bi-file-earmark-image-fill me-2"></i>Submitted Documents
                      </h6>
                      <div class="row text-center g-3">
                        <div class="col-md-4">
                          <p class="fw-semibold text-primary">
                            <i class="bi bi-front me-2"></i>ID (Front)
                          </p>
                          <?php $idFrontPath = str_replace('../', '', $row['id_front_photo']); ?>
                          <img src="<?= htmlspecialchars($idFrontPath) ?>" 
                               class="img-fluid rounded border" 
                               style="cursor: pointer; max-height: 200px; object-fit: cover; width: 100%;"
                               onclick="viewDocument('<?= htmlspecialchars($idFrontPath) ?>', 'ID Front')"
                               onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
                        </div>

                        <div class="col-md-4">
                          <p class="fw-semibold text-primary">
                            <i class="bi bi-back me-2"></i>ID (Back)
                          </p>
                          <?php $idBackPath = str_replace('../', '', $row['id_back_photo']); ?>
                          <img src="<?= htmlspecialchars($idBackPath) ?>" 
                               class="img-fluid rounded border"
                               style="cursor: pointer; max-height: 200px; object-fit: cover; width: 100%;"
                               onclick="viewDocument('<?= htmlspecialchars($idBackPath) ?>', 'ID Back')"
                               onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
                        </div>

                        <div class="col-md-4">
                          <p class="fw-semibold text-primary">
                            <i class="bi bi-camera-fill me-2"></i>Selfie Verification
                          </p>
                          <?php $selfiePath = str_replace('../', '', $row['selfie_photo']); ?>
                          <img src="<?= htmlspecialchars($selfiePath) ?>" 
                               class="img-fluid rounded border"
                               style="cursor: pointer; max-height: 200px; object-fit: cover; width: 100%;"
                               onclick="viewDocument('<?= htmlspecialchars($selfiePath) ?>', 'Selfie Photo')"
                               onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
                        </div>
                      </div>
                    </div>

                    <div class="modal-footer">
                      <?php if ($verificationStatus == "pending") { ?>
                        <form method="POST" action="users.php" class="d-inline">
                          <input type="hidden" name="id" value="<?= $row['verification_id'] ?>">
                          <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                          <button name="approve" class="btn btn-success" onclick="return confirm('✅ Approve this verification?\n\nThe user will be notified immediately and granted full access.');">
                            <i class="bi bi-check-circle-fill me-2"></i>Approve Verification
                          </button>
                        </form>

                        <button class="btn btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#rejectModal<?= $row['verification_id'] ?>"
                                data-bs-dismiss="modal">
                          <i class="bi bi-x-circle-fill me-2"></i>Reject Verification
                        </button>
                      <?php } ?>

                      <button class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Close
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Reject Reason Modal -->
              <div class="modal fade" id="rejectModal<?= $row['verification_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                      <h5 class="modal-title">
                        <i class="bi bi-x-circle-fill me-2"></i>Reject Verification
                      </h5>
                      <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <form method="POST" action="users.php">
                      <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['verification_id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">

                        <div class="alert alert-warning">
                          <i class="bi bi-exclamation-triangle-fill me-2"></i>
                          <strong>Important:</strong> The user will be notified of the rejection and can resubmit their verification.
                        </div>

                        <label class="form-label fw-bold">
                          <i class="bi bi-pencil-square me-2"></i>Reason for Rejection *
                        </label>
                        <textarea name="reason" required rows="4" class="form-control"
                          placeholder="Please provide a clear and specific reason for rejection. Include what needs to be corrected or improved."></textarea>
                      </div>

                      <div class="modal-footer">
                        <button type="submit" name="reject" class="btn btn-danger">
                          <i class="bi bi-send-fill me-2"></i>Submit Rejection
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                          <i class="bi bi-x-lg me-2"></i>Cancel
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <?php } ?>

              <?php } ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
          <ul class="pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= buildFilterUrl(['view' => 'management', 'page' => $page - 1]) ?>">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>

            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1) {
              echo '<li class="page-item"><a class="page-link" href="' . buildFilterUrl(['view' => 'management', 'page' => 1]) . '">1</a></li>';
              if ($start > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
            }
            
            for ($i = $start; $i <= $end; $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= buildFilterUrl(['view' => 'management', 'page' => $i]) ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; 
            
            if ($end < $totalPages) {
              if ($end < $totalPages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
              echo '<li class="page-item"><a class="page-link" href="' . buildFilterUrl(['view' => 'management', 'page' => $totalPages]) . '">' . $totalPages . '</a></li>';
            }
            ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= buildFilterUrl(['view' => 'management', 'page' => $page + 1]) ?>">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="text-center text-muted mt-3">
          <small>
            <i class="bi bi-info-circle me-1"></i>
            Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($offset + $limit, $totalRows) ?></strong> of <strong><?= $totalRows ?></strong> results
          </small>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<!-- Document Viewer Modal -->
<div class="doc-modal" id="docModal">
  <div class="doc-modal-content">
    <div class="doc-modal-header">
      <h3 class="doc-modal-title" id="docModalTitle">
        <i class="bi bi-file-earmark-image me-2"></i>Document Viewer
      </h3>
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
// Auto-dismiss alerts after 5 seconds with fade effect
document.addEventListener('DOMContentLoaded', function() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function(alert) {
    setTimeout(function() {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });

  // Add number counter animation to stat values
  const statValues = document.querySelectorAll('.stat-value');
  statValues.forEach(stat => {
    const finalValue = parseInt(stat.textContent);
    let currentValue = 0;
    const increment = finalValue / 50;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= finalValue) {
        stat.textContent = finalValue;
        clearInterval(timer);
      } else {
        stat.textContent = Math.floor(currentValue);
      }
    }, 20);
  });
});

// Enhanced Document Viewer with loading states
function viewDocument(docUrl, docType) {
  const modal = document.getElementById('docModal');
  const modalBody = document.getElementById('docModalBody');
  const modalTitle = document.getElementById('docModalTitle');
  const downloadBtn = document.getElementById('docDownloadBtn');
  
  modalTitle.innerHTML = `<i class="bi bi-file-earmark-image me-2"></i>${docType}`;
  downloadBtn.href = docUrl;
  downloadBtn.download = docType.replace(/\s+/g, '_') + '_' + docUrl.split('/').pop();
  
  modalBody.innerHTML = `
    <div class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Loading ${docType.toLowerCase()}...</p>
    </div>
  `;
  
  const extension = docUrl.split('.').pop().toLowerCase();
  
  if (extension === 'pdf') {
    const iframe = document.createElement('iframe');
    iframe.src = docUrl;
    iframe.onload = function() {
      modalBody.innerHTML = '';
      modalBody.appendChild(iframe);
    };
    iframe.onerror = function() {
      modalBody.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load PDF. The file may not exist or the path is incorrect.
        </div>
      `;
    };
  } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
    const img = document.createElement('img');
    img.src = docUrl;
    img.alt = docType;
    img.onload = function() {
      modalBody.innerHTML = '';
      modalBody.appendChild(img);
    };
    img.onerror = function() {
      modalBody.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load image. The file may not exist or the path is incorrect.
        </div>
      `;
    };
  } else {
    const iframe = document.createElement('iframe');
    iframe.src = docUrl;
    modalBody.innerHTML = '';
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

// Close modal when clicking outside
document.getElementById('docModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeDocModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeDocModal();
  }
});

// Add smooth scroll to highlighted row
window.addEventListener('load', function() {
  const highlightedRow = document.querySelector('tr[style*="background-color"]');
  if (highlightedRow) {
    setTimeout(() => {
      highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 500);
  }
});
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
>>>>>>> 700ac6438dddb58cc34531b90fc6b00d9b0b53e5
