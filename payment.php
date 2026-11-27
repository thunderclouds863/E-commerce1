<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();
$user_id = intval($_SESSION['user_id']);
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : intval($_SESSION['order_id'] ?? 0);

$order = null;
if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$has_pending = $order && in_array(strtolower($order['status']), ['awaiting payment', 'pending'], true);

if ($has_pending) {
    $total_amount = floatval($order['total_amount']);
    $unique_code = intval($order['payment_code']);
    if ($unique_code <= 0 || $unique_code > 999) {
        $unique_code = random_int(100, 999);
        $upd = $conn->prepare("UPDATE orders SET payment_code = ?, status = 'awaiting payment' WHERE id = ? AND user_id = ?");
        $upd->bind_param("iii", $unique_code, $order_id, $user_id);
        $upd->execute();
        $upd->close();
    }
    $payment_amount = $total_amount + $unique_code;
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

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <nav class="nav-links" data-nav-links aria-label="Customer navigation">
                <a href="customer_dashboard.php">Catalog</a>
                <a href="order-history.php">Orders</a>
                <a href="account.php">Account</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <?php if (!$has_pending): ?>
            <div class="surface-card empty-state receipt-card">
                <h3>No pending payment</h3>
                <p>This order has already been paid or cannot be found.</p>
                <a href="order-history.php" class="btn btn-primary">View Your Orders</a>
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
                        <span class="tabular-nums"><?php echo format_idr($total_amount); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Unique Transfer Code</span>
                        <span class="tabular-nums">+ <?php echo format_idr($unique_code); ?></span>
                    </div>

                    <div class="payment-code-box">
                        <div>
                            <div class="meta-tile-label" style="margin-bottom:2px;">Verification Code</div>
                            <div class="payment-code-value"><?php echo e((string)$unique_code); ?></div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" data-copy-text="<?php echo e((string)$unique_code); ?>">Copy</button>
                    </div>

                    <div class="summary-total" style="margin-bottom:24px;">
                        <span>Exact Total to Pay</span>
                        <span class="summary-total-amount"><?php echo format_idr($payment_amount); ?></span>
                    </div>

                    <form method="POST" action="confirm-payment.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo intval($order_id); ?>">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">I Have Completed Payment</button>
                    </form>
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
