<?php
/**
 * Get area manager reassignment history
 * Only accessible by IT/Admin
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $sql = 'SELECT 
                amh.id,
                amh.user_id,
                amh.from_area_id,
                amh.to_area_id,
                amh.changed_by_user_id,
                amh.reason,
                amh.changed_at,
                u.first_name as user_first_name,
                u.last_name as user_last_name,
                u.username as user_username,
                a_from.area_name as from_area_name,
                a_to.area_name as to_area_name,
                changed_by.first_name as changed_by_first_name,
                changed_by.last_name as changed_by_last_name
            FROM area_manager_history amh
            INNER JOIN users u ON amh.user_id = u.id
            LEFT JOIN areas a_from ON amh.from_area_id = a_from.area_id
            LEFT JOIN areas a_to ON amh.to_area_id = a_to.area_id
            INNER JOIN users changed_by ON amh.changed_by_user_id = changed_by.id
            ORDER BY amh.changed_at DESC';
    
    $stmt = $pdo->query($sql);
    $history = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
