<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query('
        SELECT 
            a.area_id,
            a.area_name,
            a.parent_area_id,
            a.is_active,
            a.split_from_area_id,
            a.split_date,
            a.created_at,
            parent.area_name as parent_area_name,
            split_from.area_name as split_from_area_name,
            COUNT(DISTINCT s.store_id) as store_count,
            COUNT(DISTINCT u.id) as manager_count
        FROM areas a
        LEFT JOIN areas parent ON a.parent_area_id = parent.area_id
        LEFT JOIN areas split_from ON a.split_from_area_id = split_from.area_id
        LEFT JOIN stores s ON a.area_id = s.area_id AND s.is_active = 1
        LEFT JOIN users u ON a.area_id = u.area_id AND u.role = "area_manager"
        GROUP BY a.area_id
        ORDER BY a.area_name ASC
    ');
    
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'areas' => $areas]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
