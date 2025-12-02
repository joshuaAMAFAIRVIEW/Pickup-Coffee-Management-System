<?php
/**
 * Change item status (for damaged items repair workflow)
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$newStatus = $_POST['new_status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

$allowedStatuses = ['damaged', 'to be repair', 'available'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $pdo->beginTransaction();
    
    // Get current item status
    $stmt = $pdo->prepare('SELECT status FROM items WHERE id = :item_id');
    $stmt->execute([':item_id' => $itemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    $oldStatus = $item['status'];
    
    // Update item status
    $updateStmt = $pdo->prepare('UPDATE items SET status = :new_status WHERE id = :item_id');
    $updateStmt->execute([
        ':new_status' => $newStatus,
        ':item_id' => $itemId
    ]);
    
    // Log status change to repair history
    $logStmt = $pdo->prepare('
        INSERT INTO item_repair_history (item_id, old_status, new_status, notes, changed_by)
        VALUES (:item_id, :old_status, :new_status, :notes, :changed_by)
    ');
    $logStmt->execute([
        ':item_id' => $itemId,
        ':old_status' => $oldStatus,
        ':new_status' => $newStatus,
        ':notes' => $notes,
        ':changed_by' => $_SESSION['user']['id']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Item status changed from '$oldStatus' to '$newStatus' successfully"
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
