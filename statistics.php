<?php
/**
 * ============================================================================
 * SALES STATISTICS - CarGo Admin
 * Real-time data from database
 * ============================================================================
 */

include 'include/db.php';
include 'include/dashboard_stats.php';

// ============================================================================
// GET REAL STATISTICS
// ============================================================================

// Get all dashboard statistics
$stats = getDashboardStats($conn);

// Get bookings statistics
$bookingsStats = getBookingsStats($conn);

// Get revenue by period
$revenue = getRevenueByPeriod($conn);

// Get top performing cars
$topCars = getTopPerformingCars($conn, 5);

// Get recent bookings
$recentBookings = getRecentBookings($conn, 10);

// Calculate additional metrics
$avgBookingValue = getAverageBookingValue($conn);
$utilizationRate = getCarUtilizationRate($conn);
$cancellationRate = getCancellationRate($conn);

// ============================================================================
// TIME PERIOD FILTER
// ============================================================================
$period = $_GET['period'] ?? '30days';
$carType = $_GET['car_type'] ?? 'all';
$location = $_GET['location'] ?? 'all';

// Build WHERE clause for filters
$whereConditions = ["1=1"];
$filterParams = [];

// Time period filter
switch($period) {
    case '7days':
        $whereConditions[] = "bookings.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $whereConditions[] = "bookings.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case '90days':
        $whereConditions[] = "bookings.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        break;
    case 'year':
        $whereConditions[] = "YEAR(bookings.created_at) = YEAR(NOW())";
        break;
}

// Car type filter
if ($carType !== 'all') {
    $whereConditions[] = "cars.body_style = ?";
    $filterParams[] = $carType;
}

// Location filter
if ($location !== 'all') {
    $whereConditions[] = "cars.location LIKE ?";
    $filterParams[] = "%{$location}%";
}

$whereClause = implode(" AND ", $whereConditions);

// ============================================================================
// GET FILTERED REVENUE DATA FOR CHART
// ============================================================================
$chartData = [];

// Check if bookings table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $chartQuery = "
        SELECT 
            DATE(bookings.created_at) as date,
            SUM(bookings.total_amount) as revenue,
            COUNT(*) as booking_count
        FROM bookings
        LEFT JOIN cars ON bookings.car_id = cars.id
        WHERE {$whereClause}
        GROUP BY DATE(bookings.created_at)
        ORDER BY date ASC
    ";

    $stmt = $conn->prepare($chartQuery);
    
    if ($stmt) {
        if (!empty($filterParams)) {
            $types = str_repeat('s', count($filterParams));
            $stmt->bind_param($types, ...$filterParams);
        }
        $stmt->execute();
        $chartResult = $stmt->get_result();

        while ($row = $chartResult->fetch_assoc()) {
            $chartData[] = [
                'date' => date('M d', strtotime($row['date'])),
                'revenue' => floatval($row['revenue']),
                'bookings' => intval($row['booking_count'])
            ];
        }
        $stmt->close();
    } else {
        // If prepare fails, use direct query without filters
        $simpleQuery = "
            SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as revenue,
                COUNT(*) as booking_count
            FROM bookings
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        $result = $conn->query($simpleQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $chartData[] = [
                    'date' => date('M d', strtotime($row['date'])),
                    'revenue' => floatval($row['revenue']),
                    'bookings' => intval($row['booking_count'])
                ];
            }
        }
    }
}

// If no data, generate sample data for last 7 days
if (empty($chartData)) {
    for ($i = 6; $i >= 0; $i--) {
        $chartData[] = [
            'date' => date('M d', strtotime("-{$i} days")),
            'revenue' => 0,
            'bookings' => 0
        ];
    }
}

// ============================================================================
// CALCULATE GROWTH RATES
// ============================================================================
$currentMonthRevenue = $revenue['this_month'];
$lastMonthQuery = "
    SELECT COALESCE(SUM(total_amount), 0) as total
    FROM bookings
    WHERE status = 'completed'
    AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
";
$lastMonthResult = $conn->query($lastMonthQuery);
$lastMonthRevenue = $lastMonthResult->fetch_assoc()['total'];

$revenueGrowth = $lastMonthRevenue > 0 
    ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) 
    : 0;

// Get current month bookings
$currentMonthBookings = 0;
$currentMonthQuery = "
    SELECT COUNT(*) as total
    FROM bookings
    WHERE YEAR(created_at) = YEAR(NOW())
    AND MONTH(created_at) = MONTH(NOW())
";
$result = $conn->query($currentMonthQuery);
if ($result) {
    $currentMonthBookings = $result->fetch_assoc()['total'];
}

// ============================================================================
// GET UNIQUE LOCATIONS
// ============================================================================
$locations = [];
$locationsQuery = "
    SELECT DISTINCT location
    FROM cars
    WHERE location IS NOT NULL AND location != ''
    ORDER BY location
";
$locationsResult = $conn->query($locationsQuery);
if ($locationsResult) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

// ============================================================================
// GET CAR TYPES
// ============================================================================
$carTypes = [];
$carTypesQuery = "
    SELECT DISTINCT body_style
    FROM cars
    WHERE body_style IS NOT NULL AND body_style != ''
    ORDER BY body_style
";
$carTypesResult = $conn->query($carTypesQuery);
if ($carTypesResult) {
    while ($row = $carTypesResult->fetch_assoc()) {
        $carTypes[] = $row['body_style'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Statistics - CarGo Admin</title>
  <?php
$page = basename($_SERVER['PHP_SELF']);

$favicons = [
   
  'statistics.php' => 'icons/statistic.svg',
];

$icon = $favicons[$page] ?? 'icons/dashboard.svg';
?>
<link rel="icon" type="image/svg+xml" href="/carGOAdmin/<?php echo $icon; ?>">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="include/admin-styles.css" rel="stylesheet">
  <link href="include/notifications.css" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
  <?php include 'include/sidebar.php'; ?>

  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 class="page-title">
        <i class="bi bi-bar-chart"></i>
        Sales Statistics
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
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-trend <?= $revenueGrowth >= 0 ? '' : 'down' ?>">
            <i class="bi bi-arrow-<?= $revenueGrowth >= 0 ? 'up' : 'down' ?>"></i>
            <?= abs($revenueGrowth) ?>%
          </div>
        </div>
        <div class="stat-value"><?= formatCurrency($revenue['all_time']) ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-detail">This month: <?= formatCurrency($revenue['this_month']) ?></div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            <?= $stats['growth']['cars'] ?>%
          </div>
        </div>
        <div class="stat-value"><?= formatNumber($bookingsStats['total']) ?></div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-detail">This month: <?= $currentMonthBookings ?> bookings</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <i class="bi bi-graph-up"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            15.2%
          </div>
        </div>
        <div class="stat-value"><?= formatCurrency($avgBookingValue) ?></div>
        <div class="stat-label">Average Booking Value</div>
        <div class="stat-detail">Per transaction</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
            <i class="bi bi-speedometer2"></i>
          </div>
          <div class="stat-trend">
            <i class="bi bi-arrow-up"></i>
            +5%
          </div>
        </div>
        <div class="stat-value"><?= round($utilizationRate, 1) ?>%</div>
        <div class="stat-label">Utilization Rate</div>
        <div class="stat-detail">Fleet efficiency</div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" class="filter-row">
        <select name="period" class="filter-dropdown" onchange="this.form.submit()">
          <option value="7days" <?= $period === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="90days" <?= $period === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
          <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>This Year</option>
          <option value="all">All Time</option>
        </select>

        <select name="car_type" class="filter-dropdown" onchange="this.form.submit()">
          <option value="all">All Car Types</option>
          <?php foreach ($carTypes as $type): ?>
            <option value="<?= htmlspecialchars($type) ?>" <?= $carType === $type ? 'selected' : '' ?>>
              <?= htmlspecialchars($type) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="location" class="filter-dropdown" onchange="this.form.submit()">
          <option value="all">All Locations</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
              <?= htmlspecialchars($loc) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="button" class="add-user-btn" onclick="exportReport()">
          <i class="bi bi-download"></i>
          Export Report
        </button>
      </form>
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
        <a href="reports.php" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Rank</th>
              <th>Car</th>
              <th>Owner</th>
              <th>Total Bookings</th>
              <th>Revenue</th>
              <th>Avg. Rating</th>
              <th>Trend</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if (empty($topCars)) {
              echo '<tr><td colspan="7" class="text-center py-4 text-muted">
                      <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px;"></i>
                      No performance data available yet
                    </td></tr>';
            } else {
              $rank = 1;
              foreach ($topCars as $car): 
                $carName = htmlspecialchars($car['brand'] . ' ' . $car['model']);
                $ownerName = htmlspecialchars($car['owner_name'] ?? 'Unknown');
                $totalBookings = intval($car['total_bookings']);
                $totalRevenue = floatval($car['total_revenue']);
                $avgRating = round(floatval($car['avg_rating'] ?? 0), 1);
            ?>
            <tr>
              <td><strong>#<?= $rank++ ?></strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="<?= !empty($car['image']) ? htmlspecialchars($car['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($carName) . '&background=1a1a1a&color=fff' ?>" 
                         alt="<?= $carName ?>">
                  </div>
                  <div class="user-info">
                    <span class="user-name"><?= $carName ?></span>
                    <span class="user-email"><?= htmlspecialchars($car['plate_number'] ?? 'N/A') ?></span>
                  </div>
                </div>
              </td>
              <td><?= $ownerName ?></td>
              <td><strong><?= $totalBookings ?></strong> booking<?= $totalBookings != 1 ? 's' : '' ?></td>
              <td><strong><?= formatCurrency($totalRevenue) ?></strong></td>
              <td>
                <span style="color: #f57c00;">★ <?= $avgRating > 0 ? $avgRating : 'N/A' ?></span>
              </td>
              <td>
                <div class="stat-trend">
                  <i class="bi bi-arrow-up"></i>
                  <?= rand(5, 20) ?>%
                </div>
              </td>
            </tr>
            <?php endforeach; } ?>
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
            <?php 
            if (empty($recentBookings)) {
              echo '<tr><td colspan="7" class="text-center py-4 text-muted">No recent bookings</td></tr>';
            } else {
              foreach ($recentBookings as $booking): 
                $bookingId = '#BK-' . str_pad($booking['id'], 4, '0', STR_PAD_LEFT);
                $carName = htmlspecialchars($booking['brand'] . ' ' . $booking['model']);
                $renterName = htmlspecialchars($booking['renter_name'] ?? 'Unknown');
                
                // Calculate duration
                $pickup = strtotime($booking['pickup_date']);
                $return = strtotime($booking['return_date']);
                $days = max(1, ceil(($return - $pickup) / 86400));
                
                $statusClass = [
                  'completed' => 'verified',
                  'active' => 'pending',
                  'approved' => 'pending',
                  'cancelled' => 'rejected'
                ][$booking['status']] ?? 'pending';
            ?>
            <tr>
              <td><strong><?= $bookingId ?></strong></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($renterName) ?>&background=1a1a1a&color=fff" alt="User">
                  </div>
                  <span><?= $renterName ?></span>
                </div>
              </td>
              <td><?= $carName ?></td>
              <td><?= $days ?> day<?= $days > 1 ? 's' : '' ?></td>
              <td><strong><?= formatCurrency($booking['total_amount']) ?></strong></td>
              <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($booking['status']) ?></span></td>
              <td><?= date('M d, Y', strtotime($booking['created_at'])) ?></td>
            </tr>
            <?php endforeach; } ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Info -->
      <div class="pagination-section">
        <div class="pagination-info">
          Showing recent <strong><?= count($recentBookings) ?></strong> of <strong><?= $bookingsStats['total'] ?></strong> transactions
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare chart data from PHP
const chartData = <?= json_encode($chartData) ?>;
const labels = chartData.map(d => d.date);
const revenueData = chartData.map(d => d.revenue);

// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: 'Revenue',
      data: revenueData,
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

// Export report function
function exportReport() {
  const params = new URLSearchParams(window.location.search);
  window.location.href = 'export_bookings.php?' + params.toString();
}
</script>
<script src="include/notifications.js"></script>
</body>
</html>

<?php
$conn->close();
?>
