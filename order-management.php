<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
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

// Handle Order Confirmation
if (isset($_GET['confirm_order'])) {
    $order_id = intval($_GET['confirm_order']);

    $update_status = "UPDATE orders SET status = 'Dikonfirmasi' WHERE id = ?";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $order_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($order_items);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($item = $result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
    }

    header("Location: order-management.php");
    exit();
}

$sql = "SELECT orders.id, orders.created_at, orders.total_amount, orders.status, users.username
        FROM orders
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.created_at DESC";
$result = $conn->query($sql);

function getOrderBadgeClass($status) {
    $s = strtolower(trim($status));
    if ($s === 'selesai' || $s === 'paid') return 'badge-success';
    if ($s === 'pending' || $s === 'awaiting payment' || $s === 'dikemas') return 'badge-warning';
    if ($s === 'dikonfirmasi' || $s === 'siap dikirim' || $s === 'dikirim' || $s === 'sampai tujuan') return 'badge-info';
    return 'badge-neutral';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Management — AS Berkah E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <header class="site-header">
    <div class="header-inner">
      <a href="admin_dashboard.php" class="brand-link">
        <span class="brand-mark" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </span>
        <span class="brand-text">
          <span class="brand-name">AS Berkah</span>
          <span class="brand-sub">Admin Console</span>
        </span>
      </a>

      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>

      <nav class="nav-links" data-nav-links aria-label="Admin navigation">
        <a href="admin_dashboard.php">Overview</a>
        <a href="manajemen_user.php">Users</a>
        <a href="inventory_management.php">Inventory</a>
        <a href="order-management.php" class="active">Orders</a>
        <a href="report.php">Reports</a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="page-main">
    <div class="page-header">
      <div>
        <div class="page-eyebrow">Fulfillment</div>
        <h1 class="page-title">Order Management</h1>
        <p class="page-subtitle">Review incoming customer orders and confirm pending transactions.</p>
      </div>
    </div>

    <section class="surface-card">
      <div class="surface-card-header">
        <h2 class="surface-card-title">All Orders</h2>
        <span class="badge-pill badge-neutral"><?php echo $result ? $result->num_rows : 0; ?> orders</span>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th class="text-right">Total</th>
                <th>Status</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><strong>#<?php echo intval($row['id']); ?></strong></td>
                  <td><?php echo htmlspecialchars($row['username']); ?></td>
                  <td class="tabular-nums"><?php echo date("d M Y, H:i", strtotime($row['created_at'])); ?></td>
                  <td class="text-right tabular-nums"><strong>Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?></strong></td>
                  <td>
                    <span class="badge-pill <?php echo getOrderBadgeClass($row['status']); ?>">
                      <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                    </span>
                  </td>
                  <td class="text-right">
                    <?php if (strtolower($row['status']) === 'pending'): ?>
                      <a href="order-management.php?confirm_order=<?php echo intval($row['id']); ?>" class="btn btn-success btn-sm">Confirm Order</a>
                    <?php else: ?>
                      <span style="font-size:12.5px;color:var(--ink-muted);">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h3>No orders found</h3>
          <p>Customer orders will appear here once placed.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
      <span>Admin Console</span>
    </div>
  </footer>

  <script src="assets/js/ui.js"></script>
</body>

</html>

<?php
$conn->close();
?>
