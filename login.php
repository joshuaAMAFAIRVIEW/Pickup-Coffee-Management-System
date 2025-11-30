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
        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
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
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <style>
    body { background: linear-gradient(135deg, #f5f7fa, #e9efe3); font-family: 'Jost', sans-serif; }
    .login-card { max-width: 400px; margin: auto; border-radius: 12px; box-shadow: 0 6px 20px rgba(16,24,40,.08); }
    .login-card .card-title { font-weight: 600; text-align:center; }
    .btn-primary { border-radius: 50px; padding: 10px 20px; }
    .brand-top { text-align:center; margin-bottom: 12px; }
    .brand-top h2 { margin:0; font-weight:700; color:var(--theme-color); }
  </style>
</head>
<body>
  <div class="container d-flex align-items-center justify-content-center vh-100">
    <div class="card login-card">
      <div class="card-body">
        <div class="brand-top">
          <h2>PICKUP COFFEE</h2>
          <div class="text-muted">Inventory System</div>
        </div>
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" type="text" class="form-control" required value="<?php echo htmlspecialchars($username); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary">Sign in</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>