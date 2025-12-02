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

$store_name = trim($_POST['store_name'] ?? '');
$store_code = trim($_POST['store_code'] ?? '');
$area_id = $_POST['area_id'] ?? null;
$address = trim($_POST['address'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$opening_date = $_POST['opening_date'] ?? null;

if (empty($store_name) || empty($store_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Store name and code are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO stores (store_name, store_code, area_id, address, contact_person, contact_number, opening_date, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ');
    $stmt->execute([
        $store_name,
        $store_code,
        $area_id ?: null,
        $address ?: null,
        $contact_person ?: null,
        $contact_number ?: null,
        $opening_date ?: null
    ]);
    
    $store_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'store_id' => $store_id,
        'message' => 'Store created successfully'
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Store code already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
