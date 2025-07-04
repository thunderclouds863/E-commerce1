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
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hashing password
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
  <title>Register - AS Berkah E-Commerce</title>
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

    .popup-message {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background-color: #4caf50;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.5s ease-out, fadeOut 4s ease-in forwards;
    }

    .popup-message.error {
      background-color: #f44336;
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

  <!-- PHP Simulated Message -->
  <?php if (isset($_GET['message'])): ?>
    <div class="popup-message <?php echo htmlspecialchars($_GET['type']); ?>">
      <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
  <?php endif; ?>

  <div class="container">
    <h2>Register</h2>
    <form method="POST" action="">
      <select name="role" id="role" required>
        <option value="customer">Customer</option>
        <option value="employee">Employee</option>
        <option value="courier">Courier</option>
      </select>

      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>

      <button type="submit" name="register" value="register">Register</button>
    </form>

    <div class="link">
      <p>Already have an account? <a href="index.php">Login Here</a></p>
    </div>
  </div>

  <script>
    const roleSelect = document.getElementById('role');

    roleSelect.addEventListener('change', function () {
      document.body.style.backgroundColor = this.value === 'customer' ? '#e3f2fd' :
                                             this.value === 'employee' ? '#e8f5e9' :
                                             '#fff3e0';
    });
  </script>

</body>

</html>

<?php $conn->close(); ?>
