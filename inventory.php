<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
$user = $_SESSION['user'];

$stmt = $pdo->query('SELECT * FROM inventory ORDER BY name ASC');
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventory - Inventory System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>table tbody tr:hover{background:#f8f9fa}</style>
</head>
<body>
<?php // ...existing code... ?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php' ?? ''; ?>

  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4">Inventory</h1>
      <?php if (in_array($user['role'], ['admin','manager'], true)): ?>
        <a href="add_item.php" class="btn btn-success">+ Add Item</a>
      <?php endif; ?>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead>
              <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Location</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$items): ?>
                <tr><td colspan="5" class="text-muted">No items found.</td></tr>
              <?php endif; ?>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?php echo htmlspecialchars($it['sku']); ?></td>
                  <td><?php echo htmlspecialchars($it['name']); ?></td>
                  <td><?php echo (int)$it['quantity']; ?></td>
                  <td><?php echo htmlspecialchars($it['location']); ?></td>
                  <td class="text-end">
                    <a href="edit_item.php?id=<?php echo $it['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <?php if (in_array($user['role'], ['admin','manager'], true)): ?>
                      <form method="post" action="delete_item.php" class="d-inline" onsubmit="return confirm('Delete this item?');">
                        <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php' ?? ''; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
