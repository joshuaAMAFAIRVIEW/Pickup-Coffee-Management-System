<?php
/**
 * Get available equipment for a specific category
 * Returns JSON with items that have status 'available'
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$categoryId = (int)($_GET['category_id'] ?? 0);

if ($categoryId <= 0) {
    echo json_encode(['error' => 'Invalid category ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    // Get available equipment (status = available, not currently assigned)
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

    // Parse JSON details
    foreach ($items as &$item) {
        if ($item['details']) {
            $details = json_decode($item['details'], true);
            $item['details'] = $details ?: [];
        } else {
            $item['details'] = [];
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Exception $e) {
    error_log("Error in get_available_equipment.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'items' => []
    ]);
}
