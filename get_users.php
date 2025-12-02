<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$current_user = $_SESSION['user'];

try {
    $query = '
        SELECT 
            u.id,
            u.username,
            u.full_name,
            u.role,
            u.store_id,
            s.store_name,
            u.area_id,
            a.area_name,
            u.managed_by_user_id,
            m.username as manager_username,
            u.created_at
        FROM users u
        LEFT JOIN stores s ON u.store_id = s.store_id
        LEFT JOIN areas a ON u.area_id = a.area_id
        LEFT JOIN users m ON u.managed_by_user_id = m.id
        WHERE 1=1
    ';
    
    $params = [];
    
    // Filter by area for area managers
    if ($current_user['role'] === 'area_manager') {
        $query .= ' AND (u.area_id = ? OR u.managed_by_user_id = ?)';
        $params[] = $current_user['area_id'];
        $params[] = $current_user['id'];
    }
    
    $query .= ' ORDER BY u.role, u.full_name, u.username';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
