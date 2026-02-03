<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'include/db.php';

// Get statistics
$stats = [];

// Total policies
$result = $conn->query("SELECT COUNT(*) as total FROM insurance_policies");
$stats['total_policies'] = $result->fetch_assoc()['total'];

// Active policies
$result = $conn->query("SELECT COUNT(*) as total FROM insurance_policies WHERE status = 'active'");
$stats['active_policies'] = $result->fetch_assoc()['total'];

// Total claims
$result = $conn->query("SELECT COUNT(*) as total FROM insurance_claims");
$stats['total_claims'] = $result->fetch_assoc()['total'];

// Pending claims
$result = $conn->query("SELECT COUNT(*) as total FROM insurance_claims WHERE status IN ('submitted', 'under_review')");
$stats['pending_claims'] = $result->fetch_assoc()['total'];

// Total premium collected
$result = $conn->query("SELECT SUM(premium_amount) as total FROM insurance_policies WHERE status IN ('active', 'claimed', 'expired')");
$stats['total_premium'] = $result->fetch_assoc()['total'] ?? 0;

// Total claims amount
$result = $conn->query("SELECT SUM(approved_amount) as total FROM insurance_claims WHERE status = 'approved'");
$stats['total_claims_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insurance Management - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <link href="include/notifications.css" rel="stylesheet">
  
  <style>
/* Additional inline styles for Insurance page */
.fade-in {
  animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Tab styles */
.tabs-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tabs-header {
  display: flex;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  padding: 0;
}

.tab-button {
  flex: 1;
  padding: 16px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  transition: all 0.3s ease;
  border-bottom: 3px solid transparent;
}

.tab-button:hover {
  background: #e9ecef;
  color: #1a1a1a;
}

.tab-button.active {
  color: #1a1a1a;
  background: white;
  border-bottom-color: #1a1a1a;
}

.tab-content {
  display: none;
  padding: 24px;
}

.tab-content.active {
  display: block;
  animation: fadeIn 0.3s ease;
}

/* Modal styles */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(5px);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.3s ease;
}

.modal.active {
  display: flex;
}

.modal-dialog {
  background: white;
  border-radius: 16px;
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(50px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-header {
  padding: 20px 24px;
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1a1a1a;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-close {
  background: rgba(255,255,255,0.1);
  border: 2px solid rgba(255,255,255,0.3);
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: white;
  color: #1a1a1a;
  transform: rotate(90deg);
}

.modal-body {
  padding: 24px;
  max-height: calc(90vh - 160px);
  overflow-y: auto;
}

.modal-body::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.modal-footer {
  padding: 16px 24px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 20px;
}

.info-item {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #1a1a1a;
}

.info-label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 6px;
}

.info-value {
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 600;
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 24px 0 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e9ecef;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.pagination-info {
  font-size: 14px;
  color: #666;
}

.pagination-controls {
  display: flex;
  gap: 8px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #1a1a1a;
  color: white;
  border-color: #1a1a1a;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn.active {
  background: #1a1a1a;
  color: white;
  border-color: #1a1a1a;
}

/* Export button */
.export-section {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.btn-export {
  padding: 10px 20px;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-export:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
}
</style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include 'include/sidebar.php'; ?>

  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar fade-in">
      <h1 class="page-title">
        <i class="bi bi-shield-check"></i>
        Insurance Management
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

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i> +12%
          </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_policies']); ?></div>
        <div class="stat-label">Total Policies</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i> +8%
          </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['active_policies']); ?></div>
        <div class="stat-label">Active Policies</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-file-earmark-text"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i> -3%
          </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_claims']); ?></div>
        <div class="stat-label">Total Claims</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i> +5%
          </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['pending_claims']); ?></div>
        <div class="stat-label">Pending Claims</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i> +15%
          </div>
        </div>
        <div class="stat-value">₱<?php echo number_format($stats['total_premium'], 0); ?></div>
        <div class="stat-label">Premium Collected</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%); color: white;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-trend down">
            <i class="bi bi-arrow-down"></i> -2%
          </div>
        </div>
        <div class="stat-value">₱<?php echo number_format($stats['total_claims_amount'], 0); ?></div>
        <div class="stat-label">Claims Approved</div>
      </div>
    </div>

    <!-- Filter & Tabs Section -->
    <div class="table-section fade-in" style="animation-delay: 0.2s;">
      <div class="tabs-container">
        <div class="tabs-header">
          <button class="tab-button active" data-tab="policies">
            <i class="bi bi-file-text"></i> Insurance Policies
          </button>
          <button class="tab-button" data-tab="claims">
            <i class="bi bi-file-medical"></i> Insurance Claims
          </button>
          <button class="tab-button" data-tab="providers">
            <i class="bi bi-building"></i> Providers
          </button>
        </div>

        <!-- Policies Tab -->
        <div class="tab-content active" id="policies-tab">
          <div class="export-section">
            <button class="btn-export" onclick="exportPolicies()">
              <i class="bi bi-download"></i> Export Policies
            </button>
          </div>
          
          <div class="filter-section">
            <div class="filter-row">
              <div class="search-box">
                <input type="text" id="policy-search" placeholder="🔍 Search by policy number, renter name...">
                <i class="bi bi-search"></i>
              </div>
              <select class="filter-dropdown" id="policy-status-filter">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="claimed">Claimed</option>
              </select>
              <button class="export-btn" onclick="loadPolicies()">
                <i class="bi bi-search"></i> Search
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table id="policies-table">
              <thead>
                <tr>
                  <th><i class="bi bi-hash me-1"></i>Policy #</th>
                  <th><i class="bi bi-person me-1"></i>Renter</th>
                  <th><i class="bi bi-car-front me-1"></i>Vehicle</th>
                  <th><i class="bi bi-shield me-1"></i>Coverage</th>
                  <th><i class="bi bi-currency-dollar me-1"></i>Premium</th>
                  <th><i class="bi bi-calendar me-1"></i>Period</th>
                  <th><i class="bi bi-info-circle me-1"></i>Status</th>
                  <th><i class="bi bi-gear me-1"></i>Actions</th>
                </tr>
              </thead>
              <tbody id="policies-tbody">
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div id="policies-pagination" class="pagination-container"></div>
        </div>

        <!-- Claims Tab -->
        <div class="tab-content" id="claims-tab">
          <div class="export-section">
            <button class="btn-export" onclick="exportClaims()">
              <i class="bi bi-download"></i> Export Claims
            </button>
          </div>
          
          <div class="filter-section">
            <div class="filter-row">
              <select class="filter-dropdown" id="claim-status-filter">
                <option value="all">All Status</option>
                <option value="submitted">Submitted</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="paid">Paid</option>
              </select>
              <select class="filter-dropdown" id="claim-priority-filter">
                <option value="all">All Priority</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="normal">Normal</option>
                <option value="low">Low</option>
              </select>
              <button class="export-btn" onclick="loadClaims()">
                <i class="bi bi-funnel"></i> Filter
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table id="claims-table">
              <thead>
                <tr>
                  <th>Claim #</th>
                  <th>Policy #</th>
                  <th>Claimant</th>
                  <th>Type</th>
                  <th>Claimed</th>
                  <th>Approved</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="claims-tbody">
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div id="claims-pagination" class="pagination-container"></div>
        </div>

        <!-- Providers Tab -->
        <div class="tab-content" id="providers-tab">
          <div class="export-section">
            <button class="btn-export" onclick="exportProviders()">
              <i class="bi bi-download"></i> Export Providers
            </button>
          </div>
          
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Provider Name</th>
                  <th>Contact</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="providers-tbody">
                <tr>
                  <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div id="providers-pagination" class="pagination-container"></div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Policy Details Modal -->
<div class="modal" id="policy-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3><i class="bi bi-shield-check"></i> Policy Details</h3>
      <button class="modal-close" onclick="closeModal('policy-modal')">&times;</button>
    </div>
    <div class="modal-body" id="policy-modal-body">
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
    </div>
  </div>
</div>

<!-- Claim Details Modal -->
<div class="modal" id="claim-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3><i class="bi bi-file-medical"></i> Claim Details</h3>
      <button class="modal-close" onclick="closeModal('claim-modal')">&times;</button>
    </div>
    <div class="modal-body" id="claim-modal-body">
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
    </div>
    <div class="modal-footer" id="claim-modal-footer"></div>
  </div>
</div>

<!-- Provider Edit Modal -->
<div class="modal" id="provider-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3><i class="bi bi-building"></i> Edit Provider</h3>
      <button class="modal-close" onclick="closeModal('provider-modal')">&times;</button>
    </div>
    <div class="modal-body" id="provider-modal-body">
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="include/notifications.js"></script>
<script>
const API_BASE = 'api/insurance';

// Pagination state
let policiesPage = 1;
let claimsPage = 1;
let providersPage = 1;

// Tab switching with animation
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        const tabName = button.dataset.tab;
        
        // Update buttons
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        button.classList.add('active');
        
        // Update content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Load data
        if (tabName === 'policies') loadPolicies();
        else if (tabName === 'claims') loadClaims();
        else if (tabName === 'providers') loadProviders();
    });
});

// Export functions
function exportPolicies() {
    const status = document.getElementById('policy-status-filter').value;
    const search = document.getElementById('policy-search').value;
    window.location.href = `${API_BASE}/admin/export_policies.php?status=${status}&search=${encodeURIComponent(search)}`;
}

function exportClaims() {
    const status = document.getElementById('claim-status-filter').value;
    const priority = document.getElementById('claim-priority-filter').value;
    window.location.href = `${API_BASE}/admin/export_claims.php?status=${status}&priority=${priority}`;
}

function exportProviders() {
    window.location.href = `${API_BASE}/admin/export_providers.php`;
}

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Close modal when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});

// Escape key closes modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

// Render pagination
function renderPagination(containerId, currentPage, totalPages, loadFunction) {
    const container = document.getElementById(containerId);
    if (!container || totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    const startRecord = ((currentPage - 1) * 20) + 1;
    const endRecord = Math.min(currentPage * 20, totalPages * 20);
    
    let html = `
        <div class="pagination-info">
            Showing ${startRecord} - ${endRecord} of ${totalPages * 20} records
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="${loadFunction}(1)" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-double-left"></i>
            </button>
            <button class="pagination-btn" onclick="${loadFunction}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i>
            </button>
    `;
    
    // Page numbers
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="${loadFunction}(${i})">
                ${i}
            </button>
        `;
    }
    
    html += `
            <button class="pagination-btn" onclick="${loadFunction}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="bi bi-chevron-right"></i>
            </button>
            <button class="pagination-btn" onclick="${loadFunction}(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="bi bi-chevron-double-right"></i>
            </button>
        </div>
    `;
    
    container.innerHTML = html;
}

// Load policies
function loadPolicies(page = 1) {
    policiesPage = page;
    const status = document.getElementById('policy-status-filter').value;
    const search = document.getElementById('policy-search').value;
    
    $.ajax({
        url: `${API_BASE}/admin/get_all_policies.php`,
        method: 'GET',
        data: { status, search, page: policiesPage },
        dataType: 'json',
        success: function(response) {
            const tbody = document.getElementById('policies-tbody');
            
            if (response.success && response.data && response.data.length > 0) {
                // Render pagination
                if (response.pagination) {
                    renderPagination('policies-pagination', response.pagination.page, response.pagination.total_pages, 'loadPolicies');
                }
                
                tbody.innerHTML = response.data.map(policy => `
                <tr>
                    <td><strong>${policy.policy_number}</strong></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-small">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(policy.renter.name)}&background=1a1a1a&color=fff">
                            </div>
                            <div class="user-info">
                                <span class="user-name">${policy.renter.name}</span>
                                <span class="user-email">${policy.renter.email}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong>${policy.vehicle.type}</strong><br>
                        <small style="color: #999;">${policy.vehicle.name}</small>
                    </td>
                    <td>
                        <strong style="text-transform: uppercase;">${policy.coverage_type}</strong><br>
                        <small style="color: #999;">₱${formatNumber(policy.coverage_limit)} limit</small>
                    </td>
                    <td><strong style="color: #1a1a1a;">₱${formatNumber(policy.premium_amount)}</strong></td>
                    <td>
                        ${formatDate(policy.policy_start)} - ${formatDate(policy.policy_end)}<br>
                        <small style="color: #999;">${policy.days_remaining} days remaining</small>
                    </td>
                    <td><span class="status-badge ${policy.status}">${policy.status}</span></td>
                    <td>
                        <button class="action-btn view" onclick="viewPolicy(${policy.policy_id})">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div style="padding: 40px 20px;">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ddd;"></i>
                                <p style="margin-top: 16px; color: #999; font-size: 14px;">No policies found matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        },
        error: function(xhr, status, error) {
            console.error('Policies Error:', error, xhr.responseText);
            document.getElementById('policies-tbody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div style="padding: 40px 20px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                            <p style="margin-top: 16px; color: #dc3545; font-size: 14px;">Error loading policies. Please try again later.</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
}

// Load claims
function loadClaims(page = 1) {
    claimsPage = page;
    const status = document.getElementById('claim-status-filter').value;
    const priority = document.getElementById('claim-priority-filter').value;
    
    $.ajax({
        url: `${API_BASE}/admin/get_all_claims.php`,
        method: 'GET',
        data: { status, priority, page: claimsPage },
        dataType: 'json',
        success: function(response) {
            const tbody = document.getElementById('claims-tbody');
            
            if (response.success && response.data && response.data.length > 0) {
                // Render pagination
                if (response.pagination) {
                    renderPagination('claims-pagination', response.pagination.page, response.pagination.total_pages, 'loadClaims');
                }
                
                tbody.innerHTML = response.data.map(claim => `
                <tr>
                    <td>
                        <strong>${claim.claim_number}</strong><br>
                        <small style="color: #999;">${formatDate(claim.created_at)}</small>
                    </td>
                    <td><strong>${claim.policy_number}</strong></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-small">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(claim.claimant.name)}&background=1a1a1a&color=fff">
                            </div>
                            <span>${claim.claimant.name}</span>
                        </div>
                    </td>
                    <td><span class="status-badge">${claim.claim_type}</span></td>
                    <td><strong style="color: #1a1a1a;">₱${formatNumber(claim.claimed_amount)}</strong></td>
                    <td><strong style="color: #198754;">₱${formatNumber(claim.approved_amount)}</strong></td>
                    <td><span class="status-badge ${claim.priority}">${claim.priority}</span></td>
                    <td><span class="status-badge ${claim.status}">${claim.status}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view" onclick="viewClaim(${claim.claim_id})" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${claim.status === 'submitted' || claim.status === 'under_review' ? `
                                <button class="action-btn approve" onclick="approveClaim(${claim.claim_id})" title="Approve">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="action-btn reject" onclick="rejectClaim(${claim.claim_id})" title="Reject">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div style="padding: 40px 20px;">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ddd;"></i>
                                <p style="margin-top: 16px; color: #999; font-size: 14px;">No claims found matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        },
        error: function(xhr, status, error) {
            console.error('Claims Error:', error, xhr.responseText);
            document.getElementById('claims-tbody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div style="padding: 40px 20px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                            <p style="margin-top: 16px; color: #dc3545; font-size: 14px;">Error loading claims. Please try again later.</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
}

// Load providers
function loadProviders(page = 1) {
    providersPage = page;
    $.ajax({
        url: `${API_BASE}/admin/get_providers.php`,
        method: 'GET',
        data: { page: providersPage },
        dataType: 'json',
        success: function(response) {
            const tbody = document.getElementById('providers-tbody');
            
            if (response.success && response.data && response.data.length > 0) {
                // Render pagination
                if (response.pagination) {
                    renderPagination('providers-pagination', response.pagination.page, response.pagination.total_pages, 'loadProviders');
                }
                
                tbody.innerHTML = response.data.map(provider => `
                <tr>
                    <td><strong>${provider.provider_name}</strong></td>
                    <td><i class="bi bi-telephone me-1"></i>${provider.contact_phone}</td>
                    <td><i class="bi bi-envelope me-1"></i>${provider.contact_email}</td>
                    <td><span class="status-badge ${provider.is_active ? 'approved' : 'cancelled'}">${provider.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="action-btn view" onclick="viewProvider(${provider.id})" title="Edit Provider">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div style="padding: 40px 20px;">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ddd;"></i>
                                <p style="margin-top: 16px; color: #999; font-size: 14px;">No providers found. Add your first insurance provider.</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        },
        error: function(xhr, status, error) {
            console.error('Providers Error:', error, xhr.responseText);
            document.getElementById('providers-tbody').innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div style="padding: 40px 20px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                            <p style="margin-top: 16px; color: #dc3545; font-size: 14px;">Error loading providers. Please try again later.</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
}

// View policy details
function viewPolicy(policyId) {
    const modal = document.getElementById('policy-modal');
    const modalBody = document.getElementById('policy-modal-body');
    
    modal.classList.add('active');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    
    $.ajax({
        url: `${API_BASE}/admin/get_policy_details.php`,
        method: 'GET',
        data: { id: policyId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const policy = response.data;
                modalBody.innerHTML = `
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Policy Number</div>
                            <div class="info-value">${policy.policy_number}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge ${policy.status}">${policy.status}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Coverage Type</div>
                            <div class="info-value">${policy.coverage_type}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Premium Amount</div>
                            <div class="info-value">₱${formatNumber(policy.premium_amount)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Coverage Limit</div>
                            <div class="info-value">₱${formatNumber(policy.coverage_limit)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Deductible</div>
                            <div class="info-value">₱${formatNumber(policy.deductible)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Policy Period</div>
                            <div class="info-value">${formatDate(policy.policy_start)} - ${formatDate(policy.policy_end)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Days Remaining</div>
                            <div class="info-value">${policy.days_remaining} days</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Renter Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value">${policy.renter.name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">${policy.renter.email}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact</div>
                            <div class="info-value">${policy.renter.contact}</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Vehicle Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Vehicle</div>
                            <div class="info-value">${policy.vehicle.brand} ${policy.vehicle.model} ${policy.vehicle.year}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Plate Number</div>
                            <div class="info-value">${policy.vehicle.plate}</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Provider Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Provider</div>
                            <div class="info-value">${policy.provider.name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact</div>
                            <div class="info-value">${policy.provider.phone} / ${policy.provider.email}</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Claims Summary</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Total Claims</div>
                            <div class="info-value">${policy.claims_summary.total_claims}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Approved Claims</div>
                            <div class="info-value">${policy.claims_summary.approved_claims}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Claimed Amount</div>
                            <div class="info-value">₱${formatNumber(policy.claims_summary.total_claimed_amount)}</div>
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${response.message}</div>`;
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading policy details</div>';
        }
    });
}

// View claim details
function viewClaim(claimId) {
    const modal = document.getElementById('claim-modal');
    const modalBody = document.getElementById('claim-modal-body');
    const modalFooter = document.getElementById('claim-modal-footer');
    
    modal.classList.add('active');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modalFooter.innerHTML = '';
    
    $.ajax({
        url: `${API_BASE}/admin/get_claim_details.php`,
        method: 'GET',
        data: { id: claimId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const claim = response.data;
                modalBody.innerHTML = `
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Claim Number</div>
                            <div class="info-value">${claim.claim_number}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge ${claim.status}">${claim.status}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Priority</div>
                            <div class="info-value"><span class="status-badge ${claim.priority}">${claim.priority}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Claim Type</div>
                            <div class="info-value">${claim.claim_type}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Incident Date</div>
                            <div class="info-value">${formatDate(claim.incident_date)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Incident Location</div>
                            <div class="info-value">${claim.incident_location || 'N/A'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Claimed Amount</div>
                            <div class="info-value">₱${formatNumber(claim.claimed_amount)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Approved Amount</div>
                            <div class="info-value">₱${formatNumber(claim.approved_amount)}</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Incident Description</div>
                    <div style="padding: 16px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
                        ${claim.incident_description}
                    </div>
                    
                    <div class="section-title">Claimant Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value">${claim.claimant.name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">${claim.claimant.email}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact</div>
                            <div class="info-value">${claim.claimant.contact}</div>
                        </div>
                    </div>
                    
                    ${claim.review_notes ? `
                        <div class="section-title">Review Notes</div>
                        <div style="padding: 16px; background: #f8f9fa; border-radius: 8px;">
                            ${claim.review_notes}
                        </div>
                    ` : ''}
                    
                    ${claim.rejection_reason ? `
                        <div class="section-title">Rejection Reason</div>
                        <div style="padding: 16px; background: #ffebee; border-radius: 8px; color: #c62828;">
                            ${claim.rejection_reason}
                        </div>
                    ` : ''}
                `;
                
                // Add action buttons if claim is pending
                if (claim.status === 'submitted' || claim.status === 'under_review') {
                    modalFooter.innerHTML = `
                        <button class="btn btn-success" onclick="approveClaimFromModal(${claimId})">
                            <i class="bi bi-check-lg"></i> Approve Claim
                        </button>
                        <button class="btn btn-danger" onclick="rejectClaimFromModal(${claimId})">
                            <i class="bi bi-x-lg"></i> Reject Claim
                        </button>
                    `;
                }
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${response.message}</div>`;
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading claim details</div>';
        }
    });
}

// Approve claim from modal
function approveClaimFromModal(claimId) {
    const approvedAmount = prompt('Enter approved amount:');
    if (!approvedAmount) return;
    
    const reviewNotes = prompt('Enter review notes (optional):') || '';
    
    $.ajax({
        url: `${API_BASE}/admin/approve_claim.php`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            claim_id: claimId,
            approved_amount: parseFloat(approvedAmount),
            review_notes: reviewNotes,
            admin_id: 1
        }),
        success: function(response) {
            if (response.success) {
                alert('✓ Claim approved successfully!');
                closeModal('claim-modal');
                loadClaims();
            } else {
                alert('✗ Error: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Error approving claim');
        }
    });
}

// Reject claim from modal
function rejectClaimFromModal(claimId) {
    const rejectionReason = prompt('Enter rejection reason:');
    if (!rejectionReason) return;
    
    $.ajax({
        url: `${API_BASE}/admin/reject_claim.php`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            claim_id: claimId,
            rejection_reason: rejectionReason,
            admin_id: 1
        }),
        success: function(response) {
            if (response.success) {
                alert('✓ Claim rejected successfully!');
                closeModal('claim-modal');
                loadClaims();
            } else {
                alert('✗ Error: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Error rejecting claim');
        }
    });
}

// Approve claim (quick action from table)
function approveClaim(claimId) {
    const approvedAmount = prompt('Enter approved amount:');
    if (!approvedAmount) return;
    
    const reviewNotes = prompt('Enter review notes (optional):') || '';
    
    $.ajax({
        url: `${API_BASE}/admin/approve_claim.php`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            claim_id: claimId,
            approved_amount: parseFloat(approvedAmount),
            review_notes: reviewNotes,
            admin_id: 1
        }),
        success: function(response) {
            if (response.success) {
                alert('✓ Claim approved successfully!');
                loadClaims();
            } else {
                alert('✗ Error: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Error approving claim');
        }
    });
}

// Reject claim (quick action from table)
function rejectClaim(claimId) {
    const rejectionReason = prompt('Enter rejection reason:');
    if (!rejectionReason) return;
    
    $.ajax({
        url: `${API_BASE}/admin/reject_claim.php`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            claim_id: claimId,
            rejection_reason: rejectionReason,
            admin_id: 1
        }),
        success: function(response) {
            if (response.success) {
                alert('✓ Claim rejected successfully!');
                loadClaims();
            } else {
                alert('✗ Error: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Error rejecting claim');
        }
    });
}

// View/Edit provider
function viewProvider(providerId) {
    const modal = document.getElementById('provider-modal');
    const modalBody = document.getElementById('provider-modal-body');
    
    modal.classList.add('active');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    
    $.ajax({
        url: `${API_BASE}/admin/get_provider_details.php`,
        method: 'GET',
        data: { id: providerId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const provider = response.data;
                modalBody.innerHTML = `
                    <form id="provider-form">
                        <input type="hidden" id="provider-id" value="${provider.id}">
                        
                        <div class="mb-3">
                            <label for="provider-name" class="form-label">Provider Name</label>
                            <input type="text" class="form-control" id="provider-name" value="${provider.provider_name}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="provider-phone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="provider-phone" value="${provider.contact_phone}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="provider-email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="provider-email" value="${provider.contact_email}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="provider-status" class="form-label">Status</label>
                            <select class="form-control" id="provider-status">
                                <option value="active" ${provider.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${provider.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="section-title">Statistics</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Total Policies</div>
                                <div class="info-value">${provider.statistics.total_policies}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Active Policies</div>
                                <div class="info-value">${provider.statistics.active_policies}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Premiums</div>
                                <div class="info-value">₱${formatNumber(provider.statistics.total_premiums)}</div>
                            </div>
                        </div>
                        
                        <div class="modal-footer mt-4">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('provider-modal')">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                `;
                
                // Handle form submission
                document.getElementById('provider-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    updateProvider();
                });
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger">${response.message}</div>`;
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading provider details</div>';
        }
    });
}

// Update provider
function updateProvider() {
    const providerId = document.getElementById('provider-id').value;
    const providerName = document.getElementById('provider-name').value;
    const providerPhone = document.getElementById('provider-phone').value;
    const providerEmail = document.getElementById('provider-email').value;
    const providerStatus = document.getElementById('provider-status').value;
    
    $.ajax({
        url: `${API_BASE}/admin/update_provider.php`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            id: parseInt(providerId),
            provider_name: providerName,
            contact_phone: providerPhone,
            contact_email: providerEmail,
            status: providerStatus
        }),
        success: function(response) {
            if (response.success) {
                alert('✓ Provider updated successfully!');
                closeModal('provider-modal');
                loadProviders();
            } else {
                alert('✗ Error: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Error updating provider');
        }
    });
}

// Helper functions
function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Load initial data
loadPolicies();
</script>
</body>
</html>