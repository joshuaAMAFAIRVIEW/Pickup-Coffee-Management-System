<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $packageId = $_GET['package_id'] ?? null;
    
    if (!$packageId) {
        throw new Exception('Package ID is required');
    }
    
    // Get package details
    $stmt = $pdo->prepare("
        SELECT 
            rp.*,
            s.store_name,
            s.store_code,
            u.full_name as prepared_by_name,
            u2.full_name as received_by_name
        FROM release_packages rp
        JOIN stores s ON rp.store_id = s.store_id
        JOIN users u ON rp.prepared_by_user_id = u.id
        LEFT JOIN users u2 ON rp.received_by_user_id = u2.id
        WHERE rp.package_id = ?
    ");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception('Package not found');
    }
    
    // Get package items
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.display_name,
            i.attributes,
            c.name as category_name
        FROM release_package_items rpi
        JOIN items i ON rpi.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        WHERE rpi.package_id = ?
        ORDER BY c.name, i.display_name
    ");
    $stmt->execute([$packageId]);
    $package['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'package' => $package
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
