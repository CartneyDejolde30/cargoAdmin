<?php
// Include database connection and session check if needed
// session_start();
// require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Statistics - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
  <!-- Include Sidebar -->
  <?php include 'include/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-bar-chart"></i>
        Sales Statistics
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">3</span>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12.5%
          </div>
        </div>
        <div class="stat-value">₱524,000</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">This month: ₱145,000</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8.3%
          </div>
        </div>
        <div class="stat-value">156</div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-detail">This month: 42 bookings</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-graph-up"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15.2%
          </div>
        </div>
        <div class="stat-value">₱3,359</div>
        <div class="stat-label">Average Booking Value</div>
        <div class="stat-detail">Up from ₱2,915</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-star"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +0.3
          </div>
        </div>
        <div class="stat-value">4.8</div>
        <div class="stat-label">Average Rating</div>
        <div class="stat-detail">Based on 234 reviews</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-row">
        <select class="filter-dropdown">
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Last 90 Days</option>
          <option>This Year</option>
          <option>All Time</option>
        </select>
        <select class="filter-dropdown">
          <option>All Cars</option>
          <option>Sedan</option>
          <option>SUV</option>
          <option>Van</option>
          <option>Pickup</option>
        </select>
        <select class="filter-dropdown">
          <option>All Locations</option>
          <option>Midsayap</option>
          <option>Butuan</option>
          <option>Manila</option>
        </select>
        <button class="add-user-btn">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </div>
    </div>

    <!-- Revenue Chart Section -->
    <div class="table-section" style="margin-bottom: 30px;">
      <div class="section-header">
        <h2 class="section-title">Revenue Overview</h2>
        <div class="table-controls">
          <button class="table-btn active">Daily</button>
          <button class="table-btn">Weekly</button>
          <button class="table-btn">Monthly</button>
        </div>
      </div>
      <div style="padding: 20px;">
        <canvas id="revenueChart" style="max-height: 400px;"></canvas>
      </div>
    </div>

    <!-- Top Performing Cars -->
    <div class="table-section" style="margin-bottom: 30px;">
      <div class="section-header">
        <h2 class="section-title">Top Performing Cars</h2>
        <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Rank</th>
              <th>Car</th>
              <th>Total Bookings</th>
              <th>Revenue</th>
              <th>Avg. Rating</th>
              <th>Trend</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>#1</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Vios&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Vios 2022</span>
                    <span class="user-email">ABC-1234 • Sedan</span>
                  </div>
                </div>
              </td>
              <td><strong>42</strong></td>
              <td><strong>₱84,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.9</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +18%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#2</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Honda+City&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Honda City 2023</span>
                    <span class="user-email">DEF-5678 • Sedan</span>
                  </div>
                </div>
              </td>
              <td><strong>38</strong></td>
              <td><strong>₱76,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.8</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +12%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#3</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mitsubishi+Xpander&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mitsubishi Xpander 2021</span>
                    <span class="user-email">GHI-9012 • MPV</span>
                  </div>
                </div>
              </td>
              <td><strong>35</strong></td>
              <td><strong>₱70,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.7</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +8%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#4</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Fortuner&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Fortuner 2022</span>
                    <span class="user-email">JKL-3456 • SUV</span>
                  </div>
                </div>
              </td>
              <td><strong>28</strong></td>
              <td><strong>₱84,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.9</span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  +15%
                </div>
              </td>
            </tr>
            <tr>
              <td><strong>#5</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Suzuki+Ertiga&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Suzuki Ertiga 2020</span>
                    <span class="user-email">MNO-7890 • MPV</span>
                  </div>
                </div>
              </td>
              <td><strong>25</strong></td>
              <td><strong>₱50,000</strong></td>
              <td>
                <span style="color: #f57c00;">★ 4.6</span>
              </td>
              <td>
                <div class="stat-trend down">
                  <i class="bi bi-arrow-down"></i>
                  -3%
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="table-section">
      <div class="section-header">
        <h2 class="section-title">Recent Transactions</h2>
        <a href="bookings.php" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Car</th>
              <th>Duration</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>#BK-1045</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Juan+Cruz&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Juan Dela Cruz</span>
                    <span class="user-email">juan@email.com</span>
                  </div>
                </div>
              </td>
              <td>Toyota Vios 2022</td>
              <td>3 days</td>
              <td><strong>₱3,000</strong></td>
              <td><span class="status-badge verified">Completed</span></td>
              <td>Dec 5, 2025</td>
            </tr>
            <tr>
              <td><strong>#BK-1044</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Maria Santos</span>
                    <span class="user-email">maria@email.com</span>
                  </div>
                </div>
              </td>
              <td>Honda City 2023</td>
              <td>5 days</td>
              <td><strong>₱6,000</strong></td>
              <td><span class="status-badge pending">Ongoing</span></td>
              <td>Dec 4, 2025</td>
            </tr>
            <tr>
              <td><strong>#BK-1043</strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Carlos+Reyes&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Carlos Reyes</span>
                    <span class="user-email">carlos@email.com</span>
                  </div>
                </div>
              </td>
              <td>Mitsubishi Xpander</td>
              <td>2 days</td>
              <td><strong>₱2,400</strong></td>
              <td><span class="status-badge verified">Completed</span></td>
              <td>Dec 3, 2025</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-section">
        <div class="pagination-info">
          Showing <strong>1-3</strong> of <strong>156</strong> transactions
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: 'Revenue',
      data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
      borderColor: '#1a1a1a',
      backgroundColor: 'rgba(26, 26, 26, 0.1)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointRadius: 6,
      pointBackgroundColor: '#1a1a1a',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointHoverRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleColor: '#fff',
        bodyColor: '#fff',
        padding: 12,
        cornerRadius: 8,
        displayColors: false,
        callbacks: {
          label: function(context) {
            return '₱' + context.parsed.y.toLocaleString();
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return '₱' + (value / 1000) + 'k';
          },
          font: {
            size: 12,
            weight: '600'
          }
        },
        grid: {
          color: '#f0f0f0'
        }
      },
      x: {
        ticks: {
          font: {
            size: 12,
            weight: '600'
          }
        },
        grid: {
          display: false
        }
      }
    }
  }
});
</script>
</body>
</html>