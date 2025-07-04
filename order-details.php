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

// Cek koneksi database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order_id = $_GET['order_id']; // Mendapatkan ID pesanan dari URL

// Mengambil detail pesanan berdasarkan order_id
$order_sql = "SELECT o.id, o.total_amount, o.status, o.created_at, o.address, o.payment_method
              FROM orders o
              WHERE o.id = ?";
$stmt_order = $conn->prepare($order_sql);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();

// Mengambil data order_items dengan informasi produk
$order_items_sql = "SELECT oi.*, p.name AS product_name, p.price AS product_price
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($order_items_sql);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$order_items_result = $stmt_items->get_result();

if ($order_result->num_rows > 0) {
    $order = $order_result->fetch_assoc();
} else {
    echo "Order not found.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - AS Berkah E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f5f2;
            /* Light pastel background */
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


        .container {
            width: 80%;
            max-width: 1200px;
            margin: 20px auto;
            flex: 1;
            /* Ensure container takes up available space */
        }

        .content {
            margin-top: 30px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .cart-table th {
            background-color: #ff7043;
            color: white;
        }

        .cart-table td {
            text-align: right;
        }

        .cart-summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff4e6;
            border-radius: 8px;
        }

        .total-price {
            font-size: 18px;
            font-weight: bold;
        }

        footer {
            background-color: #ff8a65;
            padding: 15px;
            text-align: center;
            color: white;
            font-size: 14px;
            width: 98.2%;
            margin-top: auto;
            /* Ensures footer stays at the bottom */
        }

        @media (max-width: 768px) {
            .navbar a {
                padding: 8px 15px;
                /* Smaller padding for mobile screens */
            }

            .container {
                width: 95%;
                /* Reduced container width on smaller screens */
            }
        }
    </style>
</head>

<body>

<header>
        <div class="title">
            <a href="customer_dashboard.php"> Customer Dashboard - Order Details </a>
        </div>
        <div class="navbar">
            <a href="customer_dashboard.php">Home</a>
            <a href="shopping-cart.php">Shopping Cart</a>
            <a href="checkout.php">Checkout</a>
            <a href="order-history.php">Transaction History</a>
            <a href="index.php?logout=true" class="logout">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="content">
            <h2>Order ID: <?php echo $order['id']; ?></h2>
            <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
            <p><strong>Total Amount:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
            <p><strong>Shipping Address:</strong> <?php echo $order['address']; ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
            <p><strong>Order Date:</strong> <?php echo $order['created_at']; ?></p>

            <h3>Order Items</h3>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_order_amount = 0;
                    while ($item = $order_items_result->fetch_assoc()) {
                        $total_product_price = $item['quantity'] * $item['product_price'];
                        $total_order_amount += $total_product_price;
                        ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['product_price'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($total_product_price, 0, ',', '.'); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <p class="total-price">Total Order Amount: Rp
                    <?php echo number_format($total_order_amount, 0, ',', '.'); ?>
                </p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 AS Berkah E-Commerce. All Rights Reserved.</p>
    </footer>

</body>

</html>

<?php
$conn->close();
?>