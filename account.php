<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['customer', 'employee', 'courier']);

$conn = db();
$user_id = intval($_SESSION['user_id']);
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT id, username, role, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf()) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $next = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($next) < 6) {
            $error_message = 'New password must be at least 6 characters.';
        } elseif ($next !== $confirm) {
            $error_message = 'New password and confirmation do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($current, $row['password'])) {
                $error_message = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($next, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hashed, $user_id);
                if ($upd->execute()) {
                    $success_message = 'Your password has been updated.';
                } else {
                    $error_message = 'Failed to update password. Please try again.';
                }
                $upd->close();
            }
        }
    }
}

$dashboard_href = $role . '_dashboard.php';
$cart_count = $role === 'customer' ? cart_total_units() : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings — AS Berkah E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <header class="site-header">
    <div class="header-inner">
      <a href="<?php echo e($dashboard_href); ?>" class="brand-link">
        <span class="brand-mark" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </span>
        <span class="brand-text">
          <span class="brand-name">AS Berkah</span>
          <span class="brand-sub"><?php echo e(ucfirst($role)); ?> Portal</span>
        </span>
      </a>

      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>

      <nav class="nav-links" data-nav-links aria-label="Account navigation">
        <?php if ($role === 'customer'): ?>
          <a href="customer_dashboard.php">Catalog</a>
          <a href="shopping-cart.php">Cart <?php if ($cart_count > 0): ?><span class="nav-badge"><?php echo $cart_count; ?></span><?php endif; ?></a>
          <a href="order-history.php">Orders</a>
        <?php else: ?>
          <a href="<?php echo e($dashboard_href); ?>">Dashboard</a>
        <?php endif; ?>
        <a href="account.php" class="active">Account</a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="page-main page-main--narrow">
    <div class="page-header">
      <div>
        <div class="page-eyebrow">Profile &amp; Security</div>
        <h1 class="page-title">Account Settings</h1>
        <p class="page-subtitle">Review your account details and keep your password secure.</p>
      </div>
      <a href="<?php echo e($dashboard_href); ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="split-layout">
      <section class="surface-card">
        <div class="surface-card-header">
          <h2 class="surface-card-title">Change Password</h2>
        </div>
        <div class="surface-card-body">
          <?php if ($error_message !== ''): ?>
            <div class="alert-banner alert-banner--error" role="alert"><?php echo e($error_message); ?></div>
          <?php endif; ?>
          <?php if ($success_message !== ''): ?>
            <div class="alert-banner alert-banner--success" role="status"><?php echo e($success_message); ?></div>
          <?php endif; ?>

          <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
              <label class="form-label" for="current_password">Current Password</label>
              <input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
              <label class="form-label" for="new_password">New Password</label>
              <input class="form-control" type="password" id="new_password" name="new_password" minlength="6" required autocomplete="new-password" placeholder="At least 6 characters">
            </div>
            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="6" required autocomplete="new-password">
            </div>
            <button type="submit" name="change_password" value="1" class="btn btn-primary">Update Password</button>
          </form>
        </div>
      </section>

      <aside class="summary-card">
        <h2 class="summary-title">Account Summary</h2>
        <div class="summary-line">
          <span>Username</span>
          <strong><?php echo e($user['username'] ?? ''); ?></strong>
        </div>
        <div class="summary-line">
          <span>Role</span>
          <span class="badge-pill badge-neutral"><?php echo e(ucfirst($user['role'] ?? $role)); ?></span>
        </div>
        <div class="summary-line">
          <span>Status</span>
          <span class="badge-pill badge-success"><?php echo e(ucfirst($user['status'] ?? 'actived')); ?></span>
        </div>
        <?php if (!empty($user['created_at'])): ?>
          <div class="summary-line">
            <span>Member Since</span>
            <span class="tabular-nums"><?php echo date("d M Y", strtotime($user['created_at'])); ?></span>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
      <span>Account Security</span>
    </div>
  </footer>

  <script src="assets/js/ui.js"></script>
</body>

</html>
