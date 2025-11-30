<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_role(['admin','manager']);

// $pdo is provided by config.php
global $pdo;


// detect whether the new `items` table exists (migration applied)
try {
  $tblStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'items'");
  $tblStmt->execute([':db' => DB_NAME]);
  $hasItemsTable = (bool)$tblStmt->fetchColumn();
} catch (Exception $e) {
  $hasItemsTable = false;
}

$errors = [];
// handle POST - save into `items` table with JSON attributes if available;
// otherwise fall back to inserting into legacy `inventory` table.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category_id = (int)($_POST['category_id'] ?? 0);
  $display_name = trim($_POST['name'] ?? '');

  if (!$category_id || $display_name === '') {
    $errors[] = 'Category and Name are required.';
  } else {
    $attrs = $_POST['attr'] ?? [];
    if (!is_array($attrs)) $attrs = [];
    $attributes_json = json_encode($attrs);
    try {
      if ($hasItemsTable) {
        $ins = $pdo->prepare('INSERT INTO items (category_id, display_name, attributes, assigned_user_id) VALUES (:cid, :name, :attrs, NULL)');
        $ins->execute([':cid'=>$category_id,':name'=>$display_name,':attrs'=>$attributes_json]);
      } else {
        // legacy fallback: insert into `inventory` table. create a generated SKU.
        $sku = 'LEG-' . time();
        $stmt = $pdo->prepare('INSERT INTO inventory (sku, name, quantity, location) VALUES (:sku, :name, 0, NULL)');
        $stmt->execute([':sku'=>$sku, ':name'=>$display_name]);
      }
      header('Location: inventory.php'); exit;
    } catch (PDOException $e) {
      $errors[] = 'Error saving item: ' . $e->getMessage();
    }
  }
}

// fetch categories for select
$cats = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Add Item</h1>
    <a href="inventory.php" class="btn btn-secondary">Back to Inventory</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if ($errors): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Category</label>
          <select id="categorySelect" name="category_id" class="form-select" required>
            <option value="">-- choose category --</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="categoryFields"></div>
        <div class="mb-3">
          <label class="form-label">Item Name</label>
          <input name="name" class="form-control" required value="<?php echo isset($display_name)?e($display_name):''; ?>">
        </div>
        <div class="d-grid">
          <button class="btn btn-primary">Create Item</button>
        </div>
      </form>
    </div>
  </div>
<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

<script>
  // fetch modifiers for chosen category and render inputs
  document.getElementById('categorySelect').addEventListener('change', function (){
    var id = this.value;
    var target = document.getElementById('categoryFields');
    target.innerHTML = '';
    if (!id) return;
    fetch('get_category_modifiers.php?id=' + encodeURIComponent(id)).then(r=>r.json()).then(function (js){
      if (!js.success) return;
      var mods = js.modifiers || [];
      mods.forEach(function (m){
        var div = document.createElement('div');
        div.className = 'mb-3';
        var label = document.createElement('label'); label.className='form-label'; label.textContent = m.label;
        var input = document.createElement('input'); input.className='form-control'; input.name = 'attr['+m.key_name+']';
        input.type = 'text';
        div.appendChild(label); div.appendChild(input);
        target.appendChild(div);
      });
    });
  });
</script>
