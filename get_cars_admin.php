<?php
include "include/db.php";
include "include/header.php";

$query = $conn->query("
    SELECT cars.*, users.fullname 
    FROM cars 
    JOIN users ON users.id = cars.owner_id 
    ORDER BY cars.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Car Listings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h2>Manage Car Listings</h2>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Owner</th>
            <th>Car</th>
            <th>Plate Number</th>
            <th>Status</th>
            <th>Documents</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $query->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['fullname'] ?></td>
            <td><?= $row['brand'] . " " . $row['model'] ?></td>
            <td><?= $row['plate_number'] ?></td>

            <td>
                <span class="badge 
                    <?= $row['status'] === 'approved' ? 'bg-success' : ($row['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </td>

            <td>
                <a href="<?= $row['official_receipt'] ?>" target="_blank" class="btn btn-sm btn-primary">OR</a>
                <a href="<?= $row['certificate_of_registration'] ?>" target="_blank" class="btn btn-sm btn-dark">CR</a>
            </td>

            <td>
                <form method="POST" action="update_car_status.php" style="display:inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <?php if($row['status'] !== 'approved') { ?>
                    <button name="status" value="approved" class="btn btn-success btn-sm">Approve</button>
                    <?php } ?>
                    
                    <button type="button" class="btn btn-danger btn-sm rejectBtn"
                        data-id="<?= $row['id'] ?>">Reject</button>

                    <button name="status" value="disabled" class="btn btn-secondary btn-sm">Disable</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="update_car_status.php">
        <!-- FIXED: changed car_id → id -->
        <input type="hidden" name="id" id="rejectCarId">

        <div class="modal-header">
            <h5 class="modal-title">Reject Car Listing</h5>
        </div>

        <div class="modal-body">
            <textarea name="remarks" class="form-control" placeholder="Reason for rejection"></textarea>
        </div>

        <div class="modal-footer">
            <button type="submit" name="status" value="rejected" class="btn btn-danger">Confirm Reject</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const rejectButtons = document.querySelectorAll(".rejectBtn");
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

    rejectButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            document.getElementById("rejectCarId").value = btn.dataset.id;
            rejectModal.show();
        });
    });
</script>

</body>
</html>
