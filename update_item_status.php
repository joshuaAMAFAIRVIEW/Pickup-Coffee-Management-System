<?php
session_start();
require_once __DIR__ . '/config.php';

// Only admin and manager can update status
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$item_id = (int)($_POST['item_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$valid_statuses = ['available', 'borrowed', 'maintenance', 'retired'];

if ($item_id <= 0 || !in_array($status, $valid_statuses)) {
    $_SESSION['error_message'] = 'Invalid item ID or status';
    header('Location: inventory.php');
    exit;
}

try {
    // Update item status
    $stmt = $pdo->prepare('UPDATE items SET status = ? WHERE id = ?');
    $stmt->execute([$status, $item_id]);
    
    // If status is borrowed, we might want to assign it to a user (future enhancement)
    // For now, just update the status
    
    $_SESSION['success_message'] = 'Item status updated successfully!';
    header('Location: inventory.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error updating status: ' . $e->getMessage();
    header('Location: inventory.php');
    exit;
}
