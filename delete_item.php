<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    // Check if item is currently assigned to someone
    $checkStmt = $pdo->prepare('SELECT assigned_user_id, display_name FROM items WHERE id = :id');
    $checkStmt->execute([':id' => $itemId]);
    $item = $checkStmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    if ($item['assigned_user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete item that is currently assigned to a user. Please return the item first.']);
        exit;
    }
    
    // Delete the item
    $deleteStmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
    $deleteStmt->execute([':id' => $itemId]);
    
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

