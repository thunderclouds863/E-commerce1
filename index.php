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
        // Use password_verify to check if the entered password matches the hashed password
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
  <title>AS Berkah E-Commerce</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fef5e7;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      width: 100%;
      max-width: 400px;
      padding: 20px;
      background: #fff5e5;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border-radius: 15px;
      text-align: center;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    h2 {
      color: #ff8a65;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    input,
    select,
    button {
      margin: 10px 0;
      padding: 12px;
      font-size: 16px;
      border: 1px solid #ffccbc;
      border-radius: 8px;
      background-color: #fff;
    }

    button {
      background-color: #ff8a65;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #ff7043;
    }

    .link {
      margin-top: 10px;
      font-size: 14px;
    }

    .link a {
      color: #ff8a65;
      text-decoration: none;
      font-weight: bold;
    }

    .link a:hover {
      text-decoration: underline;
    }

    .error-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background-color: #ff5252;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.5s ease-out, fadeOut 4s ease-in forwards;
    }

    @keyframes slideIn {
      from {
        transform: translateX(-50%) translateY(-20px);
        opacity: 0;
      }

      to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
      }
    }

    @keyframes fadeOut {
      90% {
        opacity: 1;
      }

      100% {
        opacity: 0;
      }
    }
  </style>
</head>

<body>
  <?php if (!empty($error_message)): ?>
    <div class="error-popup"><?php echo $error_message; ?></div>
  <?php endif; ?>

  <div class="container">
    <h2>Welcome to AS Berkah E-Commerce</h2>
    <form method="POST">
      <select name="role" id="role" required>
        <option value="" disabled selected>-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="customer">Customer</option>
        <option value="employee">Employee</option>
        <option value="courier">Courier</option>
      </select>
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="submit" value="login">Login</button>
    </form>
    <div class="link" id="link-section">
      <p>Don't have an account? <a href="register.php">Register Here</a></p>
    </div>
  </div>

  <script>
    const roleSelect = document.getElementById('role');
    const linkSection = document.getElementById('link-section');

    roleSelect.addEventListener('change', function () {
      if (this.value === 'admin') {
        linkSection.style.display = 'none';
      } else {
        linkSection.style.display = 'block';
      }
    });
  </script>
</body>

</html>

<?php $conn->close(); ?>