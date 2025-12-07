<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Reports - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <style>
    /* Report Card Styles */
    .report-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 25px;
      transition: all 0.3s ease;
    }

    .report-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .report-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .report-card-title {
      font-size: 18px;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .report-card-title i {
      font-size: 24px;
      color: #1a1a1a;
    }

    .report-actions {
      display: flex;
      gap: 10px;
    }

    .report-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .report-btn.download {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      color: white;
    }

    .report-btn.print {
      background: #f5f5f5;
      color: #666;
    }

    .report-btn:hover {
      transform: translateY(-2px);
    }

    /* Chart Container */
    .chart-container {
      position: relative;
      height: 300px;
      margin: 20px 0;
    }

    /* Progress Bar Styles */
    .progress-item {
      margin-bottom: 15px;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 13px;
      font-weight: 600;
    }

    .progress {
      height: 10px;
      border-radius: 10px;
      background: #f0f0f0;
    }

    .progress-bar {
      border-radius: 10px;
      transition: width 1s ease;
    }

    /* Issue Badge */
    .issue-badge {
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      display: inline-block;
    }

    .issue-badge.critical {
      background: #ffebee;
      color: #d32f2f;
    }

    .issue-badge.warning {
      background: #fff8e1;
      color: #f57c00;
    }

    .issue-badge.resolved {
      background: #e8f8f0;
      color: #009944;
    }

    /* Metric Box */
    .metric-box {
      background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .metric-box:hover {
      transform: scale(1.05);
    }

    .metric-value {
      font-size: 32px;
      font-weight: 800;
      color: #1a1a1a;
      margin-bottom: 8px;
    }

    .metric-label {
      font-size: 12px;
      color: #666;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Filter Bar */
    .filter-bar {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .filter-bar-content {
      display: flex;
      gap: 15px;
      align-items: center;
      flex-wrap: wrap;
    }

    .filter-label {
      font-size: 14px;
      font-weight: 600;
      color: #666;
    }

    /* Table Enhancements */
    .rank-badge {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
    }

    .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: white; }
    .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A0A0A0 100%); color: white; }
    .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #B8860B 100%); color: white; }
    .rank-other { background: #f5f5f5; color: #666; }

    /* Print Styles */
    @media print {
      .sidebar, .top-bar, .filter-bar, .report-actions {
        display: none !important;
      }
      .main-content {
        margin-left: 0;
      }
      .report-card {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">
  <?php include('include/sidebar.php'); ?>

  <main class="main-content">
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-file-text"></i>
        Car Reports & Analytics
      </h1>
      <div class="user-profile">
        <button class="notification-btn">
          <i class="bi bi-bell"></i>
          <span class="notification-badge">4</span>
        </button>
        <div class="user-avatar">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1a1a1a&color=fff" alt="Admin">
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="filter-bar-content">
        <span class="filter-label">Report Period:</span>
        <select class="filter-dropdown" id="periodFilter">
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option selected>Last 3 Months</option>
          <option>Last 6 Months</option>
          <option>This Year</option>
          <option>All Time</option>
        </select>
        <select class="filter-dropdown" id="carTypeFilter">
          <option>All Car Types</option>
          <option>Sedan</option>
          <option>SUV</option>
          <option>Van</option>
          <option>Pickup</option>
        </select>
        <select class="filter-dropdown" id="locationFilter">
          <option>All Locations</option>
          <option>Midsayap</option>
          <option>Butuan</option>
          <option>Manila</option>
          <option>Davao</option>
        </select>
        <button class="add-user-btn" onclick="generateFullReport()">
          <i class="bi bi-file-earmark-pdf"></i>
          Generate Full Report
        </button>
      </div>
    </div>

    <!-- Quick Metrics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-car-front"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +8%
          </div>
        </div>
        <div class="stat-value">87</div>
        <div class="stat-label">Total Active Cars</div>
        <div class="stat-detail">12 pending approval</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +15%
          </div>
        </div>
        <div class="stat-value">₱524K</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">From all cars</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-speedometer2"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +12%
          </div>
        </div>
        <div class="stat-value">78%</div>
        <div class="stat-label">Utilization Rate</div>
        <div class="stat-detail">Average across fleet</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-star"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +0.2
          </div>
        </div>
        <div class="stat-value">4.7</div>
        <div class="stat-label">Average Rating</div>
        <div class="stat-detail">Based on 432 reviews</div>
      </div>
    </div>

    <!-- Top Performing Cars -->
    <div class="report-card">
      <div class="report-card-header">
        <div class="report-card-title">
          <i class="bi bi-trophy"></i>
          Top Performing Cars
        </div>
        <div class="report-actions">
          <button class="report-btn download" onclick="downloadReport('top-cars')">
            <i class="bi bi-download"></i>
            Download
          </button>
          <button class="report-btn print" onclick="printReport('top-cars')">
            <i class="bi bi-printer"></i>
            Print
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Rank</th>
              <th>Car Details</th>
              <th>Owner</th>
              <th>Total Bookings</th>
              <th>Revenue Generated</th>
              <th>Utilization</th>
              <th>Avg Rating</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="rank-badge rank-1">1</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Vios&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Vios 2022</span>
                    <span class="user-email">ABC-1234 • Sedan • White</span>
                  </div>
                </div>
              </td>
              <td>Pedro Santos</td>
              <td><strong>48</strong> bookings</td>
              <td><strong>₱96,000</strong></td>
              <td>
                <div class="progress-item">
                  <div class="progress">
                    <div class="progress-bar" style="width: 92%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);"></div>
                  </div>
                </div>
                <small>92%</small>
              </td>
              <td><span style="color: #f57c00;">★ 4.9</span></td>
              <td><span class="status-badge verified">Active</span></td>
            </tr>
            <tr>
              <td>
                <div class="rank-badge rank-2">2</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Honda+City&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Honda City 2023</span>
                    <span class="user-email">DEF-5678 • Sedan • Silver</span>
                  </div>
                </div>
              </td>
              <td>Maria Garcia</td>
              <td><strong>42</strong> bookings</td>
              <td><strong>₱84,000</strong></td>
              <td>
                <div class="progress-item">
                  <div class="progress">
                    <div class="progress-bar" style="width: 88%; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                  </div>
                </div>
                <small>88%</small>
              </td>
              <td><span style="color: #f57c00;">★ 4.8</span></td>
              <td><span class="status-badge verified">Active</span></td>
            </tr>
            <tr>
              <td>
                <div class="rank-badge rank-3">3</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Toyota+Fortuner&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Toyota Fortuner 2022</span>
                    <span class="user-email">GHI-9012 • SUV • Black</span>
                  </div>
                </div>
              </td>
              <td>Carlos Reyes</td>
              <td><strong>38</strong> bookings</td>
              <td><strong>₱114,000</strong></td>
              <td>
                <div class="progress-item">
                  <div class="progress">
                    <div class="progress-bar" style="width: 85%; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                  </div>
                </div>
                <small>85%</small>
              </td>
              <td><span style="color: #f57c00;">★ 4.9</span></td>
              <td><span class="status-badge verified">Active</span></td>
            </tr>
            <tr>
              <td>
                <div class="rank-badge rank-other">4</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mitsubishi+Xpander&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mitsubishi Xpander 2021</span>
                    <span class="user-email">JKL-3456 • MPV • Gray</span>
                  </div>
                </div>
              </td>
              <td>Anna Lopez</td>
              <td><strong>35</strong> bookings</td>
              <td><strong>₱70,000</strong></td>
              <td>
                <div class="progress-item">
                  <div class="progress">
                    <div class="progress-bar" style="width: 80%; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);"></div>
                  </div>
                </div>
                <small>80%</small>
              </td>
              <td><span style="color: #f57c00;">★ 4.7</span></td>
              <td><span class="status-badge verified">Active</span></td>
            </tr>
            <tr>
              <td>
                <div class="rank-badge rank-other">5</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Nissan+Navara&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Nissan Navara 2023</span>
                    <span class="user-email">MNO-7890 • Pickup • Blue</span>
                  </div>
                </div>
              </td>
              <td>Robert Tan</td>
              <td><strong>32</strong> bookings</td>
              <td><strong>₱80,000</strong></td>
              <td>
                <div class="progress-item">
                  <div class="progress">
                    <div class="progress-bar" style="width: 75%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                  </div>
                </div>
                <small>75%</small>
              </td>
              <td><span style="color: #f57c00;">★ 4.8</span></td>
              <td><span class="status-badge verified">Active</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Car Type Distribution & Revenue Chart -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
      <div class="report-card">
        <div class="report-card-header">
          <div class="report-card-title">
            <i class="bi bi-pie-chart"></i>
            Car Type Distribution
          </div>
        </div>
        <div class="chart-container">
          <canvas id="carTypeChart"></canvas>
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px;">
          <div class="metric-box">
            <div class="metric-value">45</div>
            <div class="metric-label">Sedans</div>
          </div>
          <div class="metric-box">
            <div class="metric-value">28</div>
            <div class="metric-label">SUVs</div>
          </div>
          <div class="metric-box">
            <div class="metric-value">18</div>
            <div class="metric-label">MPVs</div>
          </div>
          <div class="metric-box">
            <div class="metric-value">12</div>
            <div class="metric-label">Pickups</div>
          </div>
        </div>
      </div>

      <div class="report-card">
        <div class="report-card-header">
          <div class="report-card-title">
            <i class="bi bi-graph-up"></i>
            Monthly Revenue Trend
          </div>
        </div>
        <div class="chart-container">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Underperforming Cars -->
    <div class="report-card">
      <div class="report-card-header">
        <div class="report-card-title">
          <i class="bi bi-exclamation-triangle"></i>
          Underperforming Cars (Needs Attention)
        </div>
        <div class="report-actions">
          <button class="report-btn download">
            <i class="bi bi-download"></i>
            Download
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Car Details</th>
              <th>Owner</th>
              <th>Last Booking</th>
              <th>Total Bookings</th>
              <th>Revenue</th>
              <th>Issue</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Suzuki+Swift&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Suzuki Swift 2020</span>
                    <span class="user-email">PQR-1122 • Hatchback</span>
                  </div>
                </div>
              </td>
              <td>Lisa Ramos</td>
              <td>45 days ago</td>
              <td><strong>3</strong> bookings</td>
              <td><strong>₱6,000</strong></td>
              <td><span class="issue-badge warning">Low Demand</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn" style="background: #fff3e0; color: #ef6c00;" title="Suggest Price Adjustment">
                    <i class="bi bi-tag"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Ford+Ranger&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Ford Ranger 2019</span>
                    <span class="user-email">STU-3344 • Pickup</span>
                  </div>
                </div>
              </td>
              <td>David Cruz</td>
              <td>60+ days ago</td>
              <td><strong>2</strong> bookings</td>
              <td><strong>₱5,000</strong></td>
              <td><span class="issue-badge critical">Inactive</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Contact Owner">
                    <i class="bi bi-envelope"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Maintenance & Issues Report -->
    <div class="report-card">
      <div class="report-card-header">
        <div class="report-card-title">
          <i class="bi bi-tools"></i>
          Maintenance & Issues Tracker
        </div>
        <div class="report-actions">
          <button class="report-btn download">
            <i class="bi bi-download"></i>
            Download
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Car Details</th>
              <th>Owner</th>
              <th>Issue Type</th>
              <th>Reported Date</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Honda+Civic&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Honda Civic 2021</span>
                    <span class="user-email">VWX-5566 • Sedan</span>
                  </div>
                </div>
              </td>
              <td>Sofia Martin</td>
              <td>Insurance Expiring</td>
              <td>Dec 1, 2025</td>
              <td><span class="issue-badge critical">High</span></td>
              <td><span class="status-badge pending">Pending</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="action-btn suspend" title="Send Reminder">
                    <i class="bi bi-bell"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Mazda+3&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Mazda 3 2022</span>
                    <span class="user-email">YZA-7788 • Sedan</span>
                  </div>
                </div>
              </td>
              <td>Mark Johnson</td>
              <td>Damage Reported</td>
              <td>Nov 28, 2025</td>
              <td><span class="issue-badge warning">Medium</span></td>
              <td><span class="status-badge ongoing">In Progress</span></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn view" title="View Details">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=Hyundai+Accent&background=1a1a1a&color=fff" alt="Car">
                  </div>
                  <div class="user-info">
                    <span class="user-name">Hyundai Accent 2021</span>
                    <span class="user-email">BCD-9900 • Sedan</span>
                  </div>
                </div>
              </td>
              <td>Elena Torres</td>
              <td>Routine Maintenance</td>
              <td>Nov 25, 2025</td>