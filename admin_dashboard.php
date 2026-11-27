<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf()) {
    set_flash('error', 'Invalid security token. Please try again.');
    header("Location: admin_dashboard.php");
    exit();
  }
  if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    $valid_actions = ['approve_user', 'reject_user', 'delete_user'];
    if (in_array($action, $valid_actions, true)) {
      if ($action === 'approve_user') {
        $stmt = $conn->prepare("UPDATE users SET status = 'actived' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User account approved.');
      } elseif ($action === 'reject_user') {
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User registration rejected.');
      } elseif ($action === 'delete_user') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User deleted.');
      }
      header("Location: admin_dashboard.php");
      exit();
    }
  }
}

$pending_users = $conn->query("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC");
$customers = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer' AND status = 'actived'");
$employees = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'employee' AND status = 'actived'");
$couriers = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'courier' AND status = 'actived'");
$products_res = $conn->query("SELECT COUNT(*) AS cnt FROM products");
$orders_res = $conn->query("SELECT COUNT(*) AS cnt FROM orders");

$pending_count = $pending_users ? $pending_users->num_rows : 0;
$active_users_count = ($customers ? intval($customers->fetch_assoc()['cnt']) : 0)
  + ($employees ? intval($employees->fetch_assoc()['cnt']) : 0)
  + ($couriers ? intval($couriers->fetch_assoc()['cnt']) : 0);
$products_count = $products_res ? intval($products_res->fetch_assoc()['cnt']) : 0;
$orders_count = $orders_res ? intval($orders_res->fetch_assoc()['cnt']) : 0;
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Overview — AS Berkah E-Commerce</title>
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
        <a href="admin_dashboard.php" class="active">Overview</a>
        <a href="manajemen_user.php">Users</a>
        <a href="inventory_management.php">Inventory</a>
        <a href="order-management.php">Orders</a>
        <a href="report.php">Reports</a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="page-main">
    <div class="page-header">
      <div>
        <div class="page-eyebrow">Operations</div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Monitor store activity, approve new accounts, and manage fulfillment.</p>
      </div>
      <a href="inventory_management.php" class="btn btn-primary">Manage Inventory</a>
    </div>

    <section class="kpi-grid" aria-label="Store metrics">
      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">Pending Approvals</span>
          <span class="kpi-icon kpi-icon--warning" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </span>
        </div>
        <div class="kpi-value"><?php echo $pending_count; ?></div>
        <div class="kpi-meta">Accounts waiting review</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">Active Users</span>
          <span class="kpi-icon kpi-icon--success" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </span>
        </div>
        <div class="kpi-value"><?php echo $active_users_count; ?></div>
        <div class="kpi-meta">Customers, staff &amp; couriers</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">Catalog Products</span>
          <span class="kpi-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
          </span>
        </div>
        <div class="kpi-value"><?php echo $products_count; ?></div>
        <div class="kpi-meta">Active SKU listings</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-top">
          <span class="kpi-label">Total Orders</span>
          <span class="kpi-icon kpi-icon--info" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </span>
        </div>
        <div class="kpi-value"><?php echo $orders_count; ?></div>
        <div class="kpi-meta">Across all statuses</div>
      </div>
    </section>

    <section class="quick-grid" aria-label="Quick navigation">
      <a href="manajemen_user.php" class="quick-card">
        <span class="quick-card-title">User Management <span>&rarr;</span></span>
        <p class="quick-card-desc">Approve registrations and review active roles.</p>
      </a>
      <a href="inventory_management.php" class="quick-card">
        <span class="quick-card-title">Inventory <span>&rarr;</span></span>
        <p class="quick-card-desc">Add products, update prices, and adjust stock.</p>
      </a>
      <a href="order-management.php" class="quick-card">
        <span class="quick-card-title">Orders <span>&rarr;</span></span>
        <p class="quick-card-desc">Confirm incoming customer orders for packing.</p>
      </a>
      <a href="report.php" class="quick-card">
        <span class="quick-card-title">Sales Report <span>&rarr;</span></span>
        <p class="quick-card-desc">Inspect completed orders and revenue totals.</p>
      </a>
    </section>

    <section class="surface-card">
      <div class="surface-card-header">
        <h2 class="surface-card-title">Pending Account Approvals</h2>
        <a href="manajemen_user.php" class="btn btn-secondary btn-sm">View All Users</a>
      </div>

      <?php if ($pending_count > 0): ?>
        <div class="table-wrap">
          <table class="data-table data-table--responsive">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($user = $pending_users->fetch_assoc()): ?>
                <tr>
                  <td class="tabular-nums" data-label="ID">#<?php echo intval($user['id']); ?></td>
                  <td data-label="Username"><strong><?php echo e($user['username']); ?></strong></td>
                  <td data-label="Role"><span class="badge-pill badge-neutral"><?php echo e(ucfirst($user['role'])); ?></span></td>
                  <td class="text-right" data-actions="true">
                    <div class="action-group" style="justify-content:flex-end;">
                      <form method="POST" style="margin:0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                        <button type="submit" name="action" value="approve_user" class="btn btn-success btn-sm">Approve</button>
                      </form>
                      <form method="POST" style="margin:0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                        <button type="submit" name="action" value="reject_user" class="btn btn-danger btn-sm">Reject</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h3>All caught up</h3>
          <p>There are no pending account registrations waiting for approval.</p>
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
