<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();
$user_id = intval($_SESSION['user_id']);
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order_sql = "SELECT o.id, o.total_amount, o.status, o.created_at, o.address, o.payment_method
              FROM orders o
              WHERE o.id = ? AND o.user_id = ? LIMIT 1";
$stmt_order = $conn->prepare($order_sql);
$stmt_order->bind_param("ii", $order_id, $user_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
$order = $order_result && $order_result->num_rows > 0 ? $order_result->fetch_assoc() : null;
$stmt_order->close();

$order_items = [];
if ($order) {
    $order_items_sql = "SELECT oi.*, p.name AS product_name, p.price AS product_price
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?";
    $stmt_items = $conn->prepare($order_items_sql);
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();
    while ($row = $res_items->fetch_assoc()) {
        $order_items[] = $row;
    }
    $stmt_items->close();
}

$steps = [
    ['key' => 'pending', 'label' => 'Placed'],
    ['key' => 'dikonfirmasi', 'label' => 'Confirmed'],
    ['key' => 'dikemas', 'label' => 'Packed'],
    ['key' => 'dikirim', 'label' => 'In Transit'],
    ['key' => 'selesai', 'label' => 'Completed'],
];

$statusMap = [
    'pending' => 0,
    'awaiting payment' => 0,
    'paid' => 0,
    'dikonfirmasi' => 1,
    'dikemas' => 2,
    'siap dikirim' => 2,
    'dikirim' => 3,
    'sampai tujuan' => 3,
    'selesai' => 4,
];

$currentStepIndex = $order ? ($statusMap[strtolower(trim($order['status']))] ?? 0) : 0;
$paymentLabels = [
    'bank_transfer' => 'Bank Transfer',
    'credit_card' => 'Credit Card',
    'cash_on_delivery' => 'Cash on Delivery',
];
$paymentLabel = $order ? ($paymentLabels[$order['payment_method']] ?? ucfirst(str_replace('_', ' ', $order['payment_method']))) : '';
$cart_count = cart_total_units();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $order ? 'Order #' . intval($order['id']) : 'Order Not Found'; ?> — AS Berkah E-Commerce</title>
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
                    <span class="brand-sub">Storefront</span>
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
                <a href="shopping-cart.php">Cart <?php if ($cart_count > 0): ?><span class="nav-badge"><?php echo $cart_count; ?></span><?php endif; ?></a>
                <a href="order-history.php" class="active">Orders</a>
                <a href="account.php">Account</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main page-main--narrow">
        <?php if (!$order): ?>
            <div class="surface-card empty-state">
                <h3>Order not found</h3>
                <p>This order does not exist or does not belong to your account.</p>
                <a href="order-history.php" class="btn btn-primary">Back to Order History</a>
            </div>
        <?php else: ?>
            <div class="page-header">
                <div>
                    <div class="page-eyebrow">Order Receipt</div>
                    <h1 class="page-title">Order #<?php echo intval($order['id']); ?></h1>
                    <p class="page-subtitle">Placed on <?php echo date("d M Y, H:i", strtotime($order['created_at'])); ?></p>
                </div>
                <div class="action-group">
                    <?php if (strtolower($order['status']) === 'awaiting payment'): ?>
                        <a href="payment.php?order_id=<?php echo intval($order['id']); ?>" class="btn btn-primary">Complete Payment</a>
                    <?php endif; ?>
                    <a href="order-history.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>

            <div class="order-stepper" aria-label="Order progress">
                <?php foreach ($steps as $i => $step):
                    $cls = $i < $currentStepIndex ? 'is-done' : ($i === $currentStepIndex ? 'is-current' : '');
                ?>
                    <div class="step-item <?php echo $cls; ?>">
                        <span class="step-dot"><?php echo $i < $currentStepIndex ? '&#10003;' : ($i + 1); ?></span>
                        <span class="step-label"><?php echo $step['label']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-meta-grid">
                <div class="meta-tile">
                    <div class="meta-tile-label">Status</div>
                    <div class="meta-tile-value"><?php echo e(ucfirst($order['status'])); ?></div>
                </div>
                <div class="meta-tile">
                    <div class="meta-tile-label">Payment</div>
                    <div class="meta-tile-value"><?php echo e($paymentLabel); ?></div>
                </div>
                <div class="meta-tile">
                    <div class="meta-tile-label">Shipping Address</div>
                    <div class="meta-tile-value"><?php echo e($order['address'] ?: 'Not specified'); ?></div>
                </div>
                <div class="meta-tile">
                    <div class="meta-tile-label">Total</div>
                    <div class="meta-tile-value tabular-nums"><?php echo format_idr($order['total_amount']); ?></div>
                </div>
            </div>

            <section class="surface-card">
                <div class="surface-card-header">
                    <h2 class="surface-card-title">Ordered Items</h2>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_order_amount = 0;
                            foreach ($order_items as $item):
                                $unit_price = floatval($item['price'] ?? $item['product_price']);
                                $total_product_price = intval($item['quantity']) * $unit_price;
                                $total_order_amount += $total_product_price;
                            ?>
                                <tr>
                                    <td><strong><?php echo e($item['product_name']); ?></strong></td>
                                    <td class="text-center tabular-nums"><?php echo intval($item['quantity']); ?></td>
                                    <td class="text-right tabular-nums"><?php echo format_idr($unit_price); ?></td>
                                    <td class="text-right tabular-nums"><strong><?php echo format_idr($total_product_price); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="surface-card-body" style="border-top:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:baseline;">
                    <span style="font-weight:600;color:var(--ink-secondary);">Total Order Amount</span>
                    <span class="summary-total-amount"><?php echo format_idr($total_order_amount); ?></span>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
            <span>Customer Storefront</span>
        </div>
    </footer>

    <script src="assets/js/ui.js"></script>
</body>

</html>
