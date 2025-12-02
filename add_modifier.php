<?php
/**
 * Add new modifier to categories
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$label = trim($input['label'] ?? '');
$categoryIds = $input['category_ids'] ?? [];

if (empty($label)) {
    echo json_encode(['success' => false, 'message' => 'Modifier label cannot be empty']);
    exit;
}

if (empty($categoryIds) || !is_array($categoryIds)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one category']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $pdo->beginTransaction();
    
    // Generate key_name from label
    $keyName = strtoupper(str_replace([' ', '-', '/'], '_', $label));
    
    // Get the highest position for each category and insert the modifier
    foreach ($categoryIds as $categoryId) {
        // Get max position for this category
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), -1) + 1 as next_position FROM category_modifiers WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        $position = $stmt->fetchColumn();
        
        // Insert modifier
        $stmt = $pdo->prepare('INSERT INTO category_modifiers (category_id, label, key_name, type, required, position) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$categoryId, $label, $keyName, 'text', 0, $position]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Modifier added successfully to ' . count($categoryIds) . ' categories'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
