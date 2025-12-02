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
    // Get old attributes for history tracking
    $oldStmt = $pdo->prepare('SELECT attributes FROM items WHERE id = :id');
    $oldStmt->execute([':id' => $item_id]);
    $oldItem = $oldStmt->fetch();
    $oldAttributes = $oldItem ? $oldItem['attributes'] : '{}';
    
    // Encode new attributes as JSON
    $attributes_json = json_encode($attrs);
    
    // Update the item's attributes
    $stmt = $pdo->prepare('UPDATE items SET attributes = :attrs WHERE id = :id');
    $stmt->execute([
        ':attrs' => $attributes_json,
        ':id' => $item_id
    ]);
    
    // Log the change to history table
    $historyStmt = $pdo->prepare('
        INSERT INTO item_modifier_history (item_id, changed_by, old_attributes, new_attributes) 
        VALUES (:item_id, :changed_by, :old_attrs, :new_attrs)
    ');
    $historyStmt->execute([
        ':item_id' => $item_id,
        ':changed_by' => $_SESSION['user']['id'],
        ':old_attrs' => $oldAttributes,
        ':new_attrs' => $attributes_json
    ]);
    
    $_SESSION['success'] = 'Item modifiers updated successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error updating modifiers: ' . $e->getMessage();
}

header('Location: inventory.php');
exit;
