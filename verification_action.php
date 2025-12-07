<?php
include "include/db.php";

$id = $_POST['id'];

if (isset($_POST['approve'])) {
  $conn->query("UPDATE user_verifications SET status='approved', verified_at=NOW() WHERE id=$id");
}

if (isset($_POST['reject'])) {
  $conn->query("UPDATE user_verifications SET status='rejected' WHERE id=$id");
}

header("Location: admin_verification.php?msg=updated");
exit;
