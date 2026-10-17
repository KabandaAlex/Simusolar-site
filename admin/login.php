<?php
require_once __DIR__ . '/auth.php';
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user && $pass && adminLogin($user, $pass)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
require_once __DIR__ . '/../config/path.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Tulima Solar</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/admin/assets/css/admin.css">
  <style>
    /* Additional Login Page Styles */
    .c-login__field-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-4);
    }
  </style>
</head>
<body>
  <main class="admin-login-wrap" role="main">
    <div class="admin-login-card">
      <!-- Header with Logo -->
      <header class="c-login__header">
        <div class="admin-login__logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"/>
            <line x1="12" y1="1" x2="12" y2="3"/>
            <line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
            <line x1="1" y1="12" x2="3" y2="12"/>
            <line x1="21" y1="12" x2="23" y2="12"/>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
          </svg>
        </div>
        <h1 class="admin-login__title">Welcome Back</h1>
        <p class="admin-login__subtitle">Sign in to access the Tulima Solar admin panel</p>
      </header>

      <!-- Error Alert -->
      <?php if ($error): ?>
      <div class="c-login__alert" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" class="c-login__form">
        <div class="c-login__field">
          <label for="username" class="c-login__label">Username</label>
          <div class="admin-login__input-wrap">
            <svg class="admin-login__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <input
              type="text"
              id="username"
              name="username"
              class="admin-login__input"
              required 
              autofocus 
              autocomplete="username" 
              placeholder="Enter your username"
            >
          </div>
        </div>

        <div class="c-login__field">
          <label for="password" class="c-login__label">Password</label>
          <div class="admin-login__input-wrap">
            <svg class="admin-login__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              type="password"
              id="password"
              name="password"
              class="admin-login__input"
              required 
              autocomplete="current-password" 
              placeholder="Enter your password"
            >
          </div>
        </div>

        <div class="c-login__field-row">
          <div class="c-login__remember">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
          </div>
          <div class="c-login__forgot">
            <a href="#">Forgot password?</a>
          </div>
        </div>

        <button type="submit" class="btn btn--primary admin-login__btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          Sign In
        </button>
      </form>

      <!-- Footer -->
      <footer class="admin-login__footer">
        <a href="<?php echo BASE_PATH; ?>/index.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to website
        </a>
      </footer>
    </div>
  </main>
</body>
</html>
