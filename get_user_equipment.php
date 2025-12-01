<?php
/**
 * Get user equipment details (current borrowed + history)
 * Returns JSON with current assignments and history
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$userId = (int)($_GET['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

// Get current borrowed equipment (not returned)
$currentStmt = $pdo->prepare('
    SELECT 
        ia.id as assignment_id,
        ia.assigned_at,
        ia.notes,
        i.display_name as item_name,
        i.attributes as details,
        i.item_condition,
        c.name as category_name
    FROM item_assignments ia
    JOIN items i ON ia.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE ia.user_id = :user_id 
      AND ia.unassigned_at IS NULL
    ORDER BY ia.assigned_at DESC
');
$currentStmt->execute([':user_id' => $userId]);
$current = $currentStmt->fetchAll(PDO::FETCH_ASSOC);

// Format details for display
foreach ($current as &$item) {
    if ($item['details']) {
        $details = json_decode($item['details'], true);
        if ($details) {
            $detailsStr = [];
            foreach ($details as $key => $value) {
                $detailsStr[] = "$key: $value";
            }
            $item['details'] = implode(', ', $detailsStr);
        }
    }
    $item['assigned_at'] = date('M d, Y H:i', strtotime($item['assigned_at']));
}

// Get assignment history (returned equipment)
$historyStmt = $pdo->prepare('
    SELECT 
        ia.assigned_at,
        ia.unassigned_at as returned_at,
        ia.notes,
        i.display_name as item_name,
        i.item_condition,
        c.name as category_name,
        TIMESTAMPDIFF(DAY, ia.assigned_at, ia.unassigned_at) as days_used
    FROM item_assignments ia
    JOIN items i ON ia.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE ia.user_id = :user_id 
      AND ia.unassigned_at IS NOT NULL
    ORDER BY ia.unassigned_at DESC
    LIMIT 50
');
$historyStmt->execute([':user_id' => $userId]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Format dates and duration
foreach ($history as &$item) {
    $item['assigned_at'] = date('M d, Y', strtotime($item['assigned_at']));
    $item['returned_at'] = date('M d, Y', strtotime($item['returned_at']));
    $item['duration'] = $item['days_used'] . ' day' . ($item['days_used'] != 1 ? 's' : '');
}

echo json_encode([
    'success' => true,
    'current' => $current,
    'history' => $history
]);
