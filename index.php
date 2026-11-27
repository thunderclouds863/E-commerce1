<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$conn = db();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!verify_csrf()) {
        $error_message = "Session expired. Please refresh and try again.";
    } else {
        $role = trim($_POST['role'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($role === 'admin') {
            if ($username === 'admin' && $password === 'admin123') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = 1;
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = 'admin';
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid admin credentials.";
            }
        } elseif (in_array($role, ['customer', 'employee', 'courier'], true)) {
            $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? AND role = ? LIMIT 1");
            $stmt->bind_param("ss", $username, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($user['status'] !== 'actived') {
                    $error_message = $user['status'] === 'pending'
                        ? "Your account is awaiting admin approval."
                        : "This account is not active.";
                } elseif (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: " . $user['role'] . "_dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $error_message = "Please select a valid role.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — AS Berkah E-Commerce</title>
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
          <!-- Boutique storefront awning & frame -->
          <rect x="64" y="64" width="252" height="142" rx="10" fill="#FFFFFF" stroke="#D5C8B8" stroke-width="1.8"/>
          <path d="M56 76C56 64 64 56 76 56H304C316 56 324 64 324 76V92H56V76Z" fill="#C8553D"/>
          <path d="M56 92C56 103 68 109 80 109C92 109 102 103 102 92C102 103 114 109 126 109C138 109 148 103 148 92C148 103 160 109 172 109C184 109 194 103 194 92C194 103 206 109 218 109C230 109 240 103 240 92C240 103 252 109 264 109C276 109 286 103 286 92C286 103 298 109 310 109C318 109 324 103 324 92" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.5"/>
          <!-- Left display window: garment hanger -->
          <rect x="86" y="122" width="96" height="66" rx="6" fill="#FAF7F2" stroke="#E8DFD3" stroke-width="1.5"/>
          <path d="M134 134C134 131 137 129 139 131C141 133 137 136 134 138L114 150H154L134 138Z" stroke="#C8553D" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M118 150L114 176H154L150 150" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.6" stroke-linejoin="round"/>
          <!-- Right door & parcel -->
          <rect x="202" y="122" width="92" height="84" rx="6" fill="#F3EDE3" stroke="#D5C8B8" stroke-width="1.5"/>
          <circle cx="282" cy="166" r="3" fill="#C8553D"/>
          <!-- Parcel box accent -->
          <rect x="218" y="162" width="38" height="30" rx="4" fill="#FFFFFF" stroke="#C8553D" stroke-width="1.6"/>
          <path d="M237 162V192M218 176H256" stroke="#EAC4BA" stroke-width="1.5"/>
          <!-- Subtle geometric stars -->
          <circle cx="38" cy="48" r="4" fill="#EAC4BA"/>
          <circle cx="344" cy="44" r="5" fill="#FBE9E4" stroke="#C8553D" stroke-width="1.5"/>
        </svg>
      </div>
      <p class="auth-caption">Retail storefront, fulfillment &amp; delivery portal.</p>
    </aside>

    <main class="auth-shell">
      <div class="auth-card">
        <div class="auth-header">
          <h1 class="auth-title">Sign In</h1>
          <p class="auth-subtitle">Access your AS Berkah account</p>
        </div>

        <?php if (!empty($error_message)): ?>
          <div class="alert-banner alert-banner--error" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?php echo e($error_message); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" name="role" id="role" required>
              <option value="" disabled selected>Select your role</option>
              <option value="admin">Admin</option>
              <option value="customer">Customer</option>
              <option value="employee">Employee</option>
              <option value="courier">Courier</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="password-field">
              <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
              <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <button class="btn btn-primary btn-lg btn-block" type="submit" name="submit" value="login">Sign In</button>
        </form>

        <div class="auth-footer" id="link-section">
          Don't have an account? <a href="register.php">Create one</a>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/ui.js"></script>
  <script>
    const roleSelect = document.getElementById('role');
    const linkSection = document.getElementById('link-section');

    roleSelect.addEventListener('change', function () {
      linkSection.style.display = this.value === 'admin' ? 'none' : 'block';
    });
  </script>
</body>

</html>
