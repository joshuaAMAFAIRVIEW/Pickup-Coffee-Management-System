<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$itemId = (int)($_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
    exit;
}

try {
    $pdo = $GLOBALS['pdo'];
    
    // Get item name
    $itemStmt = $pdo->prepare('SELECT display_name FROM items WHERE id = :id');
    $itemStmt->execute([':id' => $itemId]);
    $item = $itemStmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
        exit;
    }
    
    // Get borrower history
    $stmt = $pdo->prepare('
        SELECT 
            ia.assigned_at,
            ia.unassigned_at as returned_at,
            ia.notes,
            ia.return_condition,
            ia.damage_details,
            u.first_name,
            u.last_name,
            u.username,
            u.department,
            u.region,
            CONCAT(u.first_name, " ", u.last_name) as user_name
        FROM item_assignments ia
        JOIN users u ON ia.user_id = u.id
        WHERE ia.item_id = :item_id
        ORDER BY ia.assigned_at DESC
    ');
    
    $stmt->execute([':item_id' => $itemId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'item_name' => $item['display_name'],
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
