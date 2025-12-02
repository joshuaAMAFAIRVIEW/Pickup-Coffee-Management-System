<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager', 'store_supervisor']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$current_user = $_SESSION['user'];
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$area_id = isset($_GET['area_id']) ? (int)$_GET['area_id'] : null;
$store_id = isset($_GET['store_id']) ? (int)$_GET['store_id'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

try {
    $query = '
        SELECT 
            h.history_id,
            h.user_id,
            u.username,
            u.full_name,
            h.from_store_id,
            fs.store_name as from_store_name,
            fa.area_name as from_area_name,
            fa.area_id as from_area_id,
            h.to_store_id,
            ts.store_name as to_store_name,
            ta.area_name as to_area_name,
            ta.area_id as to_area_id,
            h.changed_date,
            h.changed_by_user_id,
            cb.username as changed_by_username,
            cb.full_name as changed_by_name,
            h.reason,
            h.notes,
            h.created_at
        FROM store_supervisor_history h
        JOIN users u ON h.user_id = u.id
        LEFT JOIN stores fs ON h.from_store_id = fs.store_id
        LEFT JOIN areas fa ON fs.area_id = fa.area_id
        LEFT JOIN stores ts ON h.to_store_id = ts.store_id
        LEFT JOIN areas ta ON ts.area_id = ta.area_id
        LEFT JOIN users cb ON h.changed_by_user_id = cb.id
        WHERE 1=1
    ';
    
    $params = [];
    
    // Role-based filtering
    if ($current_user['role'] === 'store_supervisor') {
        // Store supervisors can only see their own history
        $query .= ' AND h.user_id = ?';
        $params[] = $current_user['id'];
    } elseif ($current_user['role'] === 'area_manager') {
        // Area managers see movements in their area (from OR to their area)
        $query .= ' AND (fa.area_id = ? OR ta.area_id = ?)';
        $params[] = $current_user['area_id'];
        $params[] = $current_user['area_id'];
    }
    // Admin sees all movements
    
    // Additional filters
    if ($user_id) {
        $query .= ' AND h.user_id = ?';
        $params[] = $user_id;
    }
    
    if ($area_id) {
        $query .= ' AND (fa.area_id = ? OR ta.area_id = ?)';
        $params[] = $area_id;
        $params[] = $area_id;
    }
    
    if ($store_id) {
        $query .= ' AND (h.from_store_id = ? OR h.to_store_id = ?)';
        $params[] = $store_id;
        $params[] = $store_id;
    }
    
    if ($from_date) {
        $query .= ' AND h.changed_date >= ?';
        $params[] = $from_date;
    }
    
    if ($to_date) {
        $query .= ' AND h.changed_date <= ?';
        $params[] = $to_date;
    }
    
    $query .= ' ORDER BY h.changed_date DESC, h.created_at DESC';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'movements' => $movements,
        'count' => count($movements)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
