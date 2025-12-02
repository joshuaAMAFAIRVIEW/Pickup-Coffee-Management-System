<?php
/**
 * Get repair history for an item
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

$itemId = (int)($_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $stmt = $pdo->prepare('
        SELECT 
            rh.id,
            rh.old_status,
            rh.new_status,
            rh.notes,
            rh.changed_at,
            u.username,
            u.first_name,
            u.last_name
        FROM item_repair_history rh
        LEFT JOIN users u ON rh.changed_by = u.id
        WHERE rh.item_id = :item_id
        ORDER BY rh.changed_at DESC
    ');
    $stmt->execute([':item_id' => $itemId]);
    $history = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
