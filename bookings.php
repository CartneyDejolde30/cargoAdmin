<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings Management - CarGo Admin</title>
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
        <i class="bi bi-book"></i>
        Bookings Management
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">7</span>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12%
          </div>
        </div>
        <div class="stat-value">23</div>
        <div class="stat-label">Pending Bookings</div>
        <div class="stat-detail">Requires immediate attention</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-check-circle"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +18%
          </div>
        </div>
        <div class="stat-value">156</div>
        <div class="stat-label">Confirmed Bookings</div>
        <div class="stat-detail">Ready for pickup</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-car-front-fill"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">42</div>
        <div class="stat-label">Ongoing Rentals</div>
        <div class="stat-detail">Currently active</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +25%
          </div>
        </div>
        <div class="stat-value">₱524K</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">This month</div>
      </div>
    </div>

    <div class="quick-stats">
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #fff8e1; color: #f57c00;">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="quick-stat-value">5</div>
        <div class="quick-stat-label">Overdue Bookings</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #e8f5e9; color: #2e7d32;">
          <i class="bi bi-star"></i>
        </div>
        <div class="quick-stat-value">4.8</div>
        <div class="quick-stat-label">Average Rating</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #f3e5f5; color: #7b1fa2;">
          <i class="bi bi-arrow-repeat"></i>
        </div>
        <div class="quick-stat-value">12</div>
        <div class="quick-stat-label">Repeat Customers</div>
      </div>
      <div class="quick-stat-card">
        <div class="quick-stat-icon" style="background: #ffebee; color: #d32f2f;">
          <i class="bi bi-x-circle"></i>
        </div>
        <div class="quick-stat-value">8</div>
        <div class="quick-stat-label">Cancelled Today</div>
      </div>
    </div>

    <div class="filter-section">
      <div class="filter-row">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search by renter, owner, or car type...">
          <i class="bi bi-search"></i>
        </div>
        <select class="filter-dropdown" id="statusFilter">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="ongoing">Ongoing</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select class="filter-dropdown" id="paymentFilter">
          <option value="">Payment Status</option>
          <option value="paid">Paid</option>
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partial</option>
        </select>
        <select class="filter-dropdown" id="dateFilter">
          <option value="">Date Range</option>
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="year">This Year</option>
        </select>
        <button class="export-btn" onclick="exportBookings()">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </div>
    </div>

    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">All Bookings</h2>
        <div class="table-controls">
          <button class="table-btn active" onclick="filterTable('all')">All (229)</button>
          <button class="table-btn" onclick="filterTable('pending')">Pending (23)</button>
          <button class="table-btn" onclick="filterTable('ongoing')">Active (42)</button>
          <button class="table-btn" onclick="filterTable('completed')">Completed (156)</button>
        </div>
      </div>

      <div class="table-responsive">
        <table id="bookingsTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Renter</th>
              <th>Owner</th>
              <th>Car Details</th>
              <th>Rental Period</th>
              <th>Location</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr data-status="pending" data-payment="unpaid">
              <td><strong>#BK-2451</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="Juan">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Pedro+Santos&background=1a1a1a&color=fff" alt="Pedro">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Pedro Santos</span>
                    <span class="user-email">pedro@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Toyota Vios</strong><br>
                  <small style="color: #999;">Sedan • 2022 • ABC 1234</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 20 - Nov 25</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>San Francisco</strong><br>
                  <small style="color: #999;">to Butuan City</small>
                </div>
              </td>
              <td><strong>₱5,000</strong></td>
              <td><span class="status-badge pending">Pending</span></td>
              <td><span class="payment-badge unpaid">Unpaid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#bookingModal1" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn approve" title="Approve">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button class="action-btn reject" title="Reject">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="confirmed" data-payment="paid">
              <td><strong>#BK-2450</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Maria+Garcia&background=1a1a1a&color=fff" alt="Maria">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Maria Garcia</span>
                    <span class="user-email">maria@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Reyes&background=1a1a1a&color=fff" alt="Carlos">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Carlos Reyes</span>
                    <span class="user-email">carlos@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Honda City</strong><br>
                  <small style="color: #999;">Sedan • 2023 • DEF 5678</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 22 - Nov 27</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Davao City</strong><br>
                  <small style="color: #999;">to Cagayan de Oro</small>
                </div>
              </td>
              <td><strong>₱6,500</strong></td>
              <td><span class="status-badge confirmed">Confirmed</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" data-bs-toggle="modal" data-bs-target="#bookingModal2" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn delete" title="Cancel">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="ongoing" data-payment="paid">
              <td><strong>#BK-2449</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Robert+Tan&background=1a1a1a&color=fff" alt="Robert">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Robert Tan</span>
                    <span class="user-email">robert@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Anna+Lopez&background=1a1a1a&color=fff" alt="Anna">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Anna Lopez</span>
                    <span class="user-email">anna@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Mitsubishi Montero</strong><br>
                  <small style="color: #999;">SUV • 2021 • GHI 9012</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 18 - Nov 24</strong><br>
                  <small style="color: #999;">6 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Manila</strong><br>
                  <small style="color: #999;">to Baguio City</small>
                </div>
              </td>
              <td><strong>₱12,000</strong></td>
              <td><span class="status-badge ongoing">Ongoing</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn" style="background: #e3f2fd; color: #1976d2;" title="Track">
                    <i class="bi bi-geo-alt"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="completed" data-payment="paid">
              <td><strong>#BK-2448</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Lisa+Ramos&background=1a1a1a&color=fff" alt="Lisa">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Lisa Ramos</span>
                    <span class="user-email">lisa@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mark+Santos&background=1a1a1a&color=fff" alt="Mark">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mark Santos</span>
                    <span class="user-email">mark@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Toyota Fortuner</strong><br>
                  <small style="color: #999;">SUV • 2022 • JKL 3456</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 15 - Nov 20</strong><br>
                  <small style="color: #999;">5 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Cebu City</strong><br>
                  <small style="color: #999;">to Bohol</small>
                </div>
              </td>
              <td><strong>₱9,000</strong></td>
              <td><span class="status-badge completed">Completed</span></td>
              <td><span class="payment-badge paid">Paid</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn" style="background: #fff3e0; color: #ef6c00;" title="Generate Invoice">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr data-status="cancelled" data-payment="unpaid">
              <td><strong>#BK-2447</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=David+Cruz&background=1a1a1a&color=fff" alt="David">
                  </div>
                  <div class="user-info">
                    <span class="user-name">David Cruz</span>
                    <span class="user-email">david@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Sofia+Martin&background=1a1a1a&color=fff" alt="Sofia">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Sofia Martin</span>
                    <span class="user-email">sofia@email.com</span>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nissan Navara</strong><br>
                  <small style="color: #999;">Pickup • 2023 • MNO 7890</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Nov 10 - Nov 12</strong><br>
                  <small style="color: #999;">2 days</small>
                </div>
              </td>
              <td>
                <div>
                  <strong>Iloilo City</strong><br>
                  <small style="color: #999;">to Bacolod City</small>
                </div>
              </td>
              <td><strong>₱3,500</strong></td>
              <td><span class="status-badge cancelled">Cancelled</span></td>
              <td><span class="payment-badge unpaid">Refunded</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong>1-5</strong> of <strong>229</strong> bookings
        </div>
        <div class="pagination-controls">
          <button class="page-btn"><i class="bi bi-chevron-left"></i></button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn">4</button>
          <button class="page-btn">5</button>
          <button class="page-btn"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal1" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Booking Details - #BK-2451</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="detail-section">
          <div class="detail-section-title">Booking Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Booking ID</span>
              <span class="detail-value">#BK-2451</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Booking Date</span>
              <span class="detail-value">November 15, 2025 - 10:30 AM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status</span>
              <span class="status-badge pending">Pending Approval</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Payment Status</span>
              <span class="payment-badge unpaid">Awaiting Payment</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Renter Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Full Name</span>
              <span class="detail-value">Juan Dela Cruz</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email</span>
              <span class="detail-value">juan.delacruz@email.com</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phone Number</span>
              <span class="detail-value">+63 912 345 6789</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Driver's License</span>
              <span class="detail-value">N01-12-345678</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Vehicle Information</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Vehicle Make & Model</span>
              <span class="detail-value">Toyota Vios</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Year</span>
              <span class="detail-value">2022</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Plate Number</span>
              <span class="detail-value">ABC 1234</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Vehicle Type</span>
              <span class="detail-value">Sedan</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Rental Details</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Pickup Date & Time</span>
              <span class="detail-value">November 20, 2025 - 9:00 AM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Drop-off Date & Time</span>
              <span class="detail-value">November 25, 2025 - 6:00 PM</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Rental Duration</span>
              <span class="detail-value">5 Days</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Daily Rate</span>
              <span class="detail-value">₱1,000.00</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Payment Breakdown</div>
          <div class="detail-row">
            <div class="detail-item">
              <span class="detail-label">Base Rental (5 days × ₱1,000)</span>
              <span class="detail-value">₱5,000.00</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Service Fee (10%)</span>
              <span class="detail-value">₱500.00</span>
            </div>
            <div class="detail-item">
              <span class="detail-label" style="font-size: 14px; color: #1a1a1a;">Total Amount</span>
              <span class="detail-value" style="font-size: 24px; color: #1a1a1a;">₱5,500.00</span>
            </div>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-section-title">Activity Timeline</div>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <div class="timeline-title">Booking Created</div>
                <div class="timeline-time">November 15, 2025 - 10:30 AM</div>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot" style="border-color: #dc3545; background: #dc3545;"></div>
              <div class="timeline-content">
                <div class="timeline-title">Pending Admin Review</div>
                <div class="timeline-time">Current Status</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="modal-btn secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="modal-btn reject">Reject Booking</button>
        <button type="button" class="modal-btn approve">Approve Booking</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter table by status
function filterTable(status) {
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  const buttons = document.querySelectorAll('.table-btn');
  
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  rows.forEach(row => {
    if (status === 'all') {
      row.style.display = '';
    } else {
      const rowStatus = row.getAttribute('data-status');
      row.style.display = rowStatus === status ? '' : 'none';
    }
  });
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function(e) {
  const status = e.target.value;
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    if (!status) {
      row.style.display = '';
    } else {
      const rowStatus = row.getAttribute('data-status');
      row.style.display = rowStatus === status ? '' : 'none';
    }
  });
});

// Payment filter
document.getElementById('paymentFilter').addEventListener('change', function(e) {
  const payment = e.target.value;
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  
  rows.forEach(row => {
    if (!payment) {
      row.style.display = '';
    } else {
      const rowPayment = row.getAttribute('data-payment');
      row.style.display = rowPayment === payment ? '' : 'none';
    }
  });
});

// Export bookings
function exportBookings() {
  alert('Exporting bookings report...\n\nThis will generate a CSV file with all booking data.');
  // Add actual export functionality here
}

// Notification on page load
window.addEventListener('load', function() {
  console.log('Bookings Management System loaded successfully');
});
</script>
</body>
</html>