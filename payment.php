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

$has_pending = isset($_SESSION['order_id']) && isset($_SESSION['total_amount']);

if ($has_pending) {
    $order_id = $_SESSION['order_id'];
    $total_amount = $_SESSION['total_amount'];

    $random_code = rand(1000000000, 9999999999999);
    $payment_amount = $total_amount + $random_code;

    $sql = "UPDATE orders SET payment_code = ?, status = 'awaiting payment' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $random_code, $order_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — AS Berkah E-Commerce</title>
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
                    <span class="brand-sub">Payment</span>
                </span>
            </a>

            <nav class="nav-links" data-nav-links aria-label="Customer navigation">
                <a href="customer_dashboard.php">Catalog</a>
                <a href="order-history.php">Orders</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <?php if (!$has_pending): ?>
            <div class="surface-card empty-state receipt-card">
                <h3>No pending payment</h3>
                <p>Proceed to checkout from your cart to generate a payment code.</p>
                <a href="checkout.php" class="btn btn-primary">Go to Checkout</a>
            </div>
        <?php else: ?>
            <section class="surface-card receipt-card">
                <div class="surface-card-header">
                    <h1 class="surface-card-title">Complete Your Payment</h1>
                    <span class="badge-pill badge-warning">Awaiting Payment</span>
                </div>
                <div class="surface-card-body">
                    <div class="summary-line">
                        <span>Order ID</span>
                        <strong>#<?php echo intval($order_id); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span>Order Subtotal</span>
                        <span class="tabular-nums">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
                    </div>

                    <div class="payment-code-box">
                        <div>
                            <div class="meta-tile-label" style="margin-bottom:2px;">Unique Payment Code</div>
                            <div class="payment-code-value"><?php echo htmlspecialchars((string)$random_code); ?></div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" data-copy-text="<?php echo htmlspecialchars((string)$random_code); ?>">Copy</button>
                    </div>

                    <div class="summary-total" style="margin-bottom:24px;">
                        <span>Total to Pay</span>
                        <span class="summary-total-amount">Rp <?php echo number_format($payment_amount, 0, ',', '.'); ?></span>
                    </div>

                    <a href="confirm-payment.php?order_id=<?php echo intval($order_id); ?>" class="btn btn-primary btn-lg btn-block">I Have Completed Payment</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
            <span>Secure Payment</span>
        </div>
    </footer>

    <script src="assets/js/ui.js"></script>
</body>

</html>

<?php
$conn->close();
?>
