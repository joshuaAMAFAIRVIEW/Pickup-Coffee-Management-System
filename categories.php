<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin','manager']);

// use $pdo from config.php
global $pdo;

// fetch categories and modifiers
$stmt = $pdo->query('SELECT c.id, c.name, cm.id AS mod_id, cm.label, cm.key_name
  FROM categories c
  LEFT JOIN category_modifiers cm ON cm.category_id = c.id
  ORDER BY c.name, cm.position');

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = [];
foreach ($rows as $r) {
  $id = $r['id'];
  if (!isset($categories[$id])) $categories[$id] = ['id'=>$id,'name'=>$r['name'],'modifiers'=>[]];
  if ($r['mod_id']) $categories[$id]['modifiers'][] = ['id'=>$r['mod_id'],'label'=>$r['label'],'key'=>$r['key_name']];
}

?>
<?php include 'dashboard_nav_wrapper_start.php'; ?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Categories</h1>
      <p class="text-muted mb-0">Manage equipment categories and their modifiers</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      <span class="me-1">+</span> Add Category
    </button>
  </div>

  <?php if (empty($categories)): ?>
    <div class="card shadow-sm border-0">
      <div class="card-body text-center py-5">
        <div class="mb-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
            <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
          </svg>
        </div>
        <h5 class="text-muted">No categories yet</h5>
        <p class="text-muted mb-0">Click "Add Category" to create your first equipment category</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title mb-0"><?php echo e($cat['name']); ?></h5>
                <a href="edit_category.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary">
                  Edit
                </a>
              </div>
              <?php if (!empty($cat['modifiers'])): ?>
                <div class="mb-2">
                  <small class="text-muted d-block mb-2">Modifiers:</small>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($cat['modifiers'] as $m): ?>
                      <span class="badge bg-light text-dark border"><?php echo e($m['label']); ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php else: ?>
                <small class="text-muted">No modifiers</small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" action="create_category.php" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label fw-semibold">Category Name</label>
            <input name="name" required class="form-control" placeholder="e.g. Laptop, Tablet, Monitor">
            <small class="text-muted">Enter a descriptive name for this equipment category</small>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Standard Modifiers</label>
            <p class="small text-muted mb-2">Select the fields you want to track for this category</p>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $standard = ['S/N','MODEL','IP','MAC','RAM','CPU','IMEI-1','IMEI-2'];
              foreach ($standard as $s):
              ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="modifiers[]" value="<?php echo e($s); ?>" id="mod_<?php echo e($s); ?>">
                  <label class="form-check-label" for="mod_<?php echo e($s); ?>"><?php echo e($s); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Custom Modifiers</label>
            <p class="small text-muted mb-2">Add any additional fields specific to this category</p>
            <div class="input-group">
              <input id="customModInput" class="form-control" placeholder="e.g. Warranty Expiry, Purchase Date">
              <button class="btn btn-outline-primary" type="button" id="addCustomModBtn">+ Add</button>
            </div>
            <div id="customMods" class="mt-3"></div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Category</button>
        </div>
      </form>
    </div>
  </div>

<?php include 'dashboard_nav_wrapper_end.php'; ?>

<script>
    document.getElementById('addCustomModBtn').addEventListener('click', function (){
      var v = document.getElementById('customModInput').value.trim();
      if (!v) return;
      // create a checkbox input appended to #customMods
      var id = 'custom_' + Date.now();
      var div = document.createElement('div');
      div.className = 'form-check';
      div.innerHTML = '<input class="form-check-input" type="checkbox" name="modifiers[]" value="'+v+'" id="'+id+'" checked> <label class="form-check-label" for="'+id+'">'+v+'</label>';
      document.getElementById('customMods').appendChild(div);
      document.getElementById('customModInput').value = '';
    });
</script>
