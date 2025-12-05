<?php
/**
 * Get stores for area manager's dashboard
 * Returns stores with assigned supervisors
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['area_manager']);

header('Content-Type: application/json');

$current_user = $_SESSION['user'];

// Fetch the latest area_id from database (session might be stale)
try {
    $userStmt = $pdo->prepare('SELECT area_id FROM users WHERE id = ?');
    $userStmt->execute([$current_user['id']]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    $area_id = $userData['area_id'] ?? null;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error fetching user data']);
    exit;
}

if (empty($area_id)) {
    echo json_encode(['success' => false, 'error' => 'No area assigned']);
    exit;
}

try {
    // Get all stores in area manager's area
    $query = '
        SELECT 
            s.store_id,
            s.store_name,
            s.store_code,
            s.address,
            s.operation_hours,
            s.contact_person,
            s.contact_employee_number,
            s.contact_number,
            s.area_id,
            a.area_name
        FROM stores s
        INNER JOIN areas a ON s.area_id = a.area_id
        WHERE s.area_id = ?
        ORDER BY s.store_name
    ';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$area_id]);
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each store, get assigned supervisors
    foreach ($stores as &$store) {
        $supervisorQuery = '
            SELECT 
                u.id,
                u.employee_number,
                CONCAT(u.first_name, " ", COALESCE(u.middle_name, ""), " ", u.last_name) as full_name,
                u.email
            FROM supervisor_store_assignments ssa
            INNER JOIN users u ON ssa.supervisor_user_id = u.id
            WHERE ssa.store_id = ? 
            AND ssa.is_active = 1
            ORDER BY u.first_name
        ';
        $supervisorStmt = $pdo->prepare($supervisorQuery);
        $supervisorStmt->execute([$store['store_id']]);
        $store['supervisors'] = $supervisorStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
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
