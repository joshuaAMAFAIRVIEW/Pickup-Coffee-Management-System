<?php
session_start();
require_once __DIR__ . '/config.php';

// Admin or area manager can edit users
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'area_manager'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['user'];
$is_admin = $current_user['role'] === 'admin';
$is_area_manager = $current_user['role'] === 'area_manager';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$employee_number = trim($_POST['employee_number'] ?? '');
$username = trim($_POST['username'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$mobile_number = trim($_POST['mobile_number'] ?? '');
$department = trim($_POST['department'] ?? '');
$role = trim($_POST['role'] ?? '');
$area_id = !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null;
$store_id = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;
$managed_by_user_id = !empty($_POST['managed_by_user_id']) ? (int)$_POST['managed_by_user_id'] : null;

// Validation
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'Invalid user ID.';
}

if (empty($username) || empty($employee_number)) {
    $errors[] = 'Username and employee number are required.';
}

if (!in_array($role, ['admin', 'area_manager', 'store_supervisor', 'borrower', 'manager', 'staff'])) {
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
    // Get previous role and area for history tracking
    $prevStmt = $pdo->prepare('SELECT role, area_id, store_id FROM users WHERE id = ?');
    $prevStmt->execute([$user_id]);
    $prevData = $prevStmt->fetch();
    
    // If area manager is editing, validate permissions
    if ($is_area_manager) {
      // Force role to store_supervisor
      $role = 'store_supervisor';
      
      // Check if user being edited belongs to their area (via store)
      $permCheck = $pdo->prepare('SELECT s.store_id FROM stores s 
                                   WHERE s.store_id = ? AND s.area_id = (SELECT area_id FROM users WHERE id = ?)');
      $permCheck->execute([$prevData['store_id'], $current_user['id']]);
      if (!$permCheck->fetch()) {
        $_SESSION['error_message'] = 'You can only edit supervisors in your area';
        header('Location: users.php');
        exit;
      }
      
      // Validate new store belongs to their area
      if ($store_id) {
        $storeCheck = $pdo->prepare('SELECT s.store_id FROM stores s 
                                      JOIN users u ON u.id = ? 
                                      WHERE s.store_id = ? AND s.area_id = u.area_id');
        $storeCheck->execute([$current_user['id'], $store_id]);
        if (!$storeCheck->fetch()) {
          $_SESSION['error_message'] = 'You can only assign supervisors to stores in your area';
          header('Location: users.php');
          exit;
        }
      }
    }
    
    // Update user
    $stmt = $pdo->prepare('UPDATE users SET 
        employee_number = ?, 
        username = ?, 
        first_name = ?, 
        last_name = ?, 
        mobile_number = ?,
        department = ?, 
        role = ?,
        area_id = ?,
        store_id = ?,
        managed_by_user_id = ?
        WHERE id = ?');
    
    $stmt->execute([
        $employee_number,
        $username,
        $first_name,
        $last_name,
        $mobile_number,
        $department,
        $role,
        $area_id,
        $store_id,
        $managed_by_user_id,
        $user_id
    ]);
    
    // Track area manager history changes
    if ($role === 'area_manager' && $area_id) {
        // Check if area changed
        if ($prevData['area_id'] != $area_id) {
            // Close previous area assignment
            if ($prevData['area_id']) {
                $histStmt = $pdo->prepare('UPDATE area_manager_history SET unassigned_date = CURDATE() WHERE user_id = ? AND area_id = ? AND unassigned_date IS NULL');
                $histStmt->execute([$user_id, $prevData['area_id']]);
            }
            
            // Create new area assignment
            $histStmt = $pdo->prepare('INSERT INTO area_manager_history (user_id, area_id, assigned_date) VALUES (?, ?, CURDATE())');
            $histStmt->execute([$user_id, $area_id]);
        }
    } elseif ($prevData['role'] === 'area_manager' && $role !== 'area_manager') {
        // Role changed from area_manager to something else - close history
        if ($prevData['area_id']) {
            $histStmt = $pdo->prepare('UPDATE area_manager_history SET unassigned_date = CURDATE() WHERE user_id = ? AND area_id = ? AND unassigned_date IS NULL');
            $histStmt->execute([$user_id, $prevData['area_id']]);
        }
    }
    
    $_SESSION['success_message'] = "User updated successfully!";
    header('Location: users.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error updating user: ' . $e->getMessage();
    header('Location: users.php');
    exit;
}
