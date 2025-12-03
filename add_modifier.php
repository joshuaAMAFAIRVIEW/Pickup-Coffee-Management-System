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
$categoryIds = $input['category_ids'] ?? null;

if (empty($label)) {
    echo json_encode(['success' => false, 'message' => 'Modifier label cannot be empty']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $pdo->beginTransaction();
    
    // Generate key_name from label
    $keyName = strtoupper(str_replace([' ', '-', '/'], '_', $label));
    
    // If category_ids provided (old behavior), insert for each category
    if (!empty($categoryIds) && is_array($categoryIds)) {
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
        
        $message = 'Modifier added successfully to ' . count($categoryIds) . ' categories';
    } else {
        // New behavior: Create standalone modifier (not tied to any category)
        // Insert with category_id = NULL and position = 0
        $stmt = $pdo->prepare('INSERT INTO category_modifiers (category_id, label, key_name, type, required, position) VALUES (NULL, ?, ?, ?, ?, ?)');
        $stmt->execute([$label, $keyName, 'text', 0, 0]);
        
        $message = 'Modifier added successfully';
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
