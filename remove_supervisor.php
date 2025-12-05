<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$current_user = $_SESSION['user'];

// Only area managers can remove supervisors
if ($current_user['role'] !== 'area_manager') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$supervisor_id = $input['supervisor_id'] ?? null;
$store_id = $input['store_id'] ?? null;
$reason = $input['reason'] ?? null; // 're-assign', 'resign', 'force_remove'
$equipment_conditions = $input['equipment_conditions'] ?? [];

if (!$supervisor_id || !$store_id || !$reason) {
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
    
    // Get assignment details
    $stmt = $pdo->prepare('
        SELECT ssa.id as assignment_id, u.full_name, u.employee_number, ssa.supervisor_role
        FROM supervisor_store_assignments ssa
        JOIN users u ON ssa.supervisor_user_id = u.id
        WHERE ssa.supervisor_user_id = ? AND ssa.store_id = ? AND ssa.is_active = 1
    ');
    $stmt->execute([$supervisor_id, $store_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        throw new Exception('Supervisor assignment not found');
    }
    
    // Record equipment conditions (OUT)
    if (!empty($equipment_conditions)) {
        $stmt = $pdo->prepare('
            INSERT INTO equipment_condition_logs (assignment_id, equipment_id, condition_type, condition_status, recorded_by, recorded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        
        foreach ($equipment_conditions as $eq) {
            $stmt->execute([
                $assignment['assignment_id'],
                $eq['equipment_id'],
                'out', // OUT condition
                $eq['condition'],
                $current_user['id']
            ]);
        }
    }
    
    // Mark supervisor assignment as inactive
    $stmt = $pdo->prepare('UPDATE supervisor_store_assignments SET is_active = 0 WHERE supervisor_user_id = ? AND store_id = ?');
    $stmt->execute([$supervisor_id, $store_id]);
    
    // Log activity
    $details = json_encode([
        'action' => 'remove_supervisor',
        'employee' => $assignment['full_name'] . ' (' . $assignment['employee_number'] . ')',
        'store' => $store['store_name'],
        'supervisor_role' => $assignment['supervisor_role'],
        'reason' => $reason,
        'equipment_count' => count($equipment_conditions),
        'removed_by' => $current_user['username']
    ]);
    
    $stmt = $pdo->prepare('
        INSERT INTO store_activity_logs (store_id, user_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$store_id, $current_user['id'], 'remove_supervisor', $details]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Supervisor removed successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
