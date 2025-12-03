<?php
/**
 * Get all modifiers with their associated categories
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

$pdo = $GLOBALS['pdo'];

try {
    $stmt = $pdo->query('
        SELECT 
            MIN(cm.id) as id,
            cm.label,
            cm.key_name,
            GROUP_CONCAT(DISTINCT c.name SEPARATOR ", ") as categories
        FROM category_modifiers cm
        LEFT JOIN categories c ON cm.category_id = c.id
        GROUP BY cm.label, cm.key_name
        ORDER BY cm.label
    ');
    $modifiers = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'modifiers' => $modifiers
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
