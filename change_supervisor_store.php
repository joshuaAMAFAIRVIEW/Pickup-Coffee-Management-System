<?php
require_once __DIR__ . '/helpers.php';
require_role(['store_supervisor', 'area_manager', 'admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $current_user['id'];
$new_store_id = isset($_POST['store_id']) ? (int)$_POST['store_id'] : null;
$reason = trim($_POST['reason'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (!$new_store_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Store ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get current user info
    $stmt = $pdo->prepare('SELECT id, role, store_id, area_id FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Permission checks
    $is_self = ($user_id == $current_user['id']);
    $is_admin = $current_user['role'] === 'admin';
    $is_area_manager = $current_user['role'] === 'area_manager';
    
    // Store supervisors can only change their own store
    if ($current_user['role'] === 'store_supervisor' && !$is_self) {
        throw new Exception('You can only change your own store assignment');
    }
    
    // Area managers can only change supervisors in their area
    if ($is_area_manager) {
        // Check if target user's current store is in area manager's area
        $areaCheck = $pdo->prepare('SELECT s.store_id FROM stores s 
                                     WHERE s.store_id = ? AND s.area_id = ?');
        $areaCheck->execute([$user['store_id'], $current_user['area_id']]);
        if (!$areaCheck->fetch()) {
            throw new Exception('You can only change supervisors in your area');
        }
        
        // Check if new store is also in their area
        $newStoreCheck = $pdo->prepare('SELECT store_id FROM stores WHERE store_id = ? AND area_id = ?');
        $newStoreCheck->execute([$new_store_id, $current_user['area_id']]);
        if (!$newStoreCheck->fetch()) {
            throw new Exception('You can only assign supervisors to stores in your area');
        }
    }
    
    $old_store_id = $user['store_id'];
    
    // Only create history if store is actually changing
    if ($old_store_id != $new_store_id) {
        // Record history
        $histStmt = $pdo->prepare('
            INSERT INTO store_supervisor_history 
            (user_id, from_store_id, to_store_id, changed_date, changed_by_user_id, reason, notes)
            VALUES (?, ?, ?, CURDATE(), ?, ?, ?)
        ');
        $histStmt->execute([
            $user_id,
            $old_store_id,
            $new_store_id,
            $current_user['id'],
            $reason,
            $notes
        ]);
        
        // Update user's store
        $updateStmt = $pdo->prepare('UPDATE users SET store_id = ? WHERE id = ?');
        $updateStmt->execute([$new_store_id, $user_id]);
        
        // Get store names for notification
        $storeStmt = $pdo->prepare('SELECT store_name FROM stores WHERE store_id IN (?, ?)');
        $storeStmt->execute([$old_store_id, $new_store_id]);
        $stores = $storeStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Store assignment updated successfully',
            'history_created' => true,
            'from_store' => $stores[0] ?? null,
            'to_store' => $stores[1] ?? null
        ]);
    } else {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'No changes made - same store',
            'history_created' => false
        ]);
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
