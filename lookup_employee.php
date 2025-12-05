<?php
/**
 * Lookup employee by employee number
 * Returns user details if found and validates role
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'area_manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$employee_number = trim($_GET['employee_number'] ?? '');

if (empty($employee_number)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Employee number is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT 
            u.id,
            u.employee_number,
            u.username,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.role,
            u.department,
            u.position,
            u.region,
            u.store_id,
            s.store_name,
            s.store_code,
            s.area_id as current_area_id
        FROM users u
        LEFT JOIN stores s ON u.store_id = s.store_id
        WHERE u.employee_number = ?
    ');
    $stmt->execute([$employee_number]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'Employee number not found',
            'error_type' => 'not_found'
        ]);
        exit;
    }
    
    // Check if user belongs to OPERATION department
    if (strtolower($user['department']) !== 'operation') {
        echo json_encode([
            'success' => false,
            'error' => 'This employee is not in OPERATION department. Only OPERATION employees can be assigned as store supervisors or OIC.',
            'error_type' => 'invalid_department',
            'user' => [
                'employee_number' => $user['employee_number'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'department' => $user['department'],
                'role' => $user['role']
            ]
        ]);
        exit;
    }
    
    // Check if already assigned to a store
    $is_assigned = !empty($user['store_id']);
    
    echo json_encode([
        'success' => true,
        'employee' => [
            'id' => $user['id'],
            'employee_number' => $user['employee_number'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'middle_name' => $user['middle_name'],
            'last_name' => $user['last_name'],
            'full_name' => trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']),
            'email' => $user['email'],
            'role' => $user['role'],
            'department' => $user['department'],
            'position' => $user['position'],
            'region' => $user['region'],
            'is_assigned' => $is_assigned,
            'current_store' => $is_assigned ? [
                'store_id' => $user['store_id'],
                'store_name' => $user['store_name'],
                'store_code' => $user['store_code'],
                'area_id' => $user['current_area_id']
            ] : null
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
