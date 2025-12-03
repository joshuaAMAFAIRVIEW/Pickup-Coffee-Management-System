<?php
/**
 * Get store supervisors for area manager's team management
 * Joins with stores to get area information
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$current_user = $_SESSION['user'];
$area_id = isset($_GET['area_id']) ? (int)$_GET['area_id'] : null;

// For area managers, use their area_id
if ($current_user['role'] === 'area_manager') {
    $area_id = $current_user['area_id'];
}

if (empty($area_id)) {
    echo json_encode(['success' => false, 'error' => 'Area ID is required']);
    exit;
}

try {
    // Get all supervisors and their assigned stores
    $query = '
        SELECT 
            u.id,
            u.employee_number,
            u.username,
            u.first_name,
            u.middle_name,
            u.last_name,
            CONCAT(u.first_name, " ", COALESCE(u.middle_name, ""), " ", u.last_name) as full_name,
            u.email,
            u.role,
            u.department,
            u.position,
            u.store_id,
            u.created_at
        FROM users u
        WHERE u.role = ?
        AND (
            u.id IN (
                SELECT DISTINCT ssa.supervisor_user_id 
                FROM supervisor_store_assignments ssa
                INNER JOIN stores s ON ssa.store_id = s.store_id
                WHERE s.area_id = ? AND ssa.is_active = 1
            )
            OR (u.store_id IS NULL AND u.department = ?)
        )
        ORDER BY u.first_name, u.last_name
    ';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['store_supervisor', $area_id, 'OPERATION']);
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each supervisor, get their assigned stores in this area
    foreach ($supervisors as &$supervisor) {
        $storeQuery = '
            SELECT s.store_id, s.store_name, s.store_code
            FROM supervisor_store_assignments ssa
            INNER JOIN stores s ON ssa.store_id = s.store_id
            WHERE ssa.supervisor_user_id = ? 
            AND s.area_id = ?
            AND ssa.is_active = 1
            ORDER BY s.store_name
        ';
        $storeStmt = $pdo->prepare($storeQuery);
        $storeStmt->execute([$supervisor['id'], $area_id]);
        $supervisor['assigned_stores'] = $storeStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'supervisors' => $supervisors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
