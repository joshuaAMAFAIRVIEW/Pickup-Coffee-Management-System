<?php
/**
 * Remove supervisor from a specific store (not all stores)
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$supervisor_id = (int)($_POST['supervisor_id'] ?? 0);
$store_id = (int)($_POST['store_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (empty($supervisor_id) || empty($store_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Supervisor ID and Store ID are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get supervisor and store details
    $stmt = $pdo->prepare('
        SELECT u.id, u.username, u.first_name, u.last_name, s.store_name, s.area_id
        FROM users u
        INNER JOIN supervisor_store_assignments ssa ON u.id = ssa.supervisor_user_id
        INNER JOIN stores s ON ssa.store_id = s.store_id
        WHERE u.id = ? AND ssa.store_id = ? AND ssa.is_active = 1 AND u.role = ?
    ');
    $stmt->execute([$supervisor_id, $store_id, 'store_supervisor']);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Assignment not found or already removed']);
        exit;
    }
    
    // Verify area manager can only remove from stores in their area
    if ($current_user['role'] === 'area_manager' && $assignment['area_id'] != $current_user['area_id']) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You can only remove supervisors from stores in your area']);
        exit;
    }
    
    // Deactivate the specific assignment
    $stmt = $pdo->prepare('
        UPDATE supervisor_store_assignments 
        SET is_active = 0 
        WHERE supervisor_user_id = ? AND store_id = ? AND is_active = 1
    ');
    $stmt->execute([$supervisor_id, $store_id]);
    
    // Create removal notification
    $stmt = $pdo->prepare('
        INSERT INTO supervisor_removal_notifications (supervisor_user_id, store_id, store_name, removed_by_user_id, reason)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $supervisor_id,
        $store_id,
        $assignment['store_name'],
        $current_user['id'],
        $reason
    ]);
    
    // Log the action
    $stmt = $pdo->prepare('
        INSERT INTO store_activity_logs (user_id, action_type, store_id, details)
        VALUES (?, ?, ?, ?)
    ');
    
    $details = json_encode([
        'supervisor_id' => $supervisor_id,
        'supervisor_name' => $assignment['first_name'] . ' ' . $assignment['last_name'],
        'supervisor_username' => $assignment['username'],
        'store_name' => $assignment['store_name'],
        'reason' => $reason,
        'action' => 'remove_supervisor_from_store',
        'performed_by_role' => $current_user['role']
    ]);
    
    $stmt->execute([
        $current_user['id'],
        'update_store',
        $store_id,
        $details
    ]);
    
    // Update user's store_id if this was their primary store
    $checkStmt = $pdo->prepare('SELECT store_id FROM users WHERE id = ?');
    $checkStmt->execute([$supervisor_id]);
    $userStoreId = $checkStmt->fetchColumn();
    
    if ($userStoreId == $store_id) {
        // Set to NULL or another active store
        $otherStoreStmt = $pdo->prepare('
            SELECT s.store_id 
            FROM supervisor_store_assignments ssa
            INNER JOIN stores s ON ssa.store_id = s.store_id
            WHERE ssa.supervisor_user_id = ? AND ssa.is_active = 1
            LIMIT 1
        ');
        $otherStoreStmt->execute([$supervisor_id]);
        $otherStore = $otherStoreStmt->fetchColumn();
        
        $updateUserStmt = $pdo->prepare('UPDATE users SET store_id = ? WHERE id = ?');
        $updateUserStmt->execute([$otherStore ?: null, $supervisor_id]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Supervisor removed from store successfully'
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
