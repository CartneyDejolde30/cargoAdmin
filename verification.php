<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification - CarGo Admin</title>
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

    .verification-table {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 20px;
    }

    .table th {
      background-color: #6c757d;
      color: #fff;
    }

    .action-btns button {
      margin-right: 5px;
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

<div class="container flex-grow-1 mt-5">
  <h3 class="fw-bold mb-4">User Verification</h3>

  <div class="verification-table table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>User Name</th>
          <th>Email</th>
          <th>Document</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        
        <tr>
          <td>1</td>
          <td>Juan Dela Cruz</td>
          <td>juan@email.com</td>
          <td><a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal1">View</a></td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td class="action-btns">
            <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Approve</button>
            <button class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
          </td>
        </tr>

       
        <tr>
          <td>2</td>
          <td>Maria Santos</td>
          <td>maria@email.com</td>
          <td><a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal1">View</a></td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td class="action-btns">
            <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Approve</button>
            <button class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
          </td>
        </tr>

        
      </tbody>
    </table>

   
<div class="modal fade" id="userModal1" tabindex="-1" aria-labelledby="userModalLabel1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="userModalLabel1">User Details - Juan Dela Cruz</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <strong>Name:</strong> Juan Dela Cruz
          </div>
          <div class="col-md-6">
            <strong>Email:</strong> juan@email.com
          </div>
          <div class="col-md-6">
            <strong>Role:</strong> Owner
          </div>
          <div class="col-md-6">
            <strong>Status:</strong> <span class="badge bg-warning text-dark">Pending</span>
          </div>
          <div class="col-12 mt-3">
            <strong>Documents Submitted:</strong>
            <ul>
              <li>ID: <a href="#" class="btn btn-sm btn-outline-primary">View</a></li>
              <li>Driver's License: <a href="#" class="btn btn-sm btn-outline-primary">View</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
        <button class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

  </div>
</div>

<?php include "include/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
