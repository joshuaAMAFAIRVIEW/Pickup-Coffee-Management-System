<?php
/**
 * Get area manager activity logs with filters
 * Only accessible by IT/Admin
 */
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action_type = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : null;
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : null;

try {
    $sql = 'SELECT 
                sal.id,
                sal.user_id,
                sal.action_type,
                sal.store_id,
                sal.details,
                sal.created_at,
                u.username as user_username,
                u.first_name as user_first_name,
                u.last_name as user_last_name,
                s.store_name
            FROM store_activity_logs sal
            INNER JOIN users u ON sal.user_id = u.id
            LEFT JOIN stores s ON sal.store_id = s.store_id
            WHERE 1=1';
    
    $params = [];
    
    if ($user_id) {
        $sql .= ' AND sal.user_id = ?';
        $params[] = $user_id;
    }
    
    if ($action_type) {
        $sql .= ' AND sal.action_type = ?';
        $params[] = $action_type;
    }
    
    if ($date_from) {
        $sql .= ' AND DATE(sal.created_at) >= ?';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $sql .= ' AND DATE(sal.created_at) <= ?';
        $params[] = $date_to;
    }
    
    $sql .= ' ORDER BY sal.created_at DESC LIMIT 500';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
