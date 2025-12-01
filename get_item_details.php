<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.display_name,
            i.attributes,
            i.status,
            i.created_at,
            c.name as category_name,
            u.username,
            u.department
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN users u ON i.assigned_user_id = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
        exit;
    }
    
    // Decode attributes
    $item['attributes'] = json_decode($item['attributes'], true) ?? [];
    
    echo json_encode(['success' => true, 'item' => $item]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
