<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin','manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid category ID';
    header('Location: categories.php');
    exit;
}

try {
    // Check if category exists
    $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $_SESSION['error_message'] = 'Category not found';
        header('Location: categories.php');
        exit;
    }
    
    // Delete category (CASCADE will delete modifiers and items)
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    
    $_SESSION['success_message'] = 'Category "' . htmlspecialchars($category['name']) . '" deleted successfully';
    header('Location: categories.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error deleting category: ' . $e->getMessage();
    header('Location: categories.php');
    exit;
}
