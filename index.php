<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);
session_start();

// Error message variable
$error_message = '';

if (isset($_POST['submit'])) {
  $role = $_POST['role'];
  $username = $_POST['username'];
  $password = $_POST['password'];

  if ($role == 'admin') {
    if ($username == 'admin' && $password == 'admin123') {
      $_SESSION['user_id'] = 1;
      $_SESSION['role'] = 'admin';
      header("Location: admin_dashboard.php");
    } else {
      $error_message = "Invalid admin credentials.";
    }
  }

  if ($role == 'customer' || $role == 'employee' || $role == 'courier') {
    if (isset($_POST['register'])) {
      $sql_check = "SELECT * FROM users WHERE username = '$username'";
      $result_check = $conn->query($sql_check);

      if ($result_check->num_rows > 0) {
        $error_message = "Username already taken.";
      } else {
        $sql_insert = "INSERT INTO users (username, password, role, status) VALUES ('$username', '$password', '$role', 'pending')";
        if ($conn->query($sql_insert) === TRUE) {
          echo "<script>alert('Registration successful, waiting for admin approval.');</script>";
        } else {
          $error_message = "Error: " . $conn->error;
        }
      }
    } else {
      $sql = "SELECT * FROM users WHERE username = '$username' AND role = '$role' AND status = 'actived'";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['role'] = $user['role'];
          header("Location: " . $role . "_dashboard.php");
        } else {
          $error_message = "Invalid credentials or account not approved yet.";
        }
      } else {
        $error_message = "Invalid credentials or account not approved yet.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — AS Berkah E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">
  <main class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <h1 class="auth-title">AS Berkah</h1>
        <p class="auth-subtitle">Sign in to your account</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="alert-banner alert-banner--error" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label" for="role">Role</label>
          <select class="form-select" name="role" id="role" required>
            <option value="" disabled selected>Select your role</option>
            <option value="admin">Admin</option>
            <option value="customer">Customer</option>
            <option value="employee">Employee</option>
            <option value="courier">Courier</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-field">
            <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit" name="submit" value="login">Sign In</button>
      </form>

      <div class="auth-footer" id="link-section">
        Don't have an account? <a href="register.php">Create one</a>
      </div>
    </div>
  </main>

  <script src="assets/js/ui.js"></script>
  <script>
    const roleSelect = document.getElementById('role');
    const linkSection = document.getElementById('link-section');

    roleSelect.addEventListener('change', function () {
      linkSection.style.display = this.value === 'admin' ? 'none' : 'block';
    });
  </script>
</body>

</html>

<?php $conn->close(); ?>
