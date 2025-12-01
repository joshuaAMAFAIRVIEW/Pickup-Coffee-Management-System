<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
$user = $_SESSION['user'];

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$department_filter = trim($_GET['department'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Build query with filters
$sql = "SELECT 
    i.id,
    i.display_name,
    i.attributes,
    i.status,
    i.item_condition,
    i.created_at,
    i.assigned_user_id,
    c.name as category_name,
    c.id as category_id,
    u.id as user_id,
    u.username,
    u.first_name,
    u.last_name,
    u.department
FROM items i
LEFT JOIN categories c ON i.category_id = c.id
LEFT JOIN users u ON i.assigned_user_id = u.id
WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (i.display_name LIKE :search OR i.attributes LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $sql .= " AND i.category_id = :category";
    $params[':category'] = $category_filter;
}

if ($department_filter !== '') {
    $sql .= " AND u.department = :department";
    $params[':department'] = $department_filter;
}

if ($status_filter !== '') {
    $sql .= " AND i.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get all categories for filter dropdown
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Get departments for filter
$departments = $pdo->query('SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Items - Inventory System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    table tbody tr:hover{background:#f8f9fa}
    .badge-available { background: #28a745; }
    .badge-borrowed { background: #ffc107; color: #000; }
    .badge-maintenance { background: #dc3545; }
    .badge-retired { background: #6c757d; }
    .filter-section { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .search-box { max-width: 400px; }
  </style>
</head>
<body>
<?php // ...existing code... ?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php' ?? ''; ?>

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

  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4"><i class="fas fa-box"></i> Equipment Items</h1>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="get" id="filterForm">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">
              <i class="fas fa-search"></i> Search (Name or S/N)
            </label>
            <input type="text" name="search" class="form-control" placeholder="Search by name or serial number..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="fas fa-tag"></i> Category
            </label>
            <select name="category" class="form-select">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="fas fa-building"></i> Department
            </label>
            <select name="department" class="form-select">
              <option value="">All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <?php if ($dept['department']): ?>
                  <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept['department']); ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="fas fa-info-circle"></i> Status
            </label>
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
              <option value="borrowed" <?php echo $status_filter == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
              <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
              <option value="retired" <?php echo $status_filter == 'retired' ? 'selected' : ''; ?>>Retired</option>
            </select>
          </div>
          
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
              <i class="fas fa-filter"></i> Filter
            </button>
            <a href="inventory.php" class="btn btn-secondary">
              <i class="fas fa-redo"></i> Reset
            </a>
          </div>
        </div>
      </form>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead>
              <tr>
                <th>Item Name</th>
                <th>Category</th>
                <th>S/N</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Department</th>
                <th>Added</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$items): ?>
                <tr><td colspan="9" class="text-muted text-center py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  No items found. Add items from the "Add Item" page.
                </td></tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
                <?php 
                  $attrs = json_decode($item['attributes'], true) ?? [];
                  $serial = $attrs['S_N'] ?? $attrs['s_n'] ?? $attrs['SERIAL_NUMBER'] ?? $attrs['serial_number'] ?? $attrs['SN'] ?? $attrs['sn'] ?? 'N/A';
                  
                  // Status badge
                  $statusClass = 'badge-' . $item['status'];
                  $statusText = ucfirst($item['status']);
                ?>
                <tr>
                  <td>
                    <strong><?php echo htmlspecialchars($item['display_name']); ?></strong>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                    </span>
                  </td>
                  <td><code><?php echo htmlspecialchars($serial); ?></code></td>
                  <td>
                    <?php if ($item['item_condition'] === 'Brand New'): ?>
                      <span class="badge bg-success"><i class="fas fa-star"></i> Brand New</span>
                    <?php else: ?>
                      <span class="badge bg-info text-dark"><i class="fas fa-recycle"></i> Re-Issue</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?php echo $statusClass; ?>">
                      <?php echo $statusText; ?>
                    </span>
                  </td>
                  <td>
                    <?php 
                      if ($item['assigned_user_id']) {
                        if ($item['username']) {
                          echo htmlspecialchars($item['username']);
                        } elseif ($item['first_name'] || $item['last_name']) {
                          echo htmlspecialchars(trim($item['first_name'] . ' ' . $item['last_name']));
                        } else {
                          echo 'User #' . $item['assigned_user_id'];
                        }
                      } else {
                        echo '-';
                      }
                    ?>
                  </td>
                  <td><?php echo $item['department'] ? htmlspecialchars($item['department']) : '-'; ?></td>
                  <td><small class="text-muted"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></small></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#viewItemModal"
                            onclick="viewItem(<?php echo $item['id']; ?>)">
                      <i class="fas fa-eye"></i>
                    </button>
                    <?php if (in_array($user['role'], ['admin','manager'], true)): ?>
                      <button class="btn btn-sm btn-outline-warning" 
                              data-bs-toggle="modal" 
                              data-bs-target="#updateModifiersModal"
                              data-item-id="<?php echo $item['id']; ?>"
                              data-item-name="<?php echo htmlspecialchars($item['display_name']); ?>"
                              data-category-id="<?php echo $item['category_id']; ?>"
                              data-attributes='<?php echo htmlspecialchars(json_encode(json_decode($item['attributes'], true) ?? []), ENT_QUOTES); ?>'>
                        <i class="fas fa-edit"></i>
                      </button>
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

  <!-- View Item Modal -->
  <div class="modal fade" id="viewItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Item Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="viewItemContent">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Modifiers Modal -->
  <div class="modal fade" id="updateModifiersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" action="update_item_modifiers.php" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Item Modifiers</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="item_id" id="updateModifiersItemId">
          <input type="hidden" name="category_id" id="updateModifiersCategoryId">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Updating modifiers for: <strong id="updateModifiersItemName"></strong>
          </div>
          <div id="modifierFields">
            <div class="text-center py-3">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-save"></i> Update Modifiers
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // View Item Details
    function viewItem(itemId) {
      const content = document.getElementById('viewItemContent');
      content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_item_details.php?id=' + itemId)
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            let html = '<div class="row">';
            html += '<div class="col-md-6"><p><strong>Item Name:</strong><br>' + escapeHtml(data.item.display_name) + '</p></div>';
            html += '<div class="col-md-6"><p><strong>Category:</strong><br>' + escapeHtml(data.item.category_name) + '</p></div>';
            html += '<div class="col-md-6"><p><strong>Status:</strong><br><span class="badge badge-' + data.item.status + '">' + data.item.status + '</span></p></div>';
            html += '<div class="col-md-6"><p><strong>Assigned To:</strong><br>' + (data.item.username || 'Not assigned') + '</p></div>';
            html += '</div><hr><h6>Attributes:</h6><div class="row">';
            
            const attrs = data.item.attributes || {};
            for (let key in attrs) {
              html += '<div class="col-md-6 mb-2"><small class="text-muted">' + key.replace(/_/g, ' ') + ':</small><br><strong>' + escapeHtml(attrs[key]) + '</strong></div>';
            }
            html += '</div>';
            content.innerHTML = html;
          } else {
            content.innerHTML = '<div class="alert alert-danger">Error loading item details</div>';
          }
        });
    }
    
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    // Update Modifiers Modal
    const updateModifiersModal = document.getElementById('updateModifiersModal');
    updateModifiersModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const itemId = button.getAttribute('data-item-id');
      const categoryId = button.getAttribute('data-category-id');
      const itemName = button.getAttribute('data-item-name');
      const attributes = JSON.parse(button.getAttribute('data-attributes') || '{}');
      
      document.getElementById('updateModifiersItemId').value = itemId;
      document.getElementById('updateModifiersCategoryId').value = categoryId;
      document.getElementById('updateModifiersItemName').textContent = itemName;
      
      // Load modifiers for category
      const fieldsContainer = document.getElementById('modifierFields');
      fieldsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_category_modifiers.php?id=' + encodeURIComponent(categoryId))
        .then(r => r.json())
        .then(function(js) {
          if (!js.success) {
            fieldsContainer.innerHTML = '<div class="alert alert-danger">Error loading modifiers</div>';
            return;
          }
          
          const mods = js.modifiers || [];
          fieldsContainer.innerHTML = '';
          
          if (mods.length === 0) {
            fieldsContainer.innerHTML = '<div class="alert alert-warning">No modifiers defined for this category</div>';
            return;
          }
          
          mods.forEach(function(m) {
            const div = document.createElement('div');
            div.className = 'mb-3';
            
            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = m.label;
            
            const input = document.createElement('input');
            input.className = 'form-control';
            input.name = 'attr[' + m.key_name + ']';
            input.type = 'text';
            input.value = attributes[m.key_name] || '';
            
            div.appendChild(label);
            div.appendChild(input);
            fieldsContainer.appendChild(div);
          });
        })
        .catch(function() {
          fieldsContainer.innerHTML = '<div class="alert alert-danger">Error loading modifiers</div>';
        });
    });
  </script>
</body>
</html>
