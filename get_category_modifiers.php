<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'error'=>'missing id']); exit; }

$stmt = $pdo->prepare('SELECT id,label,key_name,type,required FROM category_modifiers WHERE category_id = ? ORDER BY position');
$stmt->execute([$id]);
$mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'modifiers'=>$mods]);
