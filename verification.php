<?php 
include "include/header.php"; 
include "include/db.php";

// Fetch verification requests
$sql = "SELECT * FROM user_verifications ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verification - CarGo Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { background-color: #f1f1f1; font-family: 'Poppins', sans-serif; }
    .verification-table { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
  </style>
</head>
<body>

<div class="container mt-5">
  <h3 class="fw-bold mb-4">User Verification Requests</h3>

  <div class="verification-table table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Email</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>

        <?php 
        if ($result->num_rows > 0) {
          $count = 1;
          while ($row = $result->fetch_assoc()) { 

            $statusClass = match($row["status"]) {
              "approved" => "success",
              "rejected" => "danger",
              default => "warning"
            };
        ?>

        <tr>
          <td><?= $count++ ?></td>
          <td><?= $row['first_name'] . " " . $row['last_name'] ?></td>
          <td><?= $row['email'] ?></td>
          <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></td>
          <td><?= date("M d, Y", strtotime($row["created_at"])) ?></td>
          <td>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal<?= $row['id'] ?>">
              View Details
            </button>
          </td>
        </tr>

        <!-- ================= Modal: Review User ================= -->
        <div class="modal fade" id="modal<?= $row['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Verification Details</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">

                <div class="row mb-3">
                  <div class="col-md-6"><strong>Name:</strong> <?= $row['first_name']." ".$row['last_name'] ?></div>
                  <div class="col-md-6"><strong>Email:</strong> <?= $row['email'] ?></div>
                  <div class="col-md-6 mt-2"><strong>Mobile:</strong> <?= $row['mobile_number'] ?></div>
                  <div class="col-md-6 mt-2"><strong>Status:</strong> <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></div>
                </div>

                <?php if($row["status"] == "rejected" && !empty($row["review_notes"])) { ?>
                  <div class="alert alert-danger p-2">
                    <strong>Rejection Reason:</strong><br><?= nl2br($row["review_notes"]) ?>
                  </div>
                <?php } ?>

                <hr>

                <h6 class="fw-bold mb-2">Submitted Documents</h6>
                <div class="row text-center g-3">

                  <div class="col-md-4">
                    <p class="fw-semibold">ID (Front)</p>
                    <img src="../<?= $row['id_front_photo'] ?>" class="img-fluid rounded border">
                  </div>

                  <div class="col-md-4">
                    <p class="fw-semibold">ID (Back)</p>
                    <img src="../<?= $row['id_back_photo'] ?>" class="img-fluid rounded border">
                  </div>

                  <div class="col-md-4">
                    <p class="fw-semibold">Selfie Verification</p>
                    <img src="../<?= $row['selfie_photo'] ?>" class="img-fluid rounded border">
                  </div>

                </div>

              </div>

              <div class="modal-footer">

                <?php if ($row['status'] == "pending") { ?>

                  <!-- Approve Form -->
                  <form method="POST" action="verification_action.php" class="d-inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button name="approve" class="btn btn-success">
                      <i class="bi bi-check-circle"></i> Approve
                    </button>
                  </form>

                  <!-- Reject Button -->
                  <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $row['id'] ?>">
                    <i class="bi bi-x-circle"></i> Reject
                  </button>
                <?php } ?>

                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>

            </div>
          </div>
        </div>


        <!-- ================= Reject Reason Modal ================= -->
        <div class="modal fade" id="rejectModal<?= $row['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Verification</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <form method="POST" action="verification_action.php">
                <div class="modal-body">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">

                  <label class="form-label"><strong>Reason for Rejection</strong></label>
                  <textarea name="reason" required rows="4" class="form-control"
                    placeholder="Example: Images are unclear, mismatched identity, missing information..."></textarea>
                </div>

                <div class="modal-footer">
                  <button type="submit" name="reject" class="btn btn-danger"><i class="bi bi-send"></i> Submit</button>
                  <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>


        <?php }} else { ?>
          <tr><td colspan="6" class="text-center text-muted">No verification requests found.</td></tr>
        <?php } ?>

      </tbody>
    </table>
  </div>
</div>

<?php include "include/footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
