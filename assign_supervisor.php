<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$current_user = $_SESSION['user'];

// Only area managers can assign supervisors
if ($current_user['role'] !== 'area_manager') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$employee_id = $input['employee_id'] ?? null;
$store_id = $input['store_id'] ?? null;
$supervisor_role = $input['supervisor_role'] ?? null; // 'store_supervisor' or 'oic'
$equipment_conditions = $input['equipment_conditions'] ?? [];

if (!$employee_id || !$store_id || !$supervisor_role) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify store belongs to area manager's area
    $stmt = $pdo->prepare('
        SELECT s.store_id, s.store_name, s.area_id 
        FROM stores s
        WHERE s.store_id = ? AND s.area_id = (SELECT area_id FROM users WHERE id = ?)
    ');
    $stmt->execute([$store_id, $current_user['id']]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        throw new Exception('Store not found or not in your area');
    }
    
    // Verify employee exists and is in OPERATION department
    $stmt = $pdo->prepare('SELECT id, full_name, employee_number, role, department FROM users WHERE id = ?');
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    if (strtolower($employee['department']) !== 'operation') {
        throw new Exception('Employee must be in OPERATION department');
    }
    
    // Check if employee is already assigned to this store
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM supervisor_store_assignments WHERE supervisor_user_id = ? AND store_id = ? AND is_active = 1');
    $stmt->execute([$employee_id, $store_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Employee is already assigned to this store');
    }
    
    // Insert supervisor assignment
    $stmt = $pdo->prepare('
        INSERT INTO supervisor_store_assignments (supervisor_user_id, store_id, assigned_by_user_id, assigned_date, is_active, supervisor_role) 
        VALUES (?, ?, ?, NOW(), 1, ?)
    ');
    $stmt->execute([$employee_id, $store_id, $current_user['id'], $supervisor_role]);
    $assignment_id = $pdo->lastInsertId();
    
    // Record equipment conditions (IN)
    if (!empty($equipment_conditions)) {
        $stmt = $pdo->prepare('
            INSERT INTO equipment_condition_logs (assignment_id, equipment_id, condition_type, condition_status, recorded_by, recorded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        
        foreach ($equipment_conditions as $eq) {
            $stmt->execute([
                $assignment_id,
                $eq['equipment_id'],
                'in', // IN condition
                $eq['condition'],
                $current_user['id']
            ]);
        }
    }
    
    // Log activity
    $details = json_encode([
        'action' => 'assign_supervisor',
        'employee' => $employee['full_name'] . ' (' . $employee['employee_number'] . ')',
        'store' => $store['store_name'],
        'supervisor_role' => $supervisor_role,
        'equipment_count' => count($equipment_conditions),
        'assigned_by' => $current_user['username']
    ]);
    
    $stmt = $pdo->prepare('
        INSERT INTO store_activity_logs (store_id, user_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$store_id, $current_user['id'], 'assign_supervisor', $details]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Supervisor assigned successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
