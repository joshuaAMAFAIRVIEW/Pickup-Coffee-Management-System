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

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Categories</h1>
      <p class="text-muted mb-0">Manage equipment categories and their modifiers</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModifiersModal">
        <i class="fas fa-edit"></i> Edit Modifiers
      </button>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <span class="me-1">+</span> Add Category
      </button>
    </div>
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

  <!-- Edit Modifiers Modal -->
  <div class="modal fade" id="editModifiersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Modifiers</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Manage all modifiers used across categories. Changes here will affect all categories using these modifiers.</p>
          <div class="mb-3">
            <button class="btn btn-success" onclick="openAddModifierModal()">
              <i class="fas fa-plus"></i> Add New Modifier
            </button>
          </div>
          <div id="modifiersListContainer">
            <div class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Single Modifier Modal -->
  <div class="modal fade" id="editSingleModifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Modifier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editModifierId">
          <div class="mb-3">
            <label for="editModifierLabel" class="form-label">Modifier Label</label>
            <input type="text" class="form-control" id="editModifierLabel" placeholder="e.g. IMEI-1, Warranty Date">
          </div>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <small>Changing this will update the modifier in all categories that use it.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveModifierEdit()">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Modifier Modal -->
  <div class="modal fade" id="addNewModifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Modifier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="newModifierLabel" class="form-label">Modifier Label</label>
            <input type="text" class="form-control" id="newModifierLabel" placeholder="e.g. STORAGE, SCREEN SIZE">
          </div>
          <div class="mb-3">
            <label for="newModifierCategories" class="form-label">Assign to Categories</label>
            <div id="newModifierCategories" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
              <div class="text-center py-2">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading categories...</span>
                </div>
              </div>
            </div>
            <small class="text-muted">Select which categories should use this modifier</small>
          </div>
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <small>This modifier will be available for all selected categories.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" onclick="saveNewModifier()">
            <i class="fas fa-plus"></i> Add Modifier
          </button>
        </div>
      </div>
    </div>
  </div>

<?php include 'dashboard_nav_wrapper_end.php'; ?>

<script>
    // Load modifiers when Edit Modifiers modal is shown
    document.getElementById('editModifiersModal').addEventListener('show.bs.modal', function() {
      loadAllModifiers();
    });

    function loadAllModifiers() {
      const container = document.getElementById('modifiersListContainer');
      container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_all_modifiers.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.modifiers.length === 0) {
              container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No modifiers found. Create a category with modifiers first.</div>';
              return;
            }
            
            let html = '<div class="list-group">';
            data.modifiers.forEach(modifier => {
              html += `
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong>${escapeHtml(modifier.label)}</strong>
                      <small class="text-muted d-block">Key: ${escapeHtml(modifier.key_name)}</small>
                      <small class="text-muted">Used in: ${escapeHtml(modifier.categories)}</small>
                    </div>
                    <div>
                      <button class="btn btn-sm btn-outline-primary" onclick="editModifier(${modifier.id}, '${escapeHtml(modifier.label)}')">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                    </div>
                  </div>
                </div>
              `;
            });
            html += '</div>';
            container.innerHTML = html;
          } else {
            container.innerHTML = '<div class="alert alert-danger">Error loading modifiers: ' + escapeHtml(data.message) + '</div>';
          }
        })
        .catch(error => {
          container.innerHTML = '<div class="alert alert-danger">Error loading modifiers</div>';
        });
    }

    function editModifier(id, label) {
      document.getElementById('editModifierId').value = id;
      document.getElementById('editModifierLabel').value = label;
      
      // Hide the modifiers list modal and show edit modal
      const modifiersModal = bootstrap.Modal.getInstance(document.getElementById('editModifiersModal'));
      modifiersModal.hide();
      
      // Wait for modal to hide, then show edit modal
      document.getElementById('editModifiersModal').addEventListener('hidden.bs.modal', function() {
        const editModal = new bootstrap.Modal(document.getElementById('editSingleModifierModal'));
        editModal.show();
      }, { once: true });
    }

    function saveModifierEdit() {
      const id = document.getElementById('editModifierId').value;
      const newLabel = document.getElementById('editModifierLabel').value.trim();
      
      if (!newLabel) {
        alert('Modifier label cannot be empty');
        return;
      }
      
      // TODO: Implement update modifier API
      fetch('update_modifier.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&label=' + encodeURIComponent(newLabel)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Modifier updated successfully!');
          
          // Close edit modal
          const editModal = bootstrap.Modal.getInstance(document.getElementById('editSingleModifierModal'));
          editModal.hide();
          
          // Reopen modifiers list modal and reload
          document.getElementById('editSingleModifierModal').addEventListener('hidden.bs.modal', function() {
            const modifiersModal = new bootstrap.Modal(document.getElementById('editModifiersModal'));
            modifiersModal.show();
            loadAllModifiers();
          }, { once: true });
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error updating modifier');
      });
    }

    function openAddModifierModal() {
      // Close modifiers list modal
      const modifiersModal = bootstrap.Modal.getInstance(document.getElementById('editModifiersModal'));
      modifiersModal.hide();
      
      // Wait for modal to hide, then show add modal
      document.getElementById('editModifiersModal').addEventListener('hidden.bs.modal', function() {
        const addModal = new bootstrap.Modal(document.getElementById('addNewModifierModal'));
        addModal.show();
        
        // Load categories
        loadCategoriesForModifier();
      }, { once: true });
    }

    function loadCategoriesForModifier() {
      const container = document.getElementById('newModifierCategories');
      container.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_categories_list.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.categories.length > 0) {
            let html = '';
            data.categories.forEach(cat => {
              html += `
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="${cat.id}" id="cat_${cat.id}" name="category_ids[]">
                  <label class="form-check-label" for="cat_${cat.id}">
                    ${escapeHtml(cat.name)}
                  </label>
                </div>
              `;
            });
            container.innerHTML = html;
          } else {
            container.innerHTML = '<div class="alert alert-info">No categories available</div>';
          }
        })
        .catch(error => {
          container.innerHTML = '<div class="alert alert-danger">Error loading categories</div>';
        });
    }

    function saveNewModifier() {
      const label = document.getElementById('newModifierLabel').value.trim();
      const checkboxes = document.querySelectorAll('#newModifierCategories input[type="checkbox"]:checked');
      const categoryIds = Array.from(checkboxes).map(cb => cb.value);
      
      if (!label) {
        alert('Modifier label cannot be empty');
        return;
      }
      
      if (categoryIds.length === 0) {
        alert('Please select at least one category');
        return;
      }
      
      fetch('add_modifier.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          label: label,
          category_ids: categoryIds
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Modifier added successfully!');
          
          // Close add modal
          const addModal = bootstrap.Modal.getInstance(document.getElementById('addNewModifierModal'));
          addModal.hide();
          
          // Clear form
          document.getElementById('newModifierLabel').value = '';
          
          // Reopen modifiers list modal and reload
          document.getElementById('addNewModifierModal').addEventListener('hidden.bs.modal', function() {
            const modifiersModal = new bootstrap.Modal(document.getElementById('editModifiersModal'));
            modifiersModal.show();
            loadAllModifiers();
          }, { once: true });
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding modifier');
      });
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

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
