<?php
/**
 * Reassign area manager to a different area
 * Only accessible by IT/Admin
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$area_manager_id = (int)($_POST['area_manager_id'] ?? 0);
$new_area_id = (int)($_POST['new_area_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (empty($area_manager_id) || empty($new_area_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Area manager ID and new area ID are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify the user is an area manager
    $stmt = $pdo->prepare('SELECT id, username, first_name, last_name, role, area_id FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$area_manager_id, 'area_manager']);
    $area_manager = $stmt->fetch();
    
    if (!$area_manager) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Area manager not found']);
        exit;
    }
    
    $old_area_id = $area_manager['area_id'];
    
    // Verify new area exists
    $stmt = $pdo->prepare('SELECT area_id, area_name FROM areas WHERE area_id = ?');
    $stmt->execute([$new_area_id]);
    $new_area = $stmt->fetch();
    
    if (!$new_area) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New area not found']);
        exit;
    }
    
    // Record in area_manager_history
    $stmt = $pdo->prepare('
        INSERT INTO area_manager_history (user_id, from_area_id, to_area_id, changed_by_user_id, reason)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $area_manager_id,
        $old_area_id,
        $new_area_id,
        $current_user['id'],
        $reason
    ]);
    
    // Update user's area_id
    $stmt = $pdo->prepare('UPDATE users SET area_id = ? WHERE id = ?');
    $stmt->execute([$new_area_id, $area_manager_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Area manager reassigned successfully',
        'old_area_id' => $old_area_id,
        'new_area_id' => $new_area_id
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
