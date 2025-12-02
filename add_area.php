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

$area_name = trim($_POST['area_name'] ?? '');

if (empty($area_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Area name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO areas (area_name, is_active) VALUES (?, 1)');
    $stmt->execute([$area_name]);
    
    $area_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'area_id' => $area_id,
        'message' => 'Area created successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
