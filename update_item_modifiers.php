<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'manager']);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$item_id = (int)($_POST['item_id'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);
$attrs = $_POST['attr'] ?? [];

if ($item_id <= 0) {
    $_SESSION['error'] = 'Invalid item ID';
    header('Location: inventory.php');
    exit;
}

if (!is_array($attrs)) {
    $attrs = [];
}

try {
    // Encode attributes as JSON
    $attributes_json = json_encode($attrs);
    
    // Update the item's attributes
    $stmt = $pdo->prepare('UPDATE items SET attributes = :attrs WHERE id = :id');
    $stmt->execute([
        ':attrs' => $attributes_json,
        ':id' => $item_id
    ]);
    
    $_SESSION['success'] = 'Item modifiers updated successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error updating modifiers: ' . $e->getMessage();
}

header('Location: inventory.php');
exit;
