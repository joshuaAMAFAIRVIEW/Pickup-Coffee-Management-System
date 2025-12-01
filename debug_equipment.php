<?php
// Simple test without auth to debug
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$categoryId = (int)($_GET['category_id'] ?? 0);

echo json_encode([
    'debug' => 'test',
    'category_id_received' => $categoryId,
    'pdo_available' => isset($GLOBALS['pdo']) ? 'yes' : 'no'
]);

if ($categoryId <= 0) {
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $stmt = $pdo->prepare('
        SELECT 
            i.id,
            i.display_name as name,
            i.attributes as details,
            i.status,
            i.item_condition
        FROM items i
        WHERE i.category_id = :category_id 
          AND i.status = "available"
          AND NOT EXISTS (
              SELECT 1 FROM item_assignments ia 
              WHERE ia.item_id = i.id 
                AND ia.unassigned_at IS NULL
          )
        ORDER BY i.display_name
    ');
    $stmt->execute([':category_id' => $categoryId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        if ($item['details']) {
            $details = json_decode($item['details'], true);
            $item['details'] = $details ?: [];
        } else {
            $item['details'] = [];
        }
    }

    echo "\n\n" . json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "\n\n" . json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
