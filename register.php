<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check if username already exists
    $sql_check = "SELECT * FROM users WHERE username = '$username'";
    $result_check = $conn->query($sql_check);
    if ($result_check->num_rows > 0) {
        header("Location: register.php?message=Username already exists!&type=error");
        exit();
    }

    // Insert the new user into the database
    $sql = "INSERT INTO users (username, password, role, status) VALUES ('$username', '$password', '$role', 'pending')";
    if ($conn->query($sql) === TRUE) {
        header("Location: register.php?message=Registration successful! Please wait for admin approval.&type=success");
    } else {
        header("Location: register.php?message=Error: " . $conn->error . "&type=error");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — AS Berkah E-Commerce</title>
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
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join AS Berkah E-Commerce</p>
      </div>

      <?php if (isset($_GET['message'])): ?>
        <?php $msgType = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'error' : 'success'; ?>
        <div class="alert-banner alert-banner--<?php echo $msgType; ?>" role="alert">
          <span><?php echo htmlspecialchars($_GET['message']); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="role">Account Role</label>
          <select class="form-select" name="role" id="role" required>
            <option value="customer">Customer</option>
            <option value="employee">Employee</option>
            <option value="courier">Courier</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" type="text" id="username" name="username" placeholder="Choose a username" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-field">
            <input class="form-control" type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit" name="register" value="register">Create Account</button>
      </form>

      <div class="auth-footer">
        Already have an account? <a href="index.php">Sign in</a>
      </div>
    </div>
  </main>

  <script src="assets/js/ui.js"></script>
</body>

</html>

<?php $conn->close(); ?>
