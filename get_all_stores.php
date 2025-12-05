<?php
/**
 * Get all stores with area information for dropdown selection
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['admin']);

header('Content-Type: application/json');

try {
    $stmt = $pdo->query('
        SELECT 
            s.store_id,
            s.store_name,
            s.store_code,
            s.address,
            a.area_id,
            a.area_name
        FROM stores s
        INNER JOIN areas a ON s.area_id = a.area_id
        WHERE s.is_active = 1
        ORDER BY a.area_name, s.store_name
    ');
    
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stores' => $stores
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
