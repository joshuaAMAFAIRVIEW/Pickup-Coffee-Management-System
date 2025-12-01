<?php
session_start();
require_once __DIR__ . '/config.php';

// Only admin can change user passwords
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'Invalid user ID.';
}

if (strlen($new_password) < 6) {
    $errors[] = 'Password must be at least 6 characters long.';
}

if ($new_password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

// Check if user exists
$stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    $errors[] = 'User not found.';
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: users.php');
    exit;
}

try {
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the user's password
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hashed_password, $user_id]);
    
    $_SESSION['success_message'] = "Password successfully changed for user: " . htmlspecialchars($target_user['username']);
    header('Location: users.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error changing password: ' . $e->getMessage();
    header('Location: users.php');
    exit;
}
