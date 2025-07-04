<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
  header("Location: index.php");
  exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle POST Requests for approving, rejecting, and deleting users
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    // Ensure that the action is valid to prevent potential issues
    $valid_actions = ['approve_user', 'reject_user', 'delete_user'];
    if (in_array($action, $valid_actions)) {
      switch ($action) {
        case 'approve_user':
          $sql = "UPDATE users SET status = ? WHERE id = ?";
          $stmt = $conn->prepare($sql);
          $status = 'actived';
          $stmt->bind_param("si", $status, $user_id);
          $stmt->execute();
          break;

        case 'reject_user':
          $sql = "UPDATE users SET status = ? WHERE id = ?";
          $stmt = $conn->prepare($sql);
          $status = 'rejected';
          $stmt->bind_param("si", $status, $user_id);
          $stmt->execute();
          break;

        case 'delete_user':
          $sql = "DELETE FROM users WHERE id = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          break;
      }
    } else {
      // Invalid action
      echo "Invalid action.";
    }
  }
}

// Fetch users with different statuses and roles
$pending_users = $conn->query("SELECT * FROM users WHERE status = 'pending'");
$customers = $conn->query("SELECT * FROM users WHERE role = 'customer' AND status = 'actived'");
$employees = $conn->query("SELECT * FROM users WHERE role = 'employee' AND status = 'actived'");
$couriers = $conn->query("SELECT * FROM users WHERE role = 'courier' AND status = 'actived'");

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fef5e7;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      /* Ensures the body takes up full height */
    }

    .container {
      padding: 20px;
      flex: 1;
      /* Ensures container takes up available space */
    }

    h1,
    h2 {
      text-align: center;
      color: #ff8a65;
    }

    button {
      background-color: #4caf50;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #388e3c;
    }

    nav {
      background-color: #ff7043;
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    nav ul {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
    }

    nav ul li {
      margin: 0 15px;
    }

    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav div a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav ul li a:hover {
      background-color: #ff8a65;
    }

    nav div a:hover {
      background-color: #ff8a65;
    }

    section {
      display: none;
      padding: 20px;
      background-color: #fff5e5;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
      border: 1px solid red;
    }

    .content {
      flex-grow: 1;
      /* Allow content to grow and take remaining space */
      margin-top: 30px;
      padding: 20px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      box-sizing: border-box;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    table th,
    table td {
      padding: 12px;
      border: 1px solid #ffccbc;
      text-align: center;
    }

    table th {
      background-color: #ffe0b2;
      color: #d84315;
    }

    footer {
      background-color: #ff7043;
      color: white;
      text-align: center;
      padding: 15px;
      position: relative;
      /* Changed to relative to avoid sticky issues */
      margin-top: auto;
      /* Ensures footer stays at the bottom */
      width: 98.2%;
    }
    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav div a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav ul li a:hover {
      background-color: #ff8a65;
    }

    nav div a:hover {
      background-color: #ff8a65;
    }F
  </style>
</head>

<body>
  <nav>
    <div><a href="admin_dashboard.php">Admin Dashboard</a></div>
    <ul>
      <li><a href="admin_dashboard.php">Home</a></li>
      <li><a href="manajemen_user.php">User Management</a></li>
      <li><a href="inventory_management.php">Inventory</a></li>
      <li><a href="order_management.php">Orders</a></li>
      <li><a href="report.php">Report</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <main>
    <div class="content">
      <h2>User Management</h2>

      <!-- Pending Users -->
      <h3>Pending Users</h3>
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Action</th>
        </tr>
        <?php while ($user = $pending_users->fetch_assoc()): ?>
          <tr>
            <td><?= $user['id'] ?></td>
            <td><?= $user['username'] ?></td>
            <td><?= $user['role'] ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <button type="submit" name="action" value="approve_user">Approve</button>
              </form>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <button type="submit" name="action" value="reject_user">Reject</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>

      <!-- Actived Users: Customers, Employees, Couriers -->
      <h3>Actived Users</h3>

      <h4>Customers</h4>
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
        </tr>
        <?php while ($customer = $customers->fetch_assoc()): ?>
          <tr>
            <td><?= $customer['id'] ?></td>
            <td><?= $customer['username'] ?></td>
          </tr>
        <?php endwhile; ?>
      </table>

      <h4>Employees</h4>
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
        </tr>
        <?php while ($employee = $employees->fetch_assoc()): ?>
          <tr>
            <td><?= $employee['id'] ?></td>
            <td><?= $employee['username'] ?></td>
          </tr>
        <?php endwhile; ?>
      </table>

      <h4>Couriers</h4>
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
        </tr>
        <?php while ($courier = $couriers->fetch_assoc()): ?>
          <tr>
            <td><?= $courier['id'] ?></td>
            <td><?= $courier['username'] ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </main>

  <footer>
    <p>&copy; 2024 Burjo Restaurant. All Rights Reserved.</p>
  </footer>

  <script>

  </script>
</body>

</html>