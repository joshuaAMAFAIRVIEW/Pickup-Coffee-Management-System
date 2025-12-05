<?php
session_start();
require_once __DIR__ . '/config.php';

$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, role, area_id, store_id, first_name, last_name FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'area_id' => $user['area_id'],
                'store_id' => $user['store_id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}
?>
<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/config.php';

// If already logged-in, prevent access to login page
if (isset($_SESSION['user'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - PICKUP COFFEE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      background: url('assets/img/backgound-login.jpg') center/cover no-repeat fixed;
      position: relative;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.3);
      z-index: 0;
    }
    
    .login-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }
    
    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      transform: translateY(0);
      transition: transform 0.3s ease;
    }
    
    .login-card:hover {
      transform: translateY(-5px);
    }
    
    .card-header-custom {
      background: linear-gradient(135deg, #AAC27F 0%, #8BA862 100%);
      padding: 40px 30px;
      text-align: center;
      color: white;
    }
    
    .brand-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 40px;
      backdrop-filter: blur(10px);
    }
    
    .brand-title {
      font-size: 28px;
      font-weight: 700;
      margin: 0;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .brand-subtitle {
      font-size: 14px;
      opacity: 0.9;
      margin-top: 5px;
    }
    
    .card-body {
      padding: 40px 35px;
    }
    
    .form-label {
      font-weight: 500;
      color: #333;
      margin-bottom: 8px;
      font-size: 14px;
    }
    
    .form-control {
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 12px 18px;
      font-size: 15px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #AAC27F;
      box-shadow: 0 0 0 0.2rem rgba(170, 194, 127, 0.15);
      transform: translateY(-2px);
    }
    
    .input-group-custom {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
    }
    
    .btn-login {
      background: linear-gradient(135deg, #AAC27F 0%, #8BA862 100%);
      border: none;
      border-radius: 12px;
      padding: 14px 20px;
      font-weight: 600;
      font-size: 16px;
      color: white;
      width: 100%;
      transition: all 0.3s ease;
      margin-top: 10px;
      position: relative;
      overflow: hidden;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(170, 194, 127, 0.4);
    }
    
    .btn-login:active {
      transform: translateY(0);
    }
    
    .btn-login.loading {
      pointer-events: none;
      opacity: 0.8;
    }
    
    .btn-login .btn-text {
      transition: opacity 0.3s ease;
    }
    
    .btn-login.loading .btn-text {
      opacity: 0;
    }
    
    .btn-login .spinner {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .btn-login.loading .spinner {
      opacity: 1;
    }
    
    .spinner {
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
      to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    
    .alert {
      border-radius: 12px;
      border: none;
      padding: 12px 16px;
      font-size: 14px;
      animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .alert-danger {
      background: #fee;
      color: #c33;
    }
    
    .alert-success {
      background: #efe;
      color: #3c3;
    }
    
    /* Loading overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(170, 194, 127, 0.95);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .loading-overlay.show {
      opacity: 1;
      visibility: visible;
    }
    
    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 5px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin2 1s linear infinite;
    }
    
    @keyframes spin2 {
      to { transform: rotate(360deg); }
    }
    
    .loading-text {
      color: white;
      font-size: 18px;
      margin-top: 20px;
      font-weight: 500;
    }
    
    .success-icon {
      width: 80px;
      height: 80px;
      border: 4px solid white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      color: white;
      animation: scaleIn 0.5s ease;
    }
    
    @keyframes scaleIn {
      from {
        transform: scale(0);
        opacity: 0;
      }
      to {
        transform: scale(1);
        opacity: 1;
      }
    }
    
    .error-shake {
      animation: shake 0.5s ease;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }
  </style>
</head>
<body>
  <div class="loading-overlay" id="loadingOverlay">
    <div id="loadingContent">
      <div class="loading-spinner"></div>
      <div class="loading-text">Signing in...</div>
    </div>
  </div>

  <div class="login-container">
    <div class="login-card">
      <div class="card-header-custom">
        <div class="brand-icon">
          <img src="assets/img/UPWhite.png" alt="Pickup Coffee Logo" style="width: 80px; height: auto;">
        </div>
        <div style="margin-top: 15px;">
          <img src="assets/img/PICKUP-Horizontal-White.png" alt="PICKUP COFFEE" style="width: 250px; height: auto;">
        </div>
        <div class="brand-subtitle">Inventory Management System</div>
      </div>
      
      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger mb-3" id="errorAlert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
          </div>
        <?php endif; ?>

        <form method="post" id="loginForm" novalidate>
          <div class="mb-3">
            <label class="form-label">
              <i class="fas fa-user me-2"></i>Username
            </label>
            <div class="input-group-custom">
              <input name="username" type="text" class="form-control" id="username" required 
                     value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username">
              <span class="input-icon"><i class="fas fa-user"></i></span>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">
              <i class="fas fa-lock me-2"></i>Password
            </label>
            <div class="input-group-custom">
              <input name="password" type="password" class="form-control" id="password" required 
                     placeholder="Enter your password">
              <span class="input-icon"><i class="fas fa-lock"></i></span>
            </div>
          </div>
          
          <button type="submit" class="btn btn-login" id="loginBtn">
            <span class="btn-text">
              <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </span>
            <div class="spinner"></div>
          </button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const errorAlert = document.getElementById('errorAlert');
    const loginCard = document.querySelector('.login-card');
    
    // Add shake animation to error alert
    if (errorAlert) {
      loginCard.classList.add('error-shake');
      setTimeout(() => loginCard.classList.remove('error-shake'), 500);
    }
    
    loginForm.addEventListener('submit', function(e) {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      
      if (username && password) {
        loginBtn.classList.add('loading');
        loadingOverlay.classList.add('show');
      }
    });
    
    // Simulate success animation (when login is successful, PHP redirects)
    // This will only show if there's a delay in the redirect
    <?php if (isset($_SESSION['user']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    setTimeout(() => {
      document.getElementById('loadingContent').innerHTML = `
        <div class="success-icon">
          <i class="fas fa-check"></i>
        </div>
        <div class="loading-text">Login Successful!</div>
      `;
    }, 500);
    <?php endif; ?>
  </script>
</body>
</html>