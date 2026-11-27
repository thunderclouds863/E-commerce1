<?php
require_once __DIR__ . '/includes/bootstrap.php';

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf()) {
        header("Location: register.php?message=" . urlencode("Session expired. Please try again.") . "&type=error");
        exit();
    }

    $username = trim($_POST['username'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if (strlen($username) < 3 || strlen($username) > 50) {
        header("Location: register.php?message=" . urlencode("Username must be between 3 and 50 characters.") . "&type=error");
        exit();
    }
    if (strlen($raw_password) < 6) {
        header("Location: register.php?message=" . urlencode("Password must be at least 6 characters.") . "&type=error");
        exit();
    }
    if (!in_array($role, ['customer', 'employee', 'courier'], true)) {
        header("Location: register.php?message=" . urlencode("Please select a valid role.") . "&type=error");
        exit();
    }

    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        $stmt_check->close();
        header("Location: register.php?message=" . urlencode("Username is already taken.") . "&type=error");
        exit();
    }
    $stmt_check->close();

    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
    $status = $role === 'customer' ? 'actived' : 'pending';

    $stmt_ins = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
    $stmt_ins->bind_param("ssss", $username, $hashed_password, $role, $status);
    if ($stmt_ins->execute()) {
        $stmt_ins->close();
        $msg = $role === 'customer'
            ? "Account created! You can sign in right away."
            : "Registration submitted! Please wait for admin approval.";
        header("Location: register.php?message=" . urlencode($msg) . "&type=success");
        exit();
    } else {
        $stmt_ins->close();
        header("Location: register.php?message=" . urlencode("Registration failed. Please try again.") . "&type=error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — AS Berkah E-Commerce</title>
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
          <rect x="64" y="64" width="252" height="142" rx="10" fill="#FFFFFF" stroke="#D5C8B8" stroke-width="1.8"/>
          <path d="M56 76C56 64 64 56 76 56H304C316 56 324 64 324 76V92H56V76Z" fill="#C8553D"/>
          <path d="M56 92C56 103 68 109 80 109C92 109 102 103 102 92C102 103 114 109 126 109C138 109 148 103 148 92C148 103 160 109 172 109C184 109 194 103 194 92C194 103 206 109 218 109C230 109 240 103 240 92C240 103 252 109 264 109C276 109 286 103 286 92C286 103 298 109 310 109C318 109 324 103 324 92" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.5"/>
          <rect x="86" y="122" width="96" height="66" rx="6" fill="#FAF7F2" stroke="#E8DFD3" stroke-width="1.5"/>
          <path d="M134 134C134 131 137 129 139 131C141 133 137 136 134 138L114 150H154L134 138Z" stroke="#C8553D" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M118 150L114 176H154L150 150" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.6" stroke-linejoin="round"/>
          <rect x="202" y="122" width="92" height="84" rx="6" fill="#F3EDE3" stroke="#D5C8B8" stroke-width="1.5"/>
          <circle cx="282" cy="166" r="3" fill="#C8553D"/>
          <rect x="218" y="162" width="38" height="30" rx="4" fill="#FFFFFF" stroke="#C8553D" stroke-width="1.6"/>
          <path d="M237 162V192M218 176H256" stroke="#EAC4BA" stroke-width="1.5"/>
        </svg>
      </div>
      <p class="auth-caption">Customer accounts activate immediately; staff &amp; courier accounts require admin review.</p>
    </aside>

    <main class="auth-shell">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Create Account</h1>
          <p class="auth-subtitle">Join AS Berkah E-Commerce</p>
        </div>

        <?php if (isset($_GET['message'])): ?>
          <?php $msgType = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'error' : 'success'; ?>
          <div class="alert-banner alert-banner--<?php echo $msgType; ?>" role="alert">
            <span><?php echo e($_GET['message']); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
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
            <input class="form-control" type="text" id="username" name="username" minlength="3" maxlength="50" placeholder="Choose a username" autocomplete="username" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="password-field">
              <input class="form-control" type="password" id="password" name="password" minlength="6" placeholder="At least 6 characters" autocomplete="new-password" required>
              <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <button class="btn btn-primary btn-lg btn-block" type="submit" name="register" value="register">Create Account</button>
        </form>

        <div class="auth-footer">
          Already have an account? <a href="index.php">Sign in</a>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/ui.js"></script>
</body>

</html>
