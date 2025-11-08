<?php
include "include/db.php";
session_start();

if($_SERVER['REQUEST_METHOD']==="POST"){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $row = $result->fetch_assoc();

        if($password == $row['password']){
            $_SESSION['admin_user'] = $row['username'];
            $_SESSION['admin_email'] = $row['email'];

            header('Location: dashboard.php');
        }else{
          $_SESSION['wrongPass'] = "Invalid Password";
        }
    }else{
      $_SESSION['wrongEmail'] = "Invalid Email";
    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarGo Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f1f1f1;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      font-family: 'Poppins', sans-serif;
    }
    .login-card {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
      padding: 30px;
    }
    .btn-custom {
      background-color: #4b4b4b;
      color: white;
      border: none;
    }
    .btn-custom:hover {
      background-color: #333;
    }
  </style>
</head>
<body>



  <div class="login-card">
    <?php
    if(isset($_SESSION['wrongPass'])) : ?>
      <div class="alert alert-danger text-center">
        <?= $_SESSION['wrongPass'] ; unset($_SESSION['wrongPass'])  ?>
      </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['wrongEmail'])) : ?>
      <div class="alert alert-danger text-center">
        <?= $_SESSION['wrongEmail']; unset($_SESSION['wrongEmail']) ?>
      </div>

    <?php endif; ?>
    <div class="text-center mb-4">
      <h4 class="fw-bold"><i class="bi bi-car-front me-2"></i>CarGo Admin</h4>
      <p class="text-muted">Sign in to manage the system</p>
    </div>

    <form action="" method="POST">
      <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
      </div>

      <button type="submit" class="btn btn-custom w-100 py-2">Login</button>
    </form>

    <div class="text-center mt-3">
      <small class="text-muted">© 2025 CarGo Admin</small>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
