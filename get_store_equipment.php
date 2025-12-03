<?php
/**
 * Get equipment for a specific store
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$store_id = isset($_GET['store_id']) ? (int)$_GET['store_id'] : 0;

if (empty($store_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Store ID is required']);
    exit;
}

try {
    // Get store details
    $storeStmt = $pdo->prepare('SELECT store_id, store_name, store_code, area_id FROM stores WHERE store_id = ?');
    $storeStmt->execute([$store_id]);
    $store = $storeStmt->fetch();
    
    if (!$store) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Store not found']);
        exit;
    }
    
    // Get equipment assigned to this store
    $equipmentStmt = $pdo->prepare('
        SELECT 
            i.id,
            i.item_name,
            i.serial_number,
            i.model,
            i.brand,
            i.status,
            c.category_name,
            sia.quantity,
            sia.assigned_date
        FROM store_item_assignments sia
        INNER JOIN items i ON sia.item_id = i.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE sia.store_id = ?
        ORDER BY i.item_name
    ');
    $equipmentStmt->execute([$store_id]);
    $equipment = $equipmentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'store' => $store,
        'equipment' => $equipment,
        'count' => count($equipment)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
