<?php
/**
 * Delete a modifier
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

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid modifier ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    // First get the label of this modifier
    $stmt = $pdo->prepare('SELECT label FROM category_modifiers WHERE id = ?');
    $stmt->execute([$id]);
    $label = $stmt->fetchColumn();
    
    if (!$label) {
        echo json_encode(['success' => false, 'message' => 'Modifier not found']);
        exit;
    }
    
    // Delete all modifiers with this label (across all categories)
    $stmt = $pdo->prepare('DELETE FROM category_modifiers WHERE label = ?');
    $stmt->execute([$label]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Modifier deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Modifier not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
