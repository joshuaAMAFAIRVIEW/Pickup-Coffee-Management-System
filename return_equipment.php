<?php
/**
 * Return equipment from a user
 * Sets returned_at timestamp and updates item status to 'available'
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/google_sheets_logger.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$assignmentId = (int)($_POST['assignment_id'] ?? 0);
$returnCondition = trim($_POST['return_condition'] ?? 'perfectly-working');
$damageDetails = trim($_POST['damage_details'] ?? '');

if ($assignmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
    exit;
}

$pdo = $GLOBALS['pdo'];

try {
    $pdo->beginTransaction();
    
    // Get assignment details with user and item info
    $stmt = $pdo->prepare('
        SELECT ia.item_id, ia.user_id, ia.assigned_at, ia.notes,
               i.display_name, i.attributes,
               u.first_name, u.last_name, u.username, u.department, u.region,
               c.name as category_name
        FROM item_assignments ia
        JOIN items i ON ia.item_id = i.id
        JOIN users u ON ia.user_id = u.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE ia.id = :id AND ia.unassigned_at IS NULL
    ');
    $stmt->execute([':id' => $assignmentId]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        throw new Exception('Assignment not found or already returned');
    }
    
    // Update assignment with return date, condition, and damage details
    $updateAssignment = $pdo->prepare('
        UPDATE item_assignments 
        SET unassigned_at = NOW(), 
            return_condition = :return_condition,
            damage_details = :damage_details
        WHERE id = :id
    ');
    $updateAssignment->execute([
        ':id' => $assignmentId,
        ':return_condition' => $returnCondition,
        ':damage_details' => $damageDetails
    ]);
    
    // Update item status back to 'available', set condition to 'Re-Issue', and clear assigned_user_id
    $updateItem = $pdo->prepare('UPDATE items SET status = "available", item_condition = "Re-Issue", assigned_user_id = NULL WHERE id = :item_id');
    $updateItem->execute([':item_id' => $assignment['item_id']]);
    
    $pdo->commit();
    
    // Log to Google Sheets (Return tab)
    logToGoogleSheets('return', [
        'item_name' => $assignment['display_name'],
        'category' => $assignment['category_name'],
        'user_name' => trim($assignment['first_name'] . ' ' . $assignment['last_name']),
        'username' => $assignment['username'],
        'department' => $assignment['department'],
        'region' => $assignment['region'],
        'assigned_at' => $assignment['assigned_at'],
        'returned_at' => date('Y-m-d H:i:s'),
        'return_condition' => $returnCondition,
        'damage_details' => $damageDetails,
        'attributes' => $assignment['attributes']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Equipment returned successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
