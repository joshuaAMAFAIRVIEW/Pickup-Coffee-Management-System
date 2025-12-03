<?php
/**
 * Remove supervisor assignment from store
 * Only accessible by area managers and admins
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
$reason = trim($_POST['reason'] ?? '');

if (empty($supervisor_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Supervisor ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get supervisor details
    $stmt = $pdo->prepare('SELECT u.id, u.username, u.first_name, u.last_name, u.store_id, s.store_name, s.area_id 
                           FROM users u 
                           LEFT JOIN stores s ON u.store_id = s.store_id 
                           WHERE u.id = ? AND u.role = ?');
    $stmt->execute([$supervisor_id, 'store_supervisor']);
    $supervisor = $stmt->fetch();
    
    if (!$supervisor) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Supervisor not found']);
        exit;
    }
    
    if (!$supervisor['store_id']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Supervisor is not assigned to any store']);
        exit;
    }
    
    // If user is area manager, verify the store belongs to their area
    if ($current_user['role'] === 'area_manager') {
        if ($supervisor['area_id'] != $current_user['area_id']) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You can only remove supervisors from stores in your area']);
            exit;
        }
    }
    
    $old_store_id = $supervisor['store_id'];
    $old_store_name = $supervisor['store_name'];
    
    // Remove the assignment (set store_id to NULL)
    $stmt = $pdo->prepare('UPDATE users SET store_id = NULL WHERE id = ?');
    $stmt->execute([$supervisor_id]);
    
    // Create removal notification for the supervisor
    $stmt = $pdo->prepare('
        INSERT INTO supervisor_removal_notifications (supervisor_user_id, store_id, store_name, removed_by_user_id, reason)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $supervisor_id,
        $old_store_id,
        $old_store_name,
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
        'supervisor_name' => $supervisor['first_name'] . ' ' . $supervisor['last_name'],
        'supervisor_username' => $supervisor['username'],
        'store_name' => $old_store_name,
        'reason' => $reason,
        'action' => 'remove_supervisor_assignment',
        'performed_by_role' => $current_user['role']
    ]);
    
    $stmt->execute([
        $current_user['id'],
        'update_store',
        $old_store_id,
        $details
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Supervisor assignment removed successfully'
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
