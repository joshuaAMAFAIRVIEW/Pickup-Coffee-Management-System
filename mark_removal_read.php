<?php
/**
 * Mark removal notification as read
 */
require_once __DIR__ . '/helpers.php';
require_role(['store_supervisor']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = $_SESSION['user'];
$removal_id = (int)($_POST['removal_id'] ?? 0);

if (empty($removal_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Removal ID is required']);
    exit;
}

try {
    // Verify this removal belongs to current user
    $stmt = $pdo->prepare('SELECT id FROM supervisor_removal_notifications WHERE id = ? AND supervisor_user_id = ?');
    $stmt->execute([$removal_id, $user['id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // Mark as read
    $stmt = $pdo->prepare('UPDATE supervisor_removal_notifications SET is_read = 1 WHERE id = ?');
    $stmt->execute([$removal_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
