<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin','manager']);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: inventory.php');
    exit;
}

$del = $pdo->prepare('DELETE FROM inventory WHERE id = ?');
$del->execute([$id]);
header('Location: inventory.php');
exit;
