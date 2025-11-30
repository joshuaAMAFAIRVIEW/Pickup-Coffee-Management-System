<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: users.php'); exit; }

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'staff';
$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$position = trim($_POST['position'] ?? '');
$department = trim($_POST['department'] ?? '');
$store = trim($_POST['store'] ?? '');
$region = trim($_POST['region'] ?? '');

if ($username === '' || $password === '' || $email === '') {
  $_SESSION['flash_error'] = 'Username, password and email are required';
  header('Location: users.php'); exit;
}

$pdo = $GLOBALS['pdo'];
try {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins = $pdo->prepare('INSERT INTO users (username, password, role, first_name, middle_name, last_name, position, department, store, email, region) VALUES (:u,:p,:r,:fn,:mn,:ln,:pos,:dept,:store,:email,:region)');
  $ins->execute([':u'=>$username,':p'=>$hash,':r'=>$role,':fn'=>$first,':mn'=>$middle,':ln'=>$last,':pos'=>$position,':dept'=>$department,':store'=>$store,':email'=>$email,':region'=>$region]);

  // send onboarding email (PHPMailer if available)
  $subject = 'Your account has been created';
  $body = "<p>Hi ".htmlspecialchars($first ?: $username).",</p>";
  $body .= "<p>Your account has been created. Login details:<br> Username: <strong>".htmlspecialchars($username)."</strong><br>Password: <strong>".htmlspecialchars($password)."</strong></p>";

  // try PHPMailer
  $sent = false;
  if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
    require_once __DIR__.'/smtp_config.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = SMTP_HOST; $mail->SMTPAuth = true; $mail->Username = SMTP_USER; $mail->Password = SMTP_PASS; $mail->SMTPSecure = SMTP_ENCRYPT; $mail->Port = SMTP_PORT;
      $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
      $mail->addAddress($email);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;
      $mail->send();
      $sent = true;
    } catch (Exception $e) { $sent = false; }
  }

  // fallback to mail()
  if (!$sent) {
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . (defined('SMTP_FROM_NAME')?SMTP_FROM_NAME:'Pickup Coffee') . " <" . (defined('SMTP_FROM_EMAIL')?SMTP_FROM_EMAIL:'no-reply@example.com') . ">\r\n";
    @mail($email, $subject, $body, $headers);
  }

  $_SESSION['flash_success'] = 'User created and onboarding email sent (if configured)';
} catch (Exception $e) {
  $_SESSION['flash_error'] = 'Error creating user: ' . $e->getMessage();
}
header('Location: users.php'); exit;
