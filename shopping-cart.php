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

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize the cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_GET['add_to_cart'])) {
    $product_id = $_GET['add_to_cart'];
    if ($product_id > 0) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = ['quantity' => 0];
        }
        $_SESSION['cart'][$product_id]['quantity']++;
    }
}

// Handle remove from cart
if (isset($_GET['remove_from_cart'])) {
    $product_id = $_GET['remove_from_cart'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Handle quantity change
if (isset($_POST['update_quantity'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if (isset($_SESSION['cart'][$product_id])) {
                $quantity = intval($quantity);
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                }
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
    <title>Shopping Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* Ensures the body takes up full height */
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

        .content {
            flex-grow: 1;
            /* Ensures content takes up remaining space */
            margin-top: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }

        .cart-item-info {
            flex-grow: 1;
            padding-left: 15px;
        }

        .cart-item-info h3 {
            font-size: 18px;
            margin: 0;
        }

        .cart-item-info .price {
            font-size: 16px;
            color: #ff7043;
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
        }

        .cart-item-actions input {
            width: 50px;
            margin-right: 10px;
        }

        .cart-item-actions a {
            background-color: #ff7043;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
        }

        .cart-item-actions a:hover {
            background-color: #ff5722;
        }

        .checkout-button {
            display: inline-block;
            background-color: #ff7043;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            margin-top: 20px;
        }

        .checkout-button:hover {
            background-color: #ff5722;
        }

        footer {
            background-color: #ff8a65;
            padding: 15px;
            text-align: center;
            color: white;
            font-size: 14px;
            position: fixed;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
            margin-top: auto;
            /* Ensures footer stays at the bottom */
        }
    </style>
</head>

<body>

    <header>
        <div class="title">
            <a href="customer_dashboard.php"> Peminjam Alat Dashboard </a>
        </div>
        <div class="navbar">
            <a href="customer_dashboard.php">Home</a>
            <a href="shopping-cart.php">Items</a>
            <a href="checkout.php">Peminjaman</a>
            <a href="index.php?logout=true" class="logout">Logout</a>
        </div>
    </header>

    <div class="content">
        <h2>Cart Peminjaman</h2>

        <form method="POST">
            <?php
            if (empty($_SESSION['cart'])) {
                echo "<p>Your cart is empty.</p>";
            } else {
                foreach ($_SESSION['cart'] as $product_id => $item) {
                    if ($product_id <= 0) {
                        unset($_SESSION['cart'][$product_id]);
                        continue;
                    }

                    if (is_array($item) && isset($item['quantity'])) {
                        $sql = "SELECT * FROM products WHERE id = $product_id";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            $product = $result->fetch_assoc();
                            $total_price = $product['price'] * $item['quantity'];
                            ?>

                            <div class="cart-item">
                                <!-- <?php
                                $product_image_name = 'produk' . $product['id'];
                                $image_extension = pathinfo($product['image'], PATHINFO_EXTENSION);
                                $image_path = 'uploads/' . $product_image_name . '.' . $image_extension;

                                if (file_exists($image_path)) {
                                    $image_src = $image_path;
                                } else {
                                    $image_src = 'uploads/default_' . $product_image_name . '.' . $image_extension;
                                }
                                ?>

                                <img src="<?php echo $image_src; ?>" alt="<?php echo $product['name']; ?>"> -->

                                <div class="cart-item-info">
                                    <h3><?php echo $product['name']; ?></h3>
                                    <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?> x
                                        <?php echo $item['quantity']; ?>
                                    </p>
                                </div>

                                <div class="cart-item-actions">
                                    <form action="shopping-cart.php" method="POST" class="update-quantity-form">
                                        <input type="number" name="quantity[<?php echo $product_id; ?>]"
                                            value="<?php echo $item['quantity']; ?>" min="1" max="10" oninput="this.form.submit()">
                                        <a href="shopping-cart.php?remove_from_cart=<?php echo $product_id; ?>" class="remove-button"
                                            onclick="return confirm('Are you sure you want to remove this item?');">Remove</a>
                                    </form>
                                </div>
                            </div>

                            <?php
                        } else {
                            echo "<p>Product not found for ID: $product_id</p>";
                        }
                    } else {
                        echo "<p>Error: Cart item is not structured properly for product ID: $product_id</p>";
                    }
                }
            }
            ?>
            <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
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