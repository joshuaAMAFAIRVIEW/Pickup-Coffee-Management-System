<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // Validate input
    $storeId = $_POST['store_id'] ?? null;
    $receivedByUserId = $_POST['received_by_user_id'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $itemIdsJson = $_POST['item_ids'] ?? '[]';
    
    if (!$storeId) {
        throw new Exception('Store is required');
    }
    
    if (!$receivedByUserId) {
        throw new Exception('Received by employee is required');
    }
    
    $itemIds = json_decode($itemIdsJson, true);
    if (empty($itemIds)) {
        throw new Exception('At least one item must be selected');
    }
    
    $pdo->beginTransaction();
    
    // Generate release code
    $year = date('Y');
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM release_packages 
        WHERE YEAR(created_at) = $year
    ");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $packageCode = 'SR-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    
    // Check if code already exists (race condition check)
    $stmt = $pdo->prepare("SELECT package_id FROM release_packages WHERE package_code = ?");
    $stmt->execute([$packageCode]);
    if ($stmt->fetch()) {
        // Generate alternative code
        $packageCode = 'SR-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT) . '-' . uniqid();
    }
    
    // Insert release package
    $stmt = $pdo->prepare("
        INSERT INTO release_packages (
            package_code,
            store_id,
            package_status,
            prepared_by_user_id,
            received_by_user_id,
            prepared_date,
            total_items,
            notes
        ) VALUES (?, ?, 'delivered', ?, ?, CURDATE(), ?, ?)
    ");
    
    $stmt->execute([
        $packageCode,
        $storeId,
        $_SESSION['user_id'],
        $receivedByUserId,
        count($itemIds),
        $notes ?: null
    ]);
    
    $packageId = $pdo->lastInsertId();
    
    // Insert package items
    $stmt = $pdo->prepare("
        INSERT INTO release_package_items (package_id, item_id)
        VALUES (?, ?)
    ");
    
    foreach ($itemIds as $itemId) {
        $stmt->execute([$packageId, $itemId]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO store_activity_logs (
            store_id,
            user_id,
            activity_type,
            activity_details
        ) VALUES (?, ?, ?, ?)
    ");
    
    $activityDetails = json_encode([
        'action' => 'store_release_created',
        'release_code' => $packageCode,
        'package_id' => $packageId,
        'total_items' => count($itemIds),
        'received_by_user_id' => $receivedByUserId
    ]);
    
    $stmt->execute([
        $storeId,
        $_SESSION['user_id'],
        'store_release',
        $activityDetails
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'package_id' => $packageId,
        'package_code' => $packageCode,
        'message' => 'Store release created successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
