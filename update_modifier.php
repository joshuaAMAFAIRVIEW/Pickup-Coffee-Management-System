<?php
/**
 * Update modifier label
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

$id = (int)($_POST['id'] ?? 0);
$label = trim($_POST['label'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid modifier ID']);
    exit;
}

if (empty($label)) {
    echo json_encode(['success' => false, 'message' => 'Modifier label cannot be empty']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    // Generate new key_name from label
    $keyName = strtoupper(str_replace([' ', '-', '/'], '_', $label));
    
    // Update the modifier
    $stmt = $pdo->prepare('UPDATE category_modifiers SET label = :label, key_name = :key_name WHERE id = :id');
    $stmt->execute([
        ':label' => $label,
        ':key_name' => $keyName,
        ':id' => $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Modifier updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
