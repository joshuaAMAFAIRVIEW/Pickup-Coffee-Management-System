<?php
/**
 * Create supervisor assignment request
 * Area manager requests to assign a supervisor to a store
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'area_manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$supervisor_user_id = (int)($_POST['supervisor_user_id'] ?? 0);
$store_id = (int)($_POST['store_id'] ?? 0);
$reason = trim($_POST['reason'] ?? 'New Assignment');
$notes = trim($_POST['notes'] ?? '');
$force_reassign = isset($_POST['force_reassign']) && $_POST['force_reassign'] === 'true';

if (empty($supervisor_user_id) || empty($store_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Supervisor and store are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify supervisor exists and is a store_supervisor
    $stmt = $pdo->prepare('SELECT id, username, first_name, last_name, role, store_id FROM users WHERE id = ?');
    $stmt->execute([$supervisor_user_id]);
    $supervisor = $stmt->fetch();
    
    if (!$supervisor || $supervisor['role'] !== 'store_supervisor') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid supervisor']);
        exit;
    }
    
    // Verify store exists and belongs to area manager's area
    $stmt = $pdo->prepare('SELECT store_id, store_name, area_id FROM stores WHERE store_id = ?');
    $stmt->execute([$store_id]);
    $store = $stmt->fetch();
    
    if (!$store) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Store not found']);
        exit;
    }
    
    // Area manager can only assign to stores in their area
    if ($current_user['role'] === 'area_manager' && $store['area_id'] != $current_user['area_id']) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You can only assign supervisors to stores in your area']);
        exit;
    }
    
    // Check if this exact assignment already exists and is pending
    $checkStmt = $pdo->prepare('
        SELECT id FROM supervisor_assignment_requests 
        WHERE supervisor_user_id = ? AND store_id = ? AND status = ?
    ');
    $checkStmt->execute([$supervisor_user_id, $store_id, 'pending']);
    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'There is already a pending request for this supervisor to this store']);
        exit;
    }
    
    // Create assignment request (no conflict check - supervisors can have multiple stores)
    $stmt = $pdo->prepare('
        INSERT INTO supervisor_assignment_requests 
        (area_manager_id, supervisor_user_id, store_id, status, reason, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $current_user['id'],
        $supervisor_user_id,
        $store_id,
        'pending',
        $reason,
        $notes
    ]);
    
    $request_id = $pdo->lastInsertId();
    
    // Log the action
    $log_stmt = $pdo->prepare('
        INSERT INTO store_activity_logs (user_id, action_type, store_id, details)
        VALUES (?, ?, ?, ?)
    ');
    $log_details = json_encode([
        'request_id' => $request_id,
        'supervisor_id' => $supervisor_user_id,
        'supervisor_name' => trim($supervisor['first_name'] . ' ' . $supervisor['last_name']),
        'store_name' => $store['store_name'],
        'reason' => $reason,
        'notes' => $notes,
        'is_reassignment' => !empty($supervisor['store_id'])
    ]);
    $log_stmt->execute([$current_user['id'], 'assign_supervisor', $store_id, $log_details]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'request_id' => $request_id,
        'message' => 'Assignment request sent to supervisor successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
