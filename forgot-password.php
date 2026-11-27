<?php
require_once __DIR__ . '/includes/bootstrap.php';

$conn = db();
ensure_password_resets_table($conn);

$error_message = '';
$info_message = '';
$demo_reset_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    if (!verify_csrf()) {
        $error_message = 'Session expired. Please refresh and try again.';
    } else {
        $role = trim($_POST['role'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if (!in_array($role, ['customer', 'employee', 'courier'], true) || $username === '') {
            $error_message = 'Please select your role and enter your username.';
        } else {
            $stmt = $conn->prepare("SELECT id, username, status FROM users WHERE username = ? AND role = ? LIMIT 1");
            $stmt->bind_param("ss", $username, $role);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Always show a non-enumerating confirmation message
            $info_message = 'If an active account matches that username and role, a single-use password reset link has been generated (valid for 30 minutes).';

            if ($user && $user['status'] === 'actived') {
                $user_id = intval($user['id']);

                // Invalidate any previous unused tokens for this user
                $inv = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
                $inv->bind_param("i", $user_id);
                $inv->execute();
                $inv->close();

                // Generate cryptographically secure single-use token (store SHA-256 hash only)
                $raw_token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $raw_token);

                $ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
                $ins->bind_param("is", $user_id, $token_hash);
                if ($ins->execute()) {
                    // Since this app has no email column/SMTP server configured, surface the one-time link directly in local/demo mode
                    $demo_reset_url = 'reset-password.php?token=' . urlencode($raw_token);
                }
                $ins->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — AS Berkah E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">
  <div class="auth-stage">
    <aside class="auth-editorial" aria-label="AS Berkah brand mark">
      <div class="auth-brand-row">
        <div class="auth-logo" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <span class="auth-wordmark">AS Berkah</span>
      </div>

      <div class="auth-artwork" aria-hidden="true">
        <svg viewBox="0 0 380 250" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="380" height="250" rx="12" fill="#FAF7F2"/>
          <path d="M24 206H356" stroke="#E8DFD3" stroke-width="2" stroke-linecap="round"/>
          <rect x="116" y="76" width="148" height="114" rx="14" fill="#FFFFFF" stroke="#D5C8B8" stroke-width="1.8"/>
          <path d="M154 76V58C154 38 170 24 190 24C210 24 226 38 226 58V76" stroke="#C8553D" stroke-width="4" stroke-linecap="round"/>
          <circle cx="190" cy="126" r="14" fill="#FBE9E4" stroke="#C8553D" stroke-width="2"/>
          <path d="M190 140V160" stroke="#C8553D" stroke-width="3" stroke-linecap="round"/>
          <circle cx="72" cy="64" r="5" fill="#EAC4BA"/>
          <circle cx="312" cy="56" r="6" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.5"/>
        </svg>
      </div>
      <p class="auth-caption">Reset tokens expire after 30 minutes and can only be used once.</p>
    </aside>

    <main class="auth-shell">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Forgot Password</h1>
          <p class="auth-subtitle">Request a secure password reset link</p>
        </div>

        <?php if ($error_message !== ''): ?>
          <div class="alert-banner alert-banner--error" role="alert">
            <span><?php echo e($error_message); ?></span>
          </div>
        <?php endif; ?>

        <?php if ($info_message !== ''): ?>
          <div class="alert-banner alert-banner--success" role="status">
            <div>
              <div><?php echo e($info_message); ?></div>
              <?php if ($demo_reset_url !== ''): ?>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--success-border);">
                  <div style="font-size:12px;margin-bottom:6px;opacity:.9;">Local / Demo Reset Link:</div>
                  <a href="<?php echo e($demo_reset_url); ?>" class="btn btn-success btn-sm" id="reset-link-btn">Open Password Reset Page &rarr;</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label class="form-label" for="role">Account Role</label>
            <select class="form-select" name="role" id="role" required>
              <option value="customer">Customer</option>
              <option value="employee">Employee</option>
              <option value="courier">Courier</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
          </div>

          <button class="btn btn-primary btn-lg btn-block" type="submit" name="request_reset" value="1">Generate Reset Link</button>
        </form>

        <div class="auth-footer">
          Remembered your password? <a href="index.php">Back to Sign In</a>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/ui.js"></script>
</body>

</html>
