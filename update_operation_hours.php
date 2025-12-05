<?php
/**
 * Update store operation hours
 * Area managers can update operation hours for stores in their area
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['area_manager', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$current_user = $_SESSION['user'];
$store_id = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
$operation_hours = isset($_POST['operation_hours']) ? trim($_POST['operation_hours']) : '';

if ($store_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid store ID']);
    exit;
}

try {
    // Verify store exists and belongs to area manager's area
    $storeStmt = $pdo->prepare('SELECT s.store_id, s.store_name, s.area_id FROM stores s WHERE s.store_id = ?');
    $storeStmt->execute([$store_id]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo json_encode(['success' => false, 'error' => 'Store not found']);
        exit;
    }
    
    // For area managers, verify the store is in their area
    if ($current_user['role'] === 'area_manager') {
        // Fetch latest area_id from database
        $userStmt = $pdo->prepare('SELECT area_id FROM users WHERE id = ?');
        $userStmt->execute([$current_user['id']]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        $user_area_id = $userData['area_id'] ?? null;
        
        if ($store['area_id'] != $user_area_id) {
            echo json_encode(['success' => false, 'error' => 'You can only update stores in your area']);
            exit;
        }
    }
    
    // Update operation hours
    $updateStmt = $pdo->prepare('UPDATE stores SET operation_hours = ?, updated_at = NOW() WHERE store_id = ?');
    $updateStmt->execute([$operation_hours, $store_id]);
    
    // Log the activity for IT tracking
    $logStmt = $pdo->prepare('
        INSERT INTO store_activity_logs (store_id, user_id, action_type, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    
    $details = json_encode([
        'action' => 'update_operation_hours',
        'store_name' => $store['store_name'],
        'operation_hours' => $operation_hours,
        'updated_by' => $current_user['username'],
        'user_role' => $current_user['role']
    ]);
    
    $logStmt->execute([$store_id, $current_user['id'], 'update', $details]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Operation hours updated successfully',
        'operation_hours' => $operation_hours
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
