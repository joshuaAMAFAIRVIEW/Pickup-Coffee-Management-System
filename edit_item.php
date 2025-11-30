<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin','manager']);
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: inventory.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    header('Location: inventory.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $location = trim($_POST['location'] ?? '');

    if ($sku === '' || $name === '') {
        $errors[] = 'SKU and Name are required.';
    } else {
        $upd = $pdo->prepare('UPDATE inventory SET sku = ?, name = ?, quantity = ?, location = ? WHERE id = ?');
        try {
            $upd->execute([$sku, $name, $quantity, $location, $id]);
            header('Location: inventory.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Edit Item</h1>
    <a href="inventory.php" class="btn btn-secondary">Back to Inventory</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if ($errors): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">SKU</label>
          <input name="sku" class="form-control" required value="<?php echo e($_POST['sku'] ?? $item['sku']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" required value="<?php echo e($_POST['name'] ?? $item['name']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Quantity</label>
          <input name="quantity" type="number" class="form-control" min="0" value="<?php echo isset($_POST['quantity'])? (int)$_POST['quantity'] : (int)$item['quantity']; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Location</label>
          <input name="location" class="form-control" value="<?php echo e($_POST['location'] ?? $item['location']); ?>">
        </div>
        <div class="d-grid">
          <button class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
