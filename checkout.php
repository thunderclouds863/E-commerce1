<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();
$cart_items = $_SESSION['cart'] ?? [];
$checkout_error = '';
$shipping_address = trim($_POST['address'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $checkout_error = "Session expired. Please refresh and try again.";
    } elseif (empty($cart_items)) {
        $checkout_error = "Your cart is empty. Please add items before checkout.";
    } elseif (strlen($shipping_address) < 5) {
        $checkout_error = "Please enter a complete shipping address (at least 5 characters).";
    } else {
        $payment_method = $_POST['payment_method'] ?? '';
        if (!in_array($payment_method, ['bank_transfer', 'credit_card', 'cash_on_delivery'], true)) {
            $checkout_error = "Please select a valid payment method.";
        } else {
            $user_id = intval($_SESSION['user_id']);
            $initial_status = $payment_method === 'cash_on_delivery' ? 'Pending' : 'awaiting payment';
            $payment_code = $payment_method === 'cash_on_delivery' ? null : random_int(100, 999);

            $conn->begin_transaction();
            try {
                $total_amount = 0;
                $validated_lines = [];

                foreach ($cart_items as $product_id => $item) {
                    $pid = intval($product_id);
                    $qty = is_array($item) && isset($item['quantity']) ? intval($item['quantity']) : 0;
                    if ($pid <= 0 || $qty <= 0) {
                        continue;
                    }

                    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE");
                    $stmt->bind_param("i", $pid);
                    $stmt->execute();
                    $prod = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$prod) {
                        throw new RuntimeException("One of the products in your cart is no longer available.");
                    }
                    if (intval($prod['stock']) < $qty) {
                        throw new RuntimeException('Insufficient stock for "' . $prod['name'] . '" (only ' . intval($prod['stock']) . ' left).');
                    }

                    $line_total = floatval($prod['price']) * $qty;
                    $total_amount += $line_total;
                    $validated_lines[] = [
                        'product_id' => $pid,
                        'quantity' => $qty,
                        'price' => floatval($prod['price']),
                    ];
                }

                if (empty($validated_lines)) {
                    throw new RuntimeException("No valid items in your cart.");
                }

                $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, address, payment_code, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_order->bind_param("idsiss", $user_id, $total_amount, $shipping_address, $payment_code, $payment_method, $initial_status);
                if (!$stmt_order->execute()) {
                    throw new RuntimeException("Failed to create order.");
                }
                $order_id = $stmt_order->insert_id;
                $stmt_order->close();

                $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

                foreach ($validated_lines as $line) {
                    $stmt_item->bind_param("iiid", $order_id, $line['product_id'], $line['quantity'], $line['price']);
                    if (!$stmt_item->execute()) {
                        throw new RuntimeException("Failed to save order item.");
                    }
                    $stmt_stock->bind_param("iii", $line['quantity'], $line['product_id'], $line['quantity']);
                    $stmt_stock->execute();
                    if ($stmt_stock->affected_rows !== 1) {
                        throw new RuntimeException("Stock changed while placing order. Please try again.");
                    }
                }
                $stmt_item->close();
                $stmt_stock->close();

                $conn->commit();

                $_SESSION['order_id'] = $order_id;
                $_SESSION['total_amount'] = $total_amount;
                $_SESSION['cart'] = [];

                if ($payment_method === 'cash_on_delivery') {
                    set_flash('success', 'Order #' . $order_id . ' placed with Cash on Delivery!');
                    header("Location: order-history.php");
                } else {
                    header("Location: payment.php?order_id=" . $order_id);
                }
                exit();
            } catch (Throwable $ex) {
                $conn->rollback();
                $checkout_error = $ex->getMessage();
            }
        }
    }
}

$display_items = [];
$total_amount = 0;
$total_units = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $product_id => $item) {
        $pid = intval($product_id);
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pid);
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
                <a href="account.php">Account</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Final Step</div>
                <h1 class="page-title">Checkout</h1>
                <p class="page-subtitle">Enter your delivery address and select how you would like to pay.</p>
            </div>
            <a href="shopping-cart.php" class="btn btn-secondary">Back to Cart</a>
        </div>

        <?php if ($checkout_error !== ''): ?>
            <div class="alert-banner alert-banner--error" role="alert">
                <span><?php echo e($checkout_error); ?></span>
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
                        <h2 class="surface-card-title">Shipping &amp; Payment</h2>
                    </div>
                    <div class="surface-card-body">
                        <form action="checkout.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="form-group">
                                <label class="form-label" for="address">Shipping Address</label>
                                <textarea class="form-textarea" id="address" name="address" required minlength="5" placeholder="Street address, district, city, postal code..."><?php echo e($shipping_address); ?></textarea>
                            </div>

                            <label class="form-label">Payment Method</label>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked required>
                                    <span>
                                        <span class="payment-option-title">Bank Transfer</span>
                                        <span class="payment-option-desc">Transfer with a unique 3-digit verification code.</span>
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="credit_card" required>
                                    <span>
                                        <span class="payment-option-title">Credit Card</span>
                                        <span class="payment-option-desc">Complete payment via verified reference code.</span>
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
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order &middot; <?php echo format_idr($total_amount); ?></button>
                        </form>
                    </div>
                </section>

                <aside class="summary-card">
                    <h2 class="summary-title">Order Summary (<?php echo $total_units; ?>)</h2>
                    <?php foreach ($display_items as $item): ?>
                        <div class="summary-line">
                            <span><?php echo e($item['name']); ?> &times; <?php echo $item['quantity']; ?></span>
                            <span class="tabular-nums"><?php echo format_idr($item['line_total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="summary-total-amount"><?php echo format_idr($total_amount); ?></span>
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
