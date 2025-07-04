<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'employee') {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Mengambil semua pesanan yang statusnya 'Dikonfirmasi' atau 'Dikemas'
$query = "SELECT * FROM orders WHERE status IN ('Dikonfirmasi', 'Dikemas')";
$result = mysqli_query($conn, $query);

// Proses perubahan status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    // Pastikan 'order_id' dan 'new_status' ada
    if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];

        // Update status pesanan
        $update_query = "UPDATE orders SET status='$new_status' WHERE id=$order_id";
        if (mysqli_query($conn, $update_query)) {
            // Redirect setelah update
            header("Location: employee_dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e8f5e9;
            /* Light pastel green background */
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #81c784;
            /* Pastel green header */
            color: white;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }

        .logout-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background-color: #ff8a80;
            /* Light coral-red for logout button */
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #ff5252;
            /* Slightly darker red on hover */
        }

        .container {
            width: 80%;
            margin: auto;
            background-color: #ffffff;
            /* White background for content */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background-color: #66bb6a;
            /* Pastel green for table headers */
            color: white;
        }

        button {
            padding: 5px 10px;
            background-color: #81c784;
            /* Matching pastel green for buttons */
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #66bb6a;
            /* Slightly darker pastel green on hover */
        }

        .status-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        footer {
            background-color: #81c784;
            /* Pastel green footer */
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Employee Dashboard</h2>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h1>Employee Order Confirmation</h1>

        <h2>Orders to be Processed</h2>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <?php if ($row['status'] == 'Dikonfirmasi'): ?>
                            <form method="POST" class="status-buttons">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="new_status" value="Dikemas">
                                <button type="submit" name="update_status">Dikemas</button>
                            </form>
                        <?php elseif ($row['status'] == 'Dikemas'): ?>
                            <form method="POST" class="status-buttons">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="new_status" value="Siap Dikirim">
                                <button type="submit" name="update_status">Siap Dikirim</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>

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
