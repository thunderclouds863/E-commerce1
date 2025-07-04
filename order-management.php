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

// Handle Order Confirmation
if (isset($_GET['confirm_order'])) {
    $order_id = $_GET['confirm_order'];

    // Update order status to 'Dikonfirmasi'
    $update_status = "UPDATE orders SET status = 'Dikonfirmasi' WHERE id = ?";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Decrease stock for each item in the order
    $order_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($order_items);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($item = $result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // Update product stock
        $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
    }

    header("Location: order-management.php");
    exit();
}

$sql = "SELECT orders.id, orders.created_at, orders.total_amount, orders.status, users.username
        FROM orders
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            padding: 20px;
            text-align: center;
            color: white;
        }
        .container {
            width: 80%;
            margin: 20px auto;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .order-table th, .order-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .order-table th {
            background-color: #ffccbc;
        }
        .order-table td .status {
            font-weight: bold;
        }
        .confirm-btn {
            background-color: #4caf50;
            color: white;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
        }
        .confirm-btn:hover {
            background-color: #388e3c;
        }
        footer {
            background-color: #ff7043;
            padding: 10px;
            color: white;
            text-align: center;
        }

    footer {
      background-color: #ff7043;
      color: white;
      text-align: center;
      padding: 10px;
      position: fixed;
      bottom: 0;
      width: 100%;
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
    <header>
        <h1 style="color:#ff7043;">Order Management</h1>
    </header>

    <div class="container">
        <h2>Order List</h2>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['username']}</td>
                                <td>{$row['created_at']}</td>
                                <td>Rp " . number_format($row['total_amount'], 0, ',', '.') . "</td>
                                <td><span class='status'>{$row['status']}</span></td>
                                <td>";
                        if ($row['status'] == 'Pending') {
                            echo "<a href='order-management.php?confirm_order={$row['id']}' class='confirm-btn'>Confirm</a>";
                        }
                        echo "</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2024 AS Berkah E-Commerce. All Rights Reserved.</p>
    </footer>

</body>
</html>

<?php
$conn->close();
?>
