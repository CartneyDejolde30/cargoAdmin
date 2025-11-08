<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f1f1f1;
      font-family: 'Poppins', sans-serif;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .table-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-top: 30px;
    }

    .table th {
      background-color: #6c757d;
      color: #fff;
    }

    footer {
      background-color: #6c757d;
      color: #fff;
      padding: 15px 0;
      text-align: center;
      width: 100%;
      margin-top: auto;
    }

    footer a {
      color: #fff;
      transition: color 0.2s ease;
    }

    footer a:hover {
      color: #dcdcdc;
    }
  </style>
</head>
<body>

<?php include "include/header.php"; ?>

<div class="container flex-grow-1">
  <h3 class="fw-bold mt-5 mb-4">Bookings Overview</h3>

  <div class="table-container table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Renter</th>
          <th>Owner</th>
          <th>Car Type</th>
          <th>Pickup Date</th>
          <th>Drop-off Date</th>
          <th>Pickup Location</th>
          <th>Drop-off Location</th>
          <th>Price</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Example row -->
        <tr>
          <td>1</td>
          <td>Juan Dela Cruz</td>
          <td>Pedro Santos</td>
          <td>Toyota Vios</td>
          <td>2025-11-20</td>
          <td>2025-11-25</td>
          <td>San Francisco</td>
          <td>Butuan City</td>
          <td>₱5,000.00</td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td><span class="badge bg-danger">Not Paid</span></td>
          <td>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bookingModal1">
              <i class="bi bi-eye"></i> View
            </button>
          </td>
        </tr>
        <!-- Add more rows as needed -->
      </tbody>
    </table>
  </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal1" tabindex="-1" aria-labelledby="bookingModalLabel1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="bookingModalLabel1">Booking Details - #1</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><strong>Renter:</strong> Juan Dela Cruz</div>
          <div class="col-md-6"><strong>Owner:</strong> Pedro Santos</div>
          <div class="col-md-6"><strong>Car Type:</strong> Toyota Vios</div>
          <div class="col-md-6"><strong>Price:</strong> ₱5,000.00</div>
          <div class="col-md-6"><strong>Pickup Date:</strong> 2025-11-20</div>
          <div class="col-md-6"><strong>Drop-off Date:</strong> 2025-11-25</div>
          <div class="col-md-6"><strong>Pickup Location:</strong> San Francisco</div>
          <div class="col-md-6"><strong>Drop-off Location:</strong> Butuan City</div>
          <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-warning text-dark">Pending</span></div>
          <div class="col-md-6"><strong>Payment Status:</strong> <span class="badge bg-danger">Not Paid</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include "include/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
