<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // Get all categories with their available items
    $stmt = $pdo->query("
        SELECT id, name 
        FROM categories 
        ORDER BY name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get items for each category
    foreach ($categories as &$category) {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                display_name,
                attributes,
                total_quantity,
                available_quantity,
                status
            FROM items
            WHERE category_id = ?
            ORDER BY display_name ASC
        ");
        $stmt->execute([$category['id']]);
        $category['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
