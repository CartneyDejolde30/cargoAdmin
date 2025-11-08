

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarGo Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
     display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .dashboard-box {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 30px 20px;
      text-align: center;
      transition: all 0.2s ease-in-out;
      border-top: 5px solid #6c757d;
    }
    .dashboard-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
      cursor: pointer;
    }
    .dashboard-icon {
      font-size: 3rem;
      color: #4b4b4b;
      margin-bottom: 10px;
    }
    .dashboard-title {
      font-size: 1.1rem;
      color: #6c757d;
    }
    .dashboard-value {
      font-size: 1.6rem;
      font-weight: bold;
      color: #212529;
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php
include "include/header.php";
?>

<div class="container-fluid mt-5">
  <h3 class="fw-bold mb-4">Dashboard Overview</h3>
  <div class="row g-4">

    <div class="col-md-4 col-sm-6">
      <div class="dashboard-box">
        <div class="dashboard-icon"><i class="bi bi-cash-stack"></i></div>
        <div class="dashboard-value">₱12,500.00</div>
        <div class="dashboard-title pb-1">Total Earnings</div>
        <br>
        <br>
      </div>
    </div>

    <div class="col-md-4 col-sm-6">
      <div class="dashboard-box">
        <div class="dashboard-icon"><i class="bi bi-calendar-check"></i></div>
        <div class="dashboard-value">45</div>
        <div class="dashboard-title pb-1">Total Bookings</div>
         <br>
         <br>
      </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="dashboard-box">
            <div class="dashboard-icon"><i class="bi bi-people"></i></div>
          <h5>Total Users</h5>
          <h5>58</h5>
          <p class="text-muted small pb-3">Owners: 20 | Renters: 38</p>
        </div>
      </div>

    <div class="row mt-3 g-4">
        <div class="col-md-4 col-sm-6">
            <div class="dashboard-box">
                <div class="dashboard-icon"><i class="bi bi-flag"></i></div>
                <div class="dashboard-value">3</div>
                <div class="dashboard-title pb-1">Reported Issues</div>
                <br>
                <br>
            </div>
        </div>

        <div class="col-md-4 mb-5 col-sm-6">
            <div class="dashboard-box">
                <div class="dashboard-icon"><i class="bi bi-flag"></i></div>
                <div class="dashboard-value">3</div>
                <div class="dashboard-title pb-1">Pending Verification</div>
                <br>
                <br>
            </div>
        </div>
    </div>

  </div>
</div>


</body>
</html>
