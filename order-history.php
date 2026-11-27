<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();
$user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
    } else {
        $order_id = intval($_POST['order_id'] ?? 0);
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'Selesai' WHERE id = ? AND user_id = ? AND status = 'Sampai Tujuan'");
        $update_stmt->bind_param("ii", $order_id, $user_id);
        $update_stmt->execute();
        if ($update_stmt->affected_rows > 0) {
            set_flash('success', 'Order #' . $order_id . ' marked as completed. Thank you!');
        }
        $update_stmt->close();
    }
    header("Location: order-history.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$cart_count = cart_total_units();
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History — AS Berkah E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php if ($flash): ?>
        <div class="toast-banner toast-banner--<?php echo e($flash['type']); ?>" data-toast role="status">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

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
                <a href="shopping-cart.php">
                    Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="nav-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="order-history.php" class="active">Orders</a>
                <a href="account.php">Account</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Purchases</div>
                <h1 class="page-title">Order History</h1>
                <p class="page-subtitle">Track active deliveries, finish pending payments, and review past orders.</p>
            </div>
            <a href="customer_dashboard.php" class="btn btn-primary">Shop Again</a>
        </div>

        <section class="surface-card">
            <?php if ($orders_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo intval($order['id']); ?></strong></td>
                                    <td class="tabular-nums"><?php echo date("d M Y, H:i", strtotime($order['created_at'])); ?></td>
                                    <td class="tabular-nums"><strong><?php echo format_idr($order['total_amount']); ?></strong></td>
                                    <td>
                                        <span class="badge-pill <?php echo status_badge_class($order['status']); ?>">
                                            <?php echo e(ucfirst($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="action-group" style="justify-content:flex-end;">
                                            <?php if (strtolower($order['status']) === 'awaiting payment'): ?>
                                                <a href="payment.php?order_id=<?php echo intval($order['id']); ?>" class="btn btn-primary btn-sm">Pay Now</a>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'Sampai Tujuan'): ?>
                                                <form method="POST" style="margin:0;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                                    <button type="submit" name="mark_completed" class="btn btn-success btn-sm">Mark Completed</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="order-details.php?order_id=<?php echo intval($order['id']); ?>" class="btn btn-secondary btn-sm">View Details</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No orders yet</h3>
                    <p>Once you place an order, you can track its progress and view receipts here.</p>
                    <a href="customer_dashboard.php" class="btn btn-primary">Explore Products</a>
                </div>
            <?php endif; ?>
        </section>
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
