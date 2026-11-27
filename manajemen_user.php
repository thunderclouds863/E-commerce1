<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf()) {
    set_flash('error', 'Invalid security token.');
    header("Location: manajemen_user.php");
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
        set_flash('success', 'User account rejected.');
      } elseif ($action === 'delete_user') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User deleted.');
      }
      header("Location: manajemen_user.php");
      exit();
    }
  }
}

$pending_users = $conn->query("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC");
$customers = $conn->query("SELECT * FROM users WHERE role = 'customer' AND status = 'actived' ORDER BY id ASC");
$employees = $conn->query("SELECT * FROM users WHERE role = 'employee' AND status = 'actived' ORDER BY id ASC");
$couriers = $conn->query("SELECT * FROM users WHERE role = 'courier' AND status = 'actived' ORDER BY id ASC");
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management — AS Berkah E-Commerce</title>
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
        <a href="admin_dashboard.php">Overview</a>
        <a href="manajemen_user.php" class="active">Users</a>
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
        <div class="page-eyebrow">Directory</div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Review pending account registrations and browse active accounts by role.</p>
      </div>
    </div>

    <div class="stack-lg">
      <section class="surface-card">
        <div class="surface-card-header">
          <h2 class="surface-card-title">Pending Users</h2>
          <span class="badge-pill badge-warning"><?php echo $pending_users->num_rows; ?> pending</span>
        </div>

        <?php if ($pending_users->num_rows > 0): ?>
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
            <h3>No pending approvals</h3>
            <p>All registered users have been reviewed.</p>
          </div>
        <?php endif; ?>
      </section>

      <div class="users-grid">
        <section class="surface-card">
          <div class="surface-card-header">
            <h2 class="surface-card-title">Customers</h2>
            <span class="badge-pill badge-success"><?php echo $customers->num_rows; ?></span>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Username</th></tr></thead>
              <tbody>
                <?php if ($customers->num_rows > 0): ?>
                  <?php while ($customer = $customers->fetch_assoc()): ?>
                    <tr>
                      <td class="tabular-nums">#<?php echo intval($customer['id']); ?></td>
                      <td><strong><?php echo e($customer['username']); ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="2" style="color:var(--ink-muted);">No active customers.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="surface-card">
          <div class="surface-card-header">
            <h2 class="surface-card-title">Employees</h2>
            <span class="badge-pill badge-info"><?php echo $employees->num_rows; ?></span>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Username</th></tr></thead>
              <tbody>
                <?php if ($employees->num_rows > 0): ?>
                  <?php while ($employee = $employees->fetch_assoc()): ?>
                    <tr>
                      <td class="tabular-nums">#<?php echo intval($employee['id']); ?></td>
                      <td><strong><?php echo e($employee['username']); ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="2" style="color:var(--ink-muted);">No active employees.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="surface-card">
          <div class="surface-card-header">
            <h2 class="surface-card-title">Couriers</h2>
            <span class="badge-pill badge-neutral"><?php echo $couriers->num_rows; ?></span>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Username</th></tr></thead>
              <tbody>
                <?php if ($couriers->num_rows > 0): ?>
                  <?php while ($courier = $couriers->fetch_assoc()): ?>
                    <tr>
                      <td class="tabular-nums">#<?php echo intval($courier['id']); ?></td>
                      <td><strong><?php echo e($courier['username']); ?></strong></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="2" style="color:var(--ink-muted);">No active couriers.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
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
