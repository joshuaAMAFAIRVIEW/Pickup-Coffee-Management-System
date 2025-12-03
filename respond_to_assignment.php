<?php
/**
 * Store supervisor responds to assignment request
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['store_supervisor']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$request_id = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'accepted' or 'declined'

if (empty($request_id) || !in_array($action, ['accepted', 'declined'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get the request and verify it belongs to this supervisor
    $stmt = $pdo->prepare('
        SELECT * FROM supervisor_assignment_requests 
        WHERE id = ? AND supervisor_user_id = ? AND status = ?
    ');
    $stmt->execute([$request_id, $current_user['id'], 'pending']);
    $request = $stmt->fetch();
    
    if (!$request) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found or already processed']);
        exit;
    }
    
    // Update request status
    $stmt = $pdo->prepare('
        UPDATE supervisor_assignment_requests 
        SET status = ?, responded_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ');
    $stmt->execute([$action, $request_id]);
    
    // If accepted, add to supervisor_store_assignments (many-to-many)
    if ($action === 'accepted') {
        // Check if this assignment already exists
        $checkStmt = $pdo->prepare('
            SELECT id FROM supervisor_store_assignments 
            WHERE supervisor_user_id = ? AND store_id = ? AND is_active = 1
        ');
        $checkStmt->execute([$current_user['id'], $request['store_id']]);
        
        if (!$checkStmt->fetch()) {
            // Add new store assignment
            $stmt = $pdo->prepare('
                INSERT INTO supervisor_store_assignments 
                (supervisor_user_id, store_id, assigned_by_user_id)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $current_user['id'],
                $request['store_id'],
                $request['area_manager_id']
            ]);
        }
        
        // Also update user's store_id for backward compatibility (use the latest accepted store)
        $stmt = $pdo->prepare('UPDATE users SET store_id = ? WHERE id = ?');
        $stmt->execute([$request['store_id'], $current_user['id']]);
        
        // Update session
        $_SESSION['user']['store_id'] = $request['store_id'];
        
        $_SESSION['success_message'] = 'Assignment accepted! You are now assigned to this store.';
    } else {
        $_SESSION['success_message'] = 'Assignment request declined.';
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'accepted' ? 'Assignment accepted' : 'Request declined'
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
