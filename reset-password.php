<?php
require_once __DIR__ . '/includes/bootstrap.php';

$conn = db();
ensure_password_resets_table($conn);

$raw_token = trim($_POST['token'] ?? $_GET['token'] ?? '');
$error_message = '';
$valid_reset = null;

if ($raw_token !== '') {
    $token_hash = hash('sha256', $raw_token);
    $stmt = $conn->prepare(
        "SELECT pr.id AS reset_id, pr.user_id, u.username, u.role
         FROM password_resets pr
         INNER JOIN users u ON pr.user_id = u.id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() AND u.status = 'actived'
         LIMIT 1"
    );
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $valid_reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!verify_csrf()) {
        $error_message = 'Session expired. Please refresh and try again.';
    } elseif (!$valid_reset) {
        $error_message = 'This reset link is invalid, expired, or has already been used.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Password and confirmation do not match.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $uid = intval($valid_reset['user_id']);
            $rid = intval($valid_reset['reset_id']);

            $conn->begin_transaction();
            try {
                $upd_user = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd_user->bind_param("si", $hashed, $uid);
                $upd_user->execute();
                $upd_user->close();

                $upd_tok = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
                $upd_tok->bind_param("i", $uid);
                $upd_tok->execute();
                $upd_tok->close();

                $conn->commit();
                set_flash('success', 'Your password has been reset. You can now sign in with your new password.');
                header("Location: index.php");
                exit();
            } catch (Throwable $ex) {
                $conn->rollback();
                $error_message = 'Failed to reset password. Please try again.';
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
  <title>Reset Password — AS Berkah E-Commerce</title>
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
        </svg>
      </div>
      <p class="auth-caption">Choose a strong new password to regain access to your account.</p>
    </aside>

    <main class="auth-shell">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Reset Password</h1>
          <?php if ($valid_reset): ?>
            <p class="auth-subtitle">Setting a new password for <strong><?php echo e($valid_reset['username']); ?></strong></p>
          <?php else: ?>
            <p class="auth-subtitle">Reset link verification</p>
          <?php endif; ?>
        </div>

        <?php if ($error_message !== ''): ?>
          <div class="alert-banner alert-banner--error" role="alert">
            <span><?php echo e($error_message); ?></span>
          </div>
        <?php endif; ?>

        <?php if (!$valid_reset): ?>
          <div class="alert-banner alert-banner--error" role="alert">
            <span>This password reset link is invalid, expired, or has already been used.</span>
          </div>
          <a href="forgot-password.php" class="btn btn-primary btn-lg btn-block">Request a New Reset Link</a>
        <?php else: ?>
          <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo e($raw_token); ?>">

            <div class="form-group">
              <label class="form-label" for="new_password">New Password</label>
              <div class="password-field">
                <input class="form-control" type="password" id="new_password" name="new_password" minlength="6" placeholder="At least 6 characters" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-toggle-password="new_password" aria-label="Show password" aria-pressed="false">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <div class="password-field">
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="Re-enter new password" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-toggle-password="confirm_password" aria-label="Show password" aria-pressed="false">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>

            <button class="btn btn-primary btn-lg btn-block" type="submit" name="reset_password" value="1">Save New Password</button>
          </form>
        <?php endif; ?>

        <div class="auth-footer">
          <a href="index.php">Back to Sign In</a>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/ui.js"></script>
</body>

</html>
