<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

require_role('courier');
$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        header("Location: courier_dashboard.php");
        exit();
    }

    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    if ($order_id > 0 && in_array($new_status, ['Dikirim', 'Sampai Tujuan'], true)) {
        $expected_prev = $new_status === 'Dikirim' ? 'Siap Dikirim' : 'Dikirim';
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND status = ?");
        $stmt->bind_param("sis", $new_status, $order_id, $expected_prev);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            set_flash('success', 'Order #' . $order_id . ' updated to ' . $new_status . '.');
        }
        $stmt->close();
    }
    header("Location: courier_dashboard.php");
    exit();
}

$result = $conn->query("SELECT * FROM orders WHERE status IN ('Siap Dikirim', 'Dikirim') ORDER BY created_at DESC");

$orders = [];
$ready_count = 0;
$in_transit_count = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
        if ($row['status'] === 'Siap Dikirim') $ready_count++;
        if ($row['status'] === 'Dikirim') $in_transit_count++;
    }
}
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Delivery — AS Berkah E-Commerce</title>
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
            <a href="courier_dashboard.php" class="brand-link">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </span>
                <span class="brand-text">
                    <span class="brand-name">AS Berkah</span>
                    <span class="brand-sub">Delivery</span>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <nav class="nav-links" data-nav-links aria-label="Courier navigation">
                <a href="courier_dashboard.php" class="active">Deliveries</a>
                <a href="account.php">Account</a>
                <a href="?logout=true" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main page-main--narrow">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Dispatch</div>
                <h1 class="page-title">Courier Dashboard</h1>
                <p class="page-subtitle">Pick up ready packages and confirm delivery at the destination.</p>
            </div>
            <span class="role-chip"><span class="role-chip-dot"></span> Courier</span>
        </div>

        <section class="kpi-grid kpi-grid--2" aria-label="Delivery summary">
            <div class="kpi-card">
                <div class="kpi-top">
                    <span class="kpi-label">Ready for Pickup</span>
                    <span class="kpi-icon kpi-icon--warning" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    </span>
                </div>
                <div class="kpi-value"><?php echo $ready_count; ?></div>
                <div class="kpi-meta">Status: Siap Dikirim</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-top">
                    <span class="kpi-label">Out for Delivery</span>
                    <span class="kpi-icon kpi-icon--info" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </span>
                </div>
                <div class="kpi-value"><?php echo $in_transit_count; ?></div>
                <div class="kpi-meta">Status: Dikirim</div>
            </div>
        </section>

        <section class="surface-card">
            <div class="surface-card-header">
                <h2 class="surface-card-title">Orders to Deliver</h2>
                <span class="badge-pill badge-neutral"><?php echo count($orders); ?> active</span>
            </div>

            <?php if (count($orders) > 0): ?>
                <div class="table-wrap">
                    <table class="data-table data-table--responsive">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $row): ?>
                                <tr>
                                    <td data-label="Order"><strong>#<?php echo intval($row['id']); ?></strong></td>
                                    <td data-label="Destination" style="color:var(--ink-secondary);"><?php echo e($row['address'] ?: 'Standard delivery'); ?></td>
                                    <td data-label="Status">
                                        <span class="badge-pill <?php echo status_badge_class($row['status']); ?>">
                                            <?php echo e($row['status']); ?>
                                        </span>
                                    </td>
                                    <td data-actions="true" class="text-right">
                                        <?php if ($row['status'] === 'Siap Dikirim'): ?>
                                            <form method="POST" style="margin:0;display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="order_id" value="<?php echo intval($row['id']); ?>">
                                                <input type="hidden" name="new_status" value="Dikirim">
                                                <button type="submit" name="update_status" value="1" class="btn btn-primary btn-sm">Start Delivery (Dikirim)</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'Dikirim'): ?>
                                            <form method="POST" style="margin:0;display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="order_id" value="<?php echo intval($row['id']); ?>">
                                                <input type="hidden" name="new_status" value="Sampai Tujuan">
                                                <button type="submit" name="update_status" value="1" class="btn btn-success btn-sm">Mark Arrived (Sampai Tujuan)</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No active deliveries</h3>
                    <p>Orders marked as Siap Dikirim by warehouse staff will appear here.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
            <span>Courier Delivery Portal</span>
        </div>
    </footer>

    <script src="assets/js/ui.js"></script>
</body>

</html>
