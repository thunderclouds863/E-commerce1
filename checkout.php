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

$cart_items = $_SESSION['cart'] ?? [];
$total_amount = 0;
$checkout_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];

    if (empty($cart_items)) {
        $checkout_error = "Your cart is empty. Please add items to your cart before checkout.";
    } else {
        $user_id = $_SESSION['user_id'];

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
}

$display_items = [];
$total_amount = 0;
$total_units = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $product_id => $item) {
        $sql = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $qty = intval($item['quantity']);
            $line = $product['price'] * $qty;
            $total_amount += $line;
            $total_units += $qty;
            $display_items[] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $qty,
                'line_total' => $line,
            ];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — AS Berkah E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="customer_dashboard.php" class="brand-link">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </span>
                <span class="brand-text">
                    <span class="brand-name">AS Berkah</span>
                    <span class="brand-sub">Checkout</span>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <nav class="nav-links" data-nav-links aria-label="Customer navigation">
                <a href="customer_dashboard.php">Catalog</a>
                <a href="shopping-cart.php">
                    Cart
                    <?php if ($total_units > 0): ?>
                        <span class="nav-badge"><?php echo $total_units; ?></span>
                    <?php endif; ?>
                </a>
                <a href="order-history.php">Orders</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Final Step</div>
                <h1 class="page-title">Checkout</h1>
                <p class="page-subtitle">Choose your preferred payment method to place your order.</p>
            </div>
            <a href="shopping-cart.php" class="btn btn-secondary">Back to Cart</a>
        </div>

        <?php if (!empty($checkout_error)): ?>
            <div class="alert-banner alert-banner--error" role="alert">
                <span><?php echo htmlspecialchars($checkout_error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($display_items)): ?>
            <div class="surface-card empty-state">
                <h3>No items ready for checkout</h3>
                <p>Add items to your cart first before completing checkout.</p>
                <a href="customer_dashboard.php" class="btn btn-primary">Browse Catalog</a>
            </div>
        <?php else: ?>
            <div class="split-layout">
                <section class="surface-card">
                    <div class="surface-card-header">
                        <h2 class="surface-card-title">Payment Method</h2>
                    </div>
                    <div class="surface-card-body">
                        <form action="checkout.php" method="POST">
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked required>
                                    <span>
                                        <span class="payment-option-title">Bank Transfer</span>
                                        <span class="payment-option-desc">Transfer directly from your bank account.</span>
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="credit_card" required>
                                    <span>
                                        <span class="payment-option-title">Credit Card</span>
                                        <span class="payment-option-desc">Pay securely using Visa or Mastercard.</span>
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="cash_on_delivery" required>
                                    <span>
                                        <span class="payment-option-title">Cash on Delivery</span>
                                        <span class="payment-option-desc">Pay in cash when your courier delivers the package.</span>
                                    </span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order &middot; Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></button>
                        </form>
                    </div>
                </section>

                <aside class="summary-card">
                    <h2 class="summary-title">Order Summary (<?php echo $total_units; ?>)</h2>
                    <?php foreach ($display_items as $item): ?>
                        <div class="summary-line">
                            <span><?php echo htmlspecialchars($item['name']); ?> &times; <?php echo $item['quantity']; ?></span>
                            <span class="tabular-nums">Rp <?php echo number_format($item['line_total'], 0, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="summary-total-amount">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
            <span>Secure Checkout</span>
        </div>
    </footer>

    <script src="assets/js/ui.js"></script>
</body>

</html>

<?php
$conn->close();
?>
