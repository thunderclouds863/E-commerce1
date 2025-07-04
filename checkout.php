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

$cart_items = $_SESSION['cart'] ?? []; // Pastikan cart selalu terdefinisi
$total_amount = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];

    if (empty($cart_items)) {
        echo "<p>Your cart is empty. Please add items to your cart before checkout.</p>";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Insert order into orders table
    $sql = "INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, 0, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $payment_method);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;

        foreach ($cart_items as $product_id => $item) {
            $sql = "SELECT * FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $price = $product['price'];
                $quantity = $item['quantity'];
                $total_amount += $price * $quantity;

                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmt->execute();
            }
        }

        $sql = "UPDATE orders SET total_amount = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $total_amount, $order_id);
        $stmt->execute();

        $_SESSION['order_id'] = $order_id;
        $_SESSION['total_amount'] = $total_amount;

        $_SESSION['cart'] = [];
        header("Location: order-history.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff7f0;
            margin: 0;
            padding-bottom: 100px;
        }

        header {
            background-color: #ff8a65;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            padding: 8px 12px;
            border-radius: 5px;
        }

        .navbar a:hover {
            background-color: #ff5722;
        }

        .checkout-container {
            padding: 30px;
            background-color: white;
            max-width: 800px;
            margin: 30px auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .checkout-form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .checkout-form button {
            background-color: #ff7043;
            color: white;
            padding: 15px;
            border-radius: 5px;
            border: none;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
        }

        footer {
            background-color: #ff8a65;
            padding: 15px;
            text-align: center;
            color: white;
            position: fixed;
            bottom: 0;
            width: 100%;
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

    <div class="checkout-container">
        <h2>Detail Peminjaman</h2>
        <?php
        if (!empty($cart_items)) {
            foreach ($cart_items as $product_id => $item) {
                $sql = "SELECT * FROM products WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    $total_amount += $product['price'] * $item['quantity'];
                    echo "<div class='checkout-item'>
                            <h3>{$product['name']}</h3>
                            <p>Rp " . number_format($product['price'], 0, ',', '.') . " x {$item['quantity']}</p>
                          </div>";
                }
            }
        } else {
            echo "<p>Your cart is empty.</p>";
        }
        ?>
        <div class="total-price">Total Price: Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></div>

        <h2>Payment Method</h2>
        <form action="checkout.php" method="POST" class="checkout-form">
            <select name="payment_method" required>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="credit_card">Credit Card</option>
                <option value="cash_on_delivery">Cash on Delivery</option>
            </select>
            <button type="submit">Pay</button>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Peminjaman Alat. All Rights Reserved.</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>
