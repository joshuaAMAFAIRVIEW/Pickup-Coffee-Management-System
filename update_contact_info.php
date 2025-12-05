<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['area_manager']);

header('Content-Type: application/json');

$current_user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
  exit();
}

$storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
$contactPerson = isset($_POST['contact_person']) ? trim($_POST['contact_person']) : '';
$contactEmployeeNumber = isset($_POST['contact_employee_number']) ? trim($_POST['contact_employee_number']) : '';
$contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';

// Validate store ID
if ($storeId <= 0) {
  echo json_encode(['success' => false, 'error' => 'Invalid store ID']);
  exit();
}

try {
  // Fetch latest area_id from database
  $userStmt = $pdo->prepare('SELECT area_id FROM users WHERE id = ?');
  $userStmt->execute([$current_user['id']]);
  $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
  $user_area_id = $userData['area_id'] ?? null;
  
  // Verify store belongs to the area manager's area
  $checkStmt = $pdo->prepare("
    SELECT s.store_id 
    FROM stores s 
    WHERE s.store_id = ? AND s.area_id = ?
  ");
  $checkStmt->execute([$storeId, $user_area_id]);
  
  if (!$checkStmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Store not found or not in your area']);
    exit();
  }
  
  // Update contact information
  $updateStmt = $pdo->prepare("
    UPDATE stores 
    SET contact_person = ?, contact_employee_number = ?, contact_number = ? 
    WHERE store_id = ?
  ");
  $updateStmt->execute([$contactPerson, $contactEmployeeNumber, $contactNumber, $storeId]);
  
  // Log activity
  $logStmt = $pdo->prepare("
    INSERT INTO store_activity_logs (store_id, user_id, action_type, details, created_at) 
    VALUES (?, ?, 'contact_info_updated', ?, NOW())
  ");
  
  $description = "Contact information updated";
  if ($contactPerson && $contactEmployeeNumber && $contactNumber) {
    $description .= ": $contactPerson (Emp: $contactEmployeeNumber, Tel: $contactNumber)";
  } elseif ($contactPerson && $contactEmployeeNumber) {
    $description .= ": $contactPerson (Emp: $contactEmployeeNumber)";
  } elseif ($contactPerson && $contactNumber) {
    $description .= ": $contactPerson (Tel: $contactNumber)";
  } elseif ($contactPerson) {
    $description .= ": $contactPerson";
  } else {
    $description .= ": cleared";
  }
  
  $logStmt->execute([$storeId, $current_user['id'], $description]);
  
  echo json_encode(['success' => true]);
  
} catch (PDOException $e) {
  error_log("Error updating contact info: " . $e->getMessage());
  echo json_encode(['success' => false, 'error' => 'Database error']);
}
