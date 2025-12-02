<?php
/**
 * Serve incident report photo from database
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

$assignmentId = (int)($_GET['assignment_id'] ?? 0);

if ($assignmentId <= 0) {
    http_response_code(400);
    echo 'Invalid assignment ID';
    exit;
}

try {
    // Fetch photo from database
    $stmt = $pdo->prepare('
        SELECT incident_photo, incident_photo_mime 
        FROM item_assignments 
        WHERE id = :assignment_id AND incident_photo IS NOT NULL
    ');
    $stmt->execute([':assignment_id' => $assignmentId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['incident_photo']) {
        http_response_code(404);
        echo 'Photo not found';
        exit;
    }
    
    // Set appropriate content type
    header('Content-Type: ' . ($result['incident_photo_mime'] ?: 'image/jpeg'));
    header('Content-Length: ' . strlen($result['incident_photo']));
    header('Cache-Control: public, max-age=86400'); // Cache for 1 day
    
    // Output photo data
    echo $result['incident_photo'];
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error loading photo';
}
