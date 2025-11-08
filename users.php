

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Management | CarGo Admin</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f0f0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background-color: #f1f1f1;
      font-family: 'Poppins', sans-serif;
    }
     
    .navbar-brand {
      font-weight: bold;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .table thead {
      background-color: #343a40;
      color: white;
    }
    .badge-owner {
      background-color: #0d6efd;
    }
    .badge-renter {
      background-color: #198754;
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100> 

  <?php
include 'include/header.php';
?>

  <div class="container-fluid mt-5 flex-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>👥 User Management</h3>
      <div class="d-flex gap-2">
        <input type="text" class="form-control" placeholder="Search users...">
        <select class="form-select" style="width: 150px;">
          <option>All</option>
          <option>Owners</option>
          <option>Renters</option>
        </select>
        <button class="btn btn-primary">Search</button>
      </div>
    </div>

    <div class="card p-3">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Juan Dela Cruz</td>
              <td>juan@example.com</td>
              <td><span class="badge badge-owner">Owner</span></td>
              <td><span class="badge bg-success">Verified</span></td>
              <td>
                <button class="btn btn-sm btn-warning">Suspend</button>
                <button class="btn btn-sm btn-danger">Delete</button>
                <button class="btn btn-sm btn-success pe-3 ps-3">Edit</button>
              </td>
            </tr>
            <tr>
              <td>2</td>
              <td>Maria Santos</td>
              <td>maria@example.com</td>
              <td><span class="badge badge-renter">Renter</span></td>
              <td><span class="badge bg-danger">Unverified</span></td>
              <td>
                <button class="btn btn-sm btn-success">Activate</button>
                <button class="btn btn-sm btn-danger">Delete</button>
                <button class="btn btn-sm btn-success pe-3 ps-3">Edit</button>
              </td>
            </tr>
            <tr>
              <td>3</td>
              <td>Carlos Reyes</td>
              <td>carlos@example.com</td>
              <td><span class="badge badge-owner">Owner</span></td>
              <td><span class="badge bg-success">Verified</span></td>
              <td>
                <button class="btn btn-sm btn-warning">Suspend</button>
                <button class="btn btn-sm btn-danger">Delete</button>
                <button class="btn btn-sm btn-success pe-3 ps-3">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>


  

</body>
</html>
