<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$parent_area_id = $_POST['parent_area_id'] ?? null;
$new_areas = json_decode($_POST['new_areas'] ?? '[]', true);
$store_assignments = json_decode($_POST['store_assignments'] ?? '{}', true);

if (empty($parent_area_id) || empty($new_areas) || count($new_areas) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parent area ID and at least 2 new areas are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get current area manager for history
    $stmt = $pdo->prepare('SELECT id FROM users WHERE area_id = ? AND role = "area_manager" LIMIT 1');
    $stmt->execute([$parent_area_id]);
    $current_manager = $stmt->fetch();
    
    // Close area manager history for parent area
    if ($current_manager) {
        $stmt = $pdo->prepare('
            UPDATE area_manager_history 
            SET unassigned_date = CURDATE()
            WHERE user_id = ? AND area_id = ? AND unassigned_date IS NULL
        ');
        $stmt->execute([$current_manager['id'], $parent_area_id]);
    }
    
    // Deactivate parent area
    $stmt = $pdo->prepare('UPDATE areas SET is_active = 0 WHERE area_id = ?');
    $stmt->execute([$parent_area_id]);
    
    $new_area_ids = [];
    
    // Create new areas
    foreach ($new_areas as $new_area) {
        $area_name = trim($new_area['area_name'] ?? '');
        if (empty($area_name)) continue;
        
        $stmt = $pdo->prepare('
            INSERT INTO areas (area_name, parent_area_id, split_from_area_id, split_date, is_active)
            VALUES (?, ?, ?, CURDATE(), 1)
        ');
        $stmt->execute([$area_name, $parent_area_id, $parent_area_id]);
        
        $new_area_ids[$area_name] = $pdo->lastInsertId();
    }
    
    // Reassign stores to new areas
    foreach ($store_assignments as $store_id => $target_area_name) {
        if (!isset($new_area_ids[$target_area_name])) continue;
        
        $stmt = $pdo->prepare('UPDATE stores SET area_id = ? WHERE store_id = ?');
        $stmt->execute([$new_area_ids[$target_area_name], $store_id]);
    }
    
    // Unassign area manager from parent area
    if ($current_manager) {
        $stmt = $pdo->prepare('UPDATE users SET area_id = NULL WHERE id = ?');
        $stmt->execute([$current_manager['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Area split successfully',
        'new_area_ids' => $new_area_ids
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
