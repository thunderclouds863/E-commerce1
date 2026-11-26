<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'courier') {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Proses perubahan status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['new_status'];

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            header("Location: courier_dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$query = "SELECT * FROM orders WHERE status IN ('Siap Dikirim', 'Dikirim') ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$orders = [];
$ready_count = 0;
$in_transit_count = 0;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
        if ($row['status'] === 'Siap Dikirim') $ready_count++;
        if ($row['status'] === 'Dikirim') $in_transit_count++;
    }
}
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

            <div style="display:flex;align-items:center;gap:12px;">
                <span class="role-chip"><span class="role-chip-dot"></span> Courier</span>
                <a href="?logout=true" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="page-main page-main--narrow">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Dispatch</div>
                <h1 class="page-title">Courier Dashboard</h1>
                <p class="page-subtitle">Pick up ready packages and confirm delivery at the destination.</p>
            </div>
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
                    <table class="data-table">
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
                                    <td><strong>#<?php echo intval($row['id']); ?></strong></td>
                                    <td style="color:var(--ink-secondary);"><?php echo htmlspecialchars($row['address'] ?: 'Standard delivery'); ?></td>
                                    <td>
                                        <span class="badge-pill <?php echo $row['status'] === 'Siap Dikirim' ? 'badge-warning' : 'badge-info'; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($row['status'] == 'Siap Dikirim'): ?>
                                            <form method="POST" style="margin:0;display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo intval($row['id']); ?>">
                                                <input type="hidden" name="new_status" value="Dikirim">
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Start Delivery (Dikirim)</button>
                                            </form>
                                        <?php elseif ($row['status'] == 'Dikirim'): ?>
                                            <form method="POST" style="margin:0;display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo intval($row['id']); ?>">
                                                <input type="hidden" name="new_status" value="Sampai Tujuan">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">Mark Arrived (Sampai Tujuan)</button>
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

<?php
$conn->close();
?>
