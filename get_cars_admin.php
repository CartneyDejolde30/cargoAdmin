<?php
include "include/db.php";

// Get filter values
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Pagination settings
$limit = 5; // rows per page
$page = $_GET['page'] ?? 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

// Base query
$sql = "
    SELECT cars.*, users.fullname 
    FROM cars 
    JOIN users ON users.id = cars.owner_id 
    WHERE 1
";

// Search filter
if (!empty($search)) {
    $sql .= " AND (cars.brand LIKE '%$search%' 
                OR cars.model LIKE '%$search%' 
                OR cars.plate_number LIKE '%$search%' 
                OR users.fullname LIKE '%$search%')";
}

// Status filter
if ($status !== "all") {
    $sql .= " AND cars.status = '$status'";
}

// Count total rows for pagination (before applying LIMIT)
$countQuery = $conn->query($sql);
$totalRows = $countQuery->num_rows;
$totalPages = ceil($totalRows / $limit);

// Final query with pagination
$sql .= " ORDER BY cars.created_at DESC LIMIT $limit OFFSET $offset";
$query = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Car Approval Management</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
    body {
        background: #f4f4f4;
        font-family: Arial, sans-serif;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        background: #111;
        z-index: 100;
    }

    .main-content {
        margin-left: 260px;
        padding: 30px;
    }

    .table-section {
      background: #fff;
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-top: 20px;
    }

    table {
      width: 100%;
      font-size: 14px;
    }

    thead th {
      background: #f9f9f9;
      padding: 14px;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
      border-bottom: 2px solid #eee;
    }

    tbody td {
      padding: 16px;
      border-bottom: 1px solid #eee;
    }

    tbody tr:hover {
      background: #f8f8f8;
    }

    .car-thumb {
      width: 60px;
      height: 40px;
      border-radius: 8px;
      object-fit: cover;
    }

    .badge-approved { background:#D1FFD1; color:#096B00; }
    .badge-rejected { background:#FFD1D1; color:#900000; }
    .badge-pending { background:#FFF4CC; color:#775200; }

</style>
</head>
<body>

<!-- Sidebar -->
<?php include "include/sidebar.php"; ?>

<!-- Content -->
<div class="main-content">

    <h3 class="fw-bold mb-4">Car Approval Management</h3>

    <!-- 🔍 Search + Filter -->
    <form method="GET" class="d-flex gap-3 mb-4">

        
        <!-- Status Filter -->
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>

        <button type="submit" class="btn btn-dark px-4">Filter</button>
    </form>

    <!-- Table -->
    <div class="table-section">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Owner</th>
                    <th>Car Type</th>
                    <th>Plate Number</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th>Documents</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                $num = 1;
                if ($query->num_rows === 0) {
                    echo "<tr><td colspan='8' class='text-center text-muted py-4'>No results found</td></tr>";
                }

                while($row = $query->fetch_assoc()) { ?>
                <tr>
                    <td><?= $num++ ?></td>
                    <td><?= $row['fullname'] ?></td>
                    <td><?= $row['brand'] . " " . $row['model'] ?></td>
                    <td><?= $row['plate_number'] ?></td>

                    <td>
                        <span class="badge <?= 'badge-' . $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                    </td>

                    <td>
                        <?php if(!empty($row['image'])) { ?>
                            <img src="<?= $row['image'] ?>" class="car-thumb">
                        <?php } else { ?>
                            <span class="text-muted">No Image</span>
                        <?php } ?>
                    </td>

                    <td>
                        <a href="<?= $row['official_receipt'] ?>" class="btn btn-dark btn-sm" target="_blank">OR</a>
                        <a href="<?= $row['certificate_of_registration'] ?>" class="btn btn-secondary btn-sm" target="_blank">CR</a>
                    </td>

                    <td>
                        <form method="POST" action="update_car_status.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <?php if($row['status'] !== 'approved') { ?>
                                <button name="status" value="approved" class="btn btn-success btn-sm">Approve</button>
                            <?php } ?>

                           <button type="button" 
        class="btn btn-danger btn-sm rejectBtn" 
        data-id="<?= $row['id'] ?>">
    Reject
</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
<nav>
<ul class="pagination justify-content-center mt-3">

    <!-- Previous Button -->
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= $search ?>&status=<?= $status ?>">Previous</a>
    </li>

    <!-- Page Numbers -->
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>&status=<?= $status ?>">
                <?= $i ?>
            </a>
        </li>
    <?php endfor; ?>

    <!-- Next Button -->
    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= $search ?>&status=<?= $status ?>">Next</a>
    </li>

</ul>
</nav>
<?php endif; ?>

    </div>

</div>


<!-- Reject Modal --> <div class="modal fade" id="rejectModal" tabindex="-1"> <div class="modal-dialog"> <form class="modal-content p-3" method="POST" action="update_car_status.php" style="border-radius:10px;"> <input type="hidden" name="id" id="rejectCarId"> <h5 class="fw-bold mb-2">Reject Car Listing</h5> <p class="text-muted mb-3">Provide a reason so the owner understands.</p> <textarea class="form-control mb-3" name="remarks" placeholder="Reason for rejection..." style="height:120px;"></textarea> <button type="submit" name="status" value="rejected" class="btn btn-dark w-100">Confirm Rejection</button> </form> </div> </div>

<script> const rejectButtons = document.querySelectorAll(".rejectBtn"); const modal = new bootstrap.Modal(document.getElementById('rejectModal')); rejectButtons.forEach(btn => { btn.onclick = () => { document.getElementById("rejectCarId").value = btn.dataset.id; modal.show(); }; }); function viewCarImage(src){ let preview = document.createElement("img"); preview.src = src; preview.style.width = "100%"; alert("Image Viewer Coming Next Upgrade 😊"); } </script>
</body>
</html>
