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
    
    // Get modifier edit history
    $stmt = $pdo->prepare('
        SELECT 
            imh.old_attributes,
            imh.new_attributes,
            imh.changed_at,
            u.username as changed_by
        FROM item_modifier_history imh
        JOIN users u ON imh.changed_by = u.id
        WHERE imh.item_id = :item_id
        ORDER BY imh.changed_at DESC
    ');
    
    $stmt->execute([':item_id' => $itemId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process changes to show field-by-field differences
    $history = [];
    foreach ($records as $record) {
        $oldAttrs = json_decode($record['old_attributes'], true) ?? [];
        $newAttrs = json_decode($record['new_attributes'], true) ?? [];
        
        $changes = [];
        
        // Find all changed fields
        $allKeys = array_unique(array_merge(array_keys($oldAttrs), array_keys($newAttrs)));
        
        foreach ($allKeys as $key) {
            $oldVal = $oldAttrs[$key] ?? '';
            $newVal = $newAttrs[$key] ?? '';
            
            if ($oldVal !== $newVal) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => $oldVal,
                    'new_value' => $newVal
                ];
            }
        }
        
        $history[] = [
            'changed_at' => $record['changed_at'],
            'changed_by' => $record['changed_by'],
            'changes' => $changes
        ];
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
