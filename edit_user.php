<?php
session_start();
require_once __DIR__ . '/config.php';

// Only admin can edit users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$employee_number = trim($_POST['employee_number'] ?? '');
$username = trim($_POST['username'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$role = trim($_POST['role'] ?? '');

// Validation
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'Invalid user ID.';
}

if (empty($username) || empty($employee_number)) {
    $errors[] = 'Username and employee number are required.';
}

if (!in_array($role, ['admin', 'manager', 'staff'])) {
    $errors[] = 'Invalid role.';
}

// Check if user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    $errors[] = 'User not found.';
}

// Check if employee number is taken by another user
$stmt = $pdo->prepare('SELECT id FROM users WHERE employee_number = ? AND id != ?');
$stmt->execute([$employee_number, $user_id]);
if ($stmt->fetch()) {
    $errors[] = 'Employee number already exists for another user.';
}

// Check if username is taken by another user
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
$stmt->execute([$username, $user_id]);
if ($stmt->fetch()) {
    $errors[] = 'Username already exists for another user.';
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: users.php');
    exit;
}

try {
    // Update user
    $stmt = $pdo->prepare('UPDATE users SET 
        employee_number = ?, 
        username = ?, 
        first_name = ?, 
        last_name = ?, 
        department = ?, 
        role = ? 
        WHERE id = ?');
    
    $stmt->execute([
        $employee_number,
        $username,
        $first_name,
        $last_name,
        $department,
        $role,
        $user_id
    ]);
    
    $_SESSION['success_message'] = "User updated successfully!";
    header('Location: users.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error updating user: ' . $e->getMessage();
    header('Location: users.php');
    exit;
}
