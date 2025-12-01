<?php
session_start();
require_once __DIR__ . '/config.php';

// Only admin can delete users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);

// Validation
if ($user_id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID';
    header('Location: users.php');
    exit;
}

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user']['id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account';
    header('Location: users.php');
    exit;
}

try {
    // Check if user exists and get their details
    $stmt = $pdo->prepare('SELECT username, employee_number FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: users.php');
        exit;
    }
    
    // Delete user
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    
    $_SESSION['success_message'] = 'User "' . htmlspecialchars($user['username']) . '" (Employee #' . htmlspecialchars($user['employee_number']) . ') has been deleted successfully';
    header('Location: users.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error deleting user: ' . $e->getMessage();
    header('Location: users.php');
    exit;
}
