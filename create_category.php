<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin','manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: categories.php'); exit;
}

$name = trim($_POST['name'] ?? '');
$mods = $_POST['modifiers'] ?? [];

if ($name === '') {
  $_SESSION['flash_error'] = 'Category name required';
  header('Location: categories.php'); exit;
}

$pdo = $GLOBALS['pdo'];
try {
  // Check if category name already exists
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
  $stmt->execute([':name'=>$name]);
  if ($stmt->fetchColumn() > 0) {
    $_SESSION['error_message'] = 'Category "' . htmlspecialchars($name) . '" already exists. Please use a different name.';
    header('Location: categories.php'); exit;
  }

  $pdo->beginTransaction();
  $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
  $stmt->execute([':name'=>$name]);
  $catId = $pdo->lastInsertId();

  $pos = 0;
  $ins = $pdo->prepare('INSERT INTO category_modifiers (category_id, label, key_name, position) VALUES (:cid, :label, :key, :pos)');
  foreach ($mods as $m) {
    $label = trim($m);
    if ($label === '') continue;
    // create a key_name: lowercase, replace non-alphanum with underscore
    $key = preg_replace('/[^a-z0-9]+/','_', strtolower($label));
    $ins->execute([':cid'=>$catId,':label'=>$label,':key'=>$key,':pos'=>$pos++]);
  }
  $pdo->commit();
  $_SESSION['success_message'] = 'Category "' . htmlspecialchars($name) . '" created successfully!';
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  $_SESSION['error_message'] = 'Error creating category: '. $e->getMessage();
}
header('Location: categories.php'); exit;
