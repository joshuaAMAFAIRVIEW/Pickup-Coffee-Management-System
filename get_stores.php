<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query('
        SELECT 
            s.store_id,
            s.store_name,
            s.store_code,
            s.area_id,
            s.address,
            s.contact_person,
            s.contact_employee_number,
            s.contact_number,
            s.opening_date,
            s.is_active,
            s.created_at,
            a.area_name,
            COUNT(DISTINCT sia.item_id) as equipment_count,
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, " ", u.last_name) SEPARATOR ", ") as supervisor_names
        FROM stores s
        LEFT JOIN areas a ON s.area_id = a.area_id
        LEFT JOIN store_item_assignments sia ON s.store_id = sia.store_id AND sia.received_date IS NOT NULL
        LEFT JOIN users u ON s.store_id = u.store_id AND u.role = "store_supervisor"
        GROUP BY s.store_id
        ORDER BY s.store_name ASC
    ');
    
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'stores' => $stores]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
