<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'courier') {
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
            header("Location: courier_dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    }
}

// Mengambil pesanan dengan status 'Siap Dikirim' atau 'Dikirim'
$query = "SELECT * FROM orders WHERE status IN ('Siap Dikirim', 'Dikirim')";
$result = mysqli_query($conn, $query);


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
    <title>Courier Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffe0b2;
            /* Pastel orange background */
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #ffcc80;
            /* Pastel orange header */
            color: white;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }

        .logout-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background-color: red;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #f06292;
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
            background-color: #ffcc80;
            color: white;
        }

        button {
            padding: 5px 10px;
            background-color: #f48fb1;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #f06292;
        }

        .status-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        footer {
            background-color: #ffcc80;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Courir Dashboard</h2>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </div>

<div class="container">
    <h2>Orders to be Delivered</h2>

    <table class="order-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <?php if ($row['status'] == 'Siap Dikirim'): ?>
                            <form method="POST" class="status-buttons">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="new_status" value="Dikirim">
                                <button type="submit" name="update_status">Dikirim</button>
                            </form>
                        <?php elseif ($row['status'] == 'Dikirim'): ?>
                            <form method="POST" class="status-buttons">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="new_status" value="Sampai Tujuan">
                                <button type="submit" name="update_status">Sampai Tujuan</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<footer>
    <p>&copy; 2024 Courier Service. All rights reserved.</p>
</footer>

<script>
    // Add click event to logout button
    document.getElementById("logout-btn").addEventListener("click", function() {
        this.classList.add("clicked"); // Add the "clicked" class to change the button color
    });
</script>

</body>

</html>

<?php
$conn->close();
?>
