<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_completed'])) {
    $order_id = $_POST['order_id'];
    // Mark the order as 'Selesai'
    $update_query = "UPDATE orders SET status = 'Selesai' WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $order_id);
    $update_stmt->execute();
    header("Location: order-history.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - AS Berkah E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f5f2;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            /* Full viewport height */
        }

        header {
            width: 100%;
            background-color: #ff8a65;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            font-size: 18px;
            box-sizing: border-box;
        }

        .navbar {
            text-align: right;
            display: flex;
            /* Make navbar a flex container */
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            border-radius: 5px;
            padding: 8px 12px;
        }

        .navbar a:hover {
            background-color: #ff5722;
        }


        .title a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            border-radius: 5px;
            padding: 8px 12px;
        }

        .title a:hover {
            background-color: #ff5722;
        }

        s .navbar {
            background-color: #ff7043;
            padding: 10px;
            text-align: center;
        }

        .navbar a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            margin: 0 15px;
            border-radius: 8px;
        }

        .navbar a:hover {
            background-color: #ff5722;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            flex: 1;
            /* This ensures the container expands to take up available space */
        }

        .content {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th,
        .order-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .order-table th {
            background-color: #f2f2f2;
        }

        .order-table td .status {
            font-weight: bold;
        }

        footer {
            background-color: #ff8a65;
            padding: 15px;
            text-align: center;
            color: white;
            font-size: 14px;
            margin-top: auto;
            /* Ensures the footer stays at the bottom */
        }

        .view-details-btn {
            background-color: #ff9f1c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
        }

        .view-details-btn:hover {
            background-color: #ff7043;
        }

        .view-details-btn {
            background-color: #ff9f1c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
        }

        .view-details-btn:hover {
            background-color: #ff7043;
        }
    </style>
</head>

<body>

<header>
        <div>Sistem Informasi Peminjaman Alat</div>
        <div class="navbar">
            <a href="customer_dashboard.php">Home</a>
            <a href="items.php">Items</a>
            <a href="peminjaman.php">Peminjaman</a>
            <a href="index.php?logout=true">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="content">
            <h2>Status Peminjaman</h2>

            <?php
            if ($orders_result->num_rows > 0) {
                echo "<table class='order-table'>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>";
                while ($order = $orders_result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$order['id']}</td>
                        <td>{$order['created_at']}</td>
                        <td>Rp " . number_format($order['total_amount'], 0, ',', '.') . "</td>
                        <td><span class='status'>{$order['status']}</span></td>
                        <td>";

                    // If the status is "Sampai Tujuan", allow to mark as "Selesai"
                    if ($order['status'] == 'Sampai Tujuan') {
                        echo "
                            <form method='POST'>
                                <input type='hidden' name='order_id' value='{$order['id']}'>
                                <button type='submit' name='mark_completed'>Selesai</button>
                            </form>
                        ";
                    } else {
                        echo "<a href='order-details.php?order_id={$order['id']}' class='view-details-btn'>View Details</a>";
                    }

                    echo "</td>
                    </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>No orders found.</p>";
            }
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Peminjaman Alat. All Rights Reserved.</p>
    </footer>

</body>

</html>

<?php
$conn->close();
?>