<?php
/**
 * Return equipment from a user
 * Sets returned_at timestamp and updates item status to 'available'
 * Handles incident report photo upload
 */

// Disable all error output to prevent breaking JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Increase limits
set_time_limit(60);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/google_sheets_logger.php';

require_role(['admin', 'manager']);

// Prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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
    
    // Handle incident report photo upload
    $photoData = null;
    $photoMime = null;
    $photoUrl = null;
    if (isset($_FILES['incident_photo']) && $_FILES['incident_photo']['error'] === UPLOAD_ERR_OK) {
        // Check file size (max 2MB)
        if ($_FILES['incident_photo']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Photo is too large. Maximum size is 2MB. Please compress or resize the image.');
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['incident_photo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        // Read photo data
        $photoData = file_get_contents($_FILES['incident_photo']['tmp_name']);
        $photoMime = $_FILES['incident_photo']['type'];
        
        // Generate URL to serve the photo from database
        $photoUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/Pickup-Coffee-Management-System/Pickup-Coffee-Management-System/serve_photo.php?assignment_id=' . $assignmentId;
    }
    
    // Update assignment with return details and photo
    $updateAssignment = $pdo->prepare('
        UPDATE item_assignments 
        SET unassigned_at = NOW(),
            returned_at = NOW(),
            return_condition = :return_condition,
            damage_details = :damage_details,
            incident_photo = :incident_photo,
            incident_photo_mime = :incident_photo_mime
        WHERE id = :id
    ');
    $updateAssignment->execute([
        ':id' => $assignmentId,
        ':return_condition' => $returnCondition,
        ':damage_details' => $damageDetails,
        ':incident_photo' => $photoData,
        ':incident_photo_mime' => $photoMime
    ]);
    
    // Update item status - set to 'damaged' if returned as damaged, otherwise 'available'
    $itemStatus = ($returnCondition === 'damaged') ? 'damaged' : 'available';
    $updateItem = $pdo->prepare('UPDATE items SET status = :status, item_condition = "Re-Issue", assigned_user_id = NULL WHERE id = :item_id');
    $result = $updateItem->execute([
        ':status' => $itemStatus,
        ':item_id' => $assignment['item_id']
    ]);
    
    // Log status update for debugging
    error_log("Updated item {$assignment['item_id']} status to: {$itemStatus} (Result: " . ($result ? 'success' : 'failed') . ")");
    
    $pdo->commit();
    
    // Log to Google Sheets
    try {
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
            'incident_photo_url' => $photoUrl ?: '',
            'attributes' => $assignment['attributes']
        ]);
    } catch (Exception $sheetsError) {
        error_log('Google Sheets logging failed: ' . $sheetsError->getMessage());
    }
    
    // Send response
    echo json_encode([
        'success' => true, 
        'message' => 'Equipment returned successfully',
        'photo_url' => $photoUrl
    ]);
    exit;
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
