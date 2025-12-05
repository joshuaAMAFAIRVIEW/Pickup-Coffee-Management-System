<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // Get filters (optional)
    $storeId = $_GET['store_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    
    $sql = "
        SELECT 
            rp.*,
            s.store_name,
            s.store_code,
            u.full_name as prepared_by_name
        FROM release_packages rp
        JOIN stores s ON rp.store_id = s.store_id
        JOIN users u ON rp.prepared_by_user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($storeId) {
        $sql .= " AND rp.store_id = ?";
        $params[] = $storeId;
    }
    
    if ($status) {
        $sql .= " AND rp.package_status = ?";
        $params[] = $status;
    }
    
    if ($dateFrom) {
        $sql .= " AND rp.prepared_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND rp.prepared_date <= ?";
        $params[] = $dateTo;
    }
    
    $sql .= " ORDER BY rp.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'packages' => $packages
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
