<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$current_user = $_SESSION['user'];
$store_id = $_POST['store_id'] ?? null;
$store_name = trim($_POST['store_name'] ?? '');
$store_code = trim($_POST['store_code'] ?? '');
$area_id = $_POST['area_id'] ?? null;
$address = trim($_POST['address'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$contact_employee_number = trim($_POST['contact_employee_number'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$opening_date = $_POST['opening_date'] ?? null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Area manager can only update stores in their area
if ($current_user['role'] === 'area_manager') {
    $check_stmt = $pdo->prepare('SELECT area_id FROM stores WHERE store_id = ?');
    $check_stmt->execute([$store_id]);
    $store = $check_stmt->fetch();
    
    if (!$store || $store['area_id'] != $current_user['area_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You can only update stores in your area']);
        exit;
    }
    $area_id = $current_user['area_id']; // Force their area
}

if (empty($store_id) || empty($store_name) || empty($store_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Store ID, name, and code are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare('
        UPDATE stores 
        SET store_name = ?, 
            store_code = ?, 
            area_id = ?, 
            address = ?, 
            contact_person = ?, 
            contact_employee_number = ?,
            contact_number = ?, 
            opening_date = ?,
            is_active = ?
        WHERE store_id = ?
    ');
    $stmt->execute([
        $store_name,
        $store_code,
        $area_id ?: null,
        $address ?: null,
        $contact_person ?: null,
        $contact_employee_number ?: null,
        $contact_number ?: null,
        $opening_date ?: null,
        $is_active,
        $store_id
    ]);
    
    // Log the action
    $log_stmt = $pdo->prepare('
        INSERT INTO store_activity_logs (user_id, action_type, store_id, details)
        VALUES (?, ?, ?, ?)
    ');
    $log_details = json_encode([
        'store_name' => $store_name,
        'store_code' => $store_code,
        'area_id' => $area_id,
        'changes' => [
            'address' => $address,
            'contact_person' => $contact_person,
            'contact_number' => $contact_number,
            'opening_date' => $opening_date,
            'is_active' => $is_active
        ],
        'updated_by_role' => $current_user['role']
    ]);
    $log_stmt->execute([$current_user['id'], 'update_store', $store_id, $log_details]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Store updated successfully'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() == 23000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Store code already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
