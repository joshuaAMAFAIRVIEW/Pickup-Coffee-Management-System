<?php
/**
 * Assign equipment to a user
 * Creates assignment record and updates item status to 'borrowed'
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/google_sheets_logger.php';

require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

// Debug: Log POST data
error_log("POST data: " . print_r($_POST, true));

$userId = (int)($_POST['user_id'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$itemCondition = $_POST['item_condition'] ?? 'Brand New';

error_log("userId: $userId, itemId: $itemId, notes: $notes, condition: $itemCondition");

if ($userId <= 0 || $itemId <= 0) {
    $_SESSION['flash_error'] = 'Invalid user or equipment selected';
    header('Location: users.php');
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $pdo->beginTransaction();
    
    // Check if item is available and not damaged
    $checkStmt = $pdo->prepare('SELECT i.status, i.display_name, i.attributes, c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id = c.id WHERE i.id = :item_id');
    $checkStmt->execute([':item_id' => $itemId]);
    $item = $checkStmt->fetch();
    
    if (!$item) {
        throw new Exception('Equipment not found');
    }
    
    if ($item['status'] === 'damaged' || $item['status'] === 'to be repair') {
        throw new Exception('Equipment is damaged and cannot be assigned. Please repair it first.');
    }
    
    if ($item['status'] !== 'available') {
        throw new Exception('Equipment is not available');
    }
    
    // Get user details
    $userStmt = $pdo->prepare('SELECT first_name, last_name, username, department, region FROM users WHERE id = :user_id');
    $userStmt->execute([':user_id' => $userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Check if item is already assigned to someone
    $assignedStmt = $pdo->prepare('SELECT id FROM item_assignments WHERE item_id = :item_id AND unassigned_at IS NULL');
    $assignedStmt->execute([':item_id' => $itemId]);
    if ($assignedStmt->fetch()) {
        throw new Exception('Equipment is already assigned to another user');
    }
    
    // Create assignment record
    $insertStmt = $pdo->prepare('
        INSERT INTO item_assignments (user_id, item_id, assigned_at, notes) 
        VALUES (:user_id, :item_id, NOW(), :notes)
    ');
    $insertStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
        ':notes' => $notes
    ]);
    
    // Update item status to 'borrowed' and set condition and assigned_user_id
    $updateStmt = $pdo->prepare('UPDATE items SET status = "borrowed", item_condition = :condition, assigned_user_id = :user_id WHERE id = :item_id');
    $updateStmt->execute([
        ':item_id' => $itemId,
        ':condition' => $itemCondition,
        ':user_id' => $userId
    ]);
    
    $pdo->commit();
    
    // Log to Google Sheets (Release tab)
    logToGoogleSheets('release', [
        'item_name' => $item['display_name'],
        'category' => $item['category_name'],
        'user_name' => trim($user['first_name'] . ' ' . $user['last_name']),
        'username' => $user['username'],
        'department' => $user['department'],
        'region' => $user['region'],
        'assigned_at' => date('Y-m-d H:i:s'),
        'item_condition' => $itemCondition,
        'notes' => $notes,
        'attributes' => $item['attributes']
    ]);
    
    $_SESSION['flash_success'] = 'Equipment successfully assigned to user';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = 'Error assigning equipment: ' . $e->getMessage();
}

header('Location: users.php');
exit;
