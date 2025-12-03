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
    i.total_quantity,
    i.available_quantity,
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

// Area Manager: Only see items from stores in their area
if ($user['role'] === 'area_manager' && !empty($user['area_id'])) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM store_item_assignments sia
        INNER JOIN stores s ON sia.store_id = s.store_id
        WHERE sia.item_id = i.id AND s.area_id = :area_id
    )";
    $params[':area_id'] = $user['area_id'];
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
    .badge-damaged { background: #dc3545; }
    .badge-to-be-repair { background: #fd7e14; }
    .badge-maintenance { background: #dc3545; }
    .badge-retired { background: #6c757d; }
    .badge { 
      display: inline-block; 
      padding: 0.35em 0.65em; 
      font-size: 0.875em; 
      font-weight: 600; 
      line-height: 1; 
      color: #fff; 
      text-align: center; 
      white-space: nowrap; 
      vertical-align: baseline; 
      border-radius: 0.375rem; 
    }
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
                <th>Qty</th>
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
                <tr><td colspan="10" class="text-muted text-center py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  No items found. Add items from the "Add Item" page.
                </td></tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
                <?php 
                  $attrs = json_decode($item['attributes'], true) ?? [];
                  $serial = $attrs['S_N'] ?? $attrs['s_n'] ?? $attrs['SERIAL_NUMBER'] ?? $attrs['serial_number'] ?? $attrs['SN'] ?? $attrs['sn'] ?? 'N/A';
                  
                  // Status badge
                  $status = $item['status'] ?? 'available';
                  $statusClass = 'badge-' . str_replace(' ', '-', strtolower($status));
                  $statusText = ucwords($status);
                ?>
                <tr>
                  <td>
                    <strong>
                      <a href="#" class="text-decoration-none" 
                         data-bs-toggle="modal" 
                         data-bs-target="#itemHistoryModal"
                         onclick="viewItemHistory(<?php echo $item['id']; ?>); return false;">
                        <?php echo htmlspecialchars($item['display_name']); ?>
                      </a>
                    </strong>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($item['total_quantity'] > 1): ?>
                      <span class="badge bg-primary" title="<?php echo $item['available_quantity']; ?> available out of <?php echo $item['total_quantity']; ?>">
                        <?php echo $item['available_quantity']; ?>/<?php echo $item['total_quantity']; ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
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
                      <button class="btn btn-sm btn-outline-danger" 
                              onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['display_name'], ENT_QUOTES); ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (in_array($item['status'], ['damaged', 'to be repair'], true)): ?>
                      <button class="btn btn-sm btn-outline-info" 
                              data-bs-toggle="modal" 
                              data-bs-target="#changeStatusModal"
                              onclick="openChangeStatus(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['display_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['status'], ENT_QUOTES); ?>')">
                        <i class="fas fa-wrench"></i>
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

  <!-- Item History Modal -->
  <div class="modal fade" id="itemHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Item History: <span id="historyItemName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs mb-3" id="historyTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="borrower-tab" data-bs-toggle="tab" data-bs-target="#borrower-history" type="button" role="tab">
                <i class="fas fa-users"></i> Borrower History
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="modifier-tab" data-bs-toggle="tab" data-bs-target="#modifier-history" type="button" role="tab">
                <i class="fas fa-edit"></i> Modifier Edit History
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="repair-tab" data-bs-toggle="tab" data-bs-target="#repair-history" type="button" role="tab">
                <i class="fas fa-wrench"></i> Repair History
              </button>
            </li>
          </ul>
          <div class="tab-content" id="historyTabContent">
            <!-- Borrower History Tab -->
            <div class="tab-pane fade show active" id="borrower-history" role="tabpanel">
              <div id="borrowerHistoryContent" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modifier Edit History Tab -->
            <div class="tab-pane fade" id="modifier-history" role="tabpanel">
              <div id="modifierHistoryContent" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Repair History Tab -->
            <div class="tab-pane fade" id="repair-history" role="tabpanel">
              <div id="repairHistoryContent" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
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

  <!-- Change Status Modal -->
  <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Change Item Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="changeStatusItemId">
          <div class="alert alert-info">
            <i class="fas fa-wrench"></i> <strong id="changeStatusItemName"></strong><br>
            <small>Current Status: <span class="badge bg-secondary" id="changeStatusCurrentStatus"></span></small>
          </div>
          <div class="mb-3">
            <label for="newStatusSelect" class="form-label">New Status</label>
            <select class="form-select" id="newStatusSelect" required>
              <option value="">-- Select Status --</option>
              <option value="damaged">Damaged</option>
              <option value="to be repair">To Be Repair</option>
              <option value="available">Available</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="statusChangeNotes" class="form-label">Notes</label>
            <textarea class="form-control" id="statusChangeNotes" rows="3" placeholder="Enter details about status change..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitStatusChange()">
            <i class="fas fa-save"></i> Update Status
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Repair History Modal -->
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
    
    // View Item History Function
    function viewItemHistory(itemId) {
      document.getElementById('historyItemName').textContent = 'Loading...';
      
      // Load borrower history
      loadBorrowerHistory(itemId);
      
      // Load modifier history when tab is shown
      document.getElementById('modifier-tab').addEventListener('shown.bs.tab', function (event) {
        loadModifierHistory(itemId);
      }, { once: true });
      
      // Load repair history when tab is shown
      document.getElementById('repair-tab').addEventListener('shown.bs.tab', function (event) {
        loadRepairHistoryTab(itemId);
      }, { once: true });
    }
    
    // Load Borrower History
    function loadBorrowerHistory(itemId) {
      const content = document.getElementById('borrowerHistoryContent');
      content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_item_borrower_history.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('historyItemName').textContent = data.item_name;
            
            if (data.history.length === 0) {
              content.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No borrower history found. This item has never been assigned.</div>';
              return;
            }
            
            // Build timeline with all movements (assignments and returns)
            let html = '<div class="timeline-container" style="position: relative; padding-left: 30px;">';
            
            data.history.forEach((record, index) => {
              const isActive = !record.returned_at;
              const isFirst = index === 0;
              
              // Assignment Event
              html += `
                <div class="timeline-item mb-4" style="position: relative;">
                  <div class="timeline-marker" style="position: absolute; left: -30px; width: 20px; height: 20px; background: #28a745; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 0 3px #e9ecef;"></div>
                  <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <i class="fas fa-arrow-circle-right"></i> <strong>ASSIGNED</strong>
                          ${isActive ? '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock"></i> Currently Active</span>' : ''}
                        </div>
                        <small>${escapeHtml(record.assigned_at)}</small>
                      </div>
                    </div>
                    <div class="card-body">
                      <h6 class="mb-2"><i class="fas fa-user"></i> ${escapeHtml(record.user_name)}</h6>
                      <div class="row">
                        <div class="col-md-6">
                          <small><strong>Department:</strong> ${escapeHtml(record.department || 'N/A')}</small>
                        </div>
                        <div class="col-md-6">
                          <small><strong>Region:</strong> ${escapeHtml(record.region || 'N/A')}</small>
                        </div>
                      </div>
                      ${record.notes ? `<div class="mt-2"><small><strong>Notes:</strong> ${escapeHtml(record.notes)}</small></div>` : ''}
                    </div>
                  </div>
                </div>
              `;
              
              // Return Event (if returned)
              if (record.returned_at) {
                const conditionBadge = {
                  'perfectly-working': 'bg-success',
                  'minor-issue': 'bg-warning text-dark',
                  'damaged': 'bg-danger'
                }[record.return_condition] || 'bg-secondary';
                
                const conditionText = {
                  'perfectly-working': 'Perfectly Working',
                  'minor-issue': 'Minor Issue',
                  'damaged': 'Damaged'
                }[record.return_condition] || record.return_condition;
                
                // Calculate duration
                const assignedDate = new Date(record.assigned_at);
                const returnedDate = new Date(record.returned_at);
                const diffDays = Math.floor((returnedDate - assignedDate) / (1000 * 60 * 60 * 24));
                const durationText = diffDays > 0 ? `${diffDays} day${diffDays > 1 ? 's' : ''}` : 'Same day';
                
                html += `
                  <div class="timeline-item mb-4" style="position: relative;">
                    <div class="timeline-marker" style="position: absolute; left: -30px; width: 20px; height: 20px; background: #6c757d; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 0 3px #e9ecef;"></div>
                    <div class="card shadow-sm">
                      <div class="card-header bg-secondary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <i class="fas fa-arrow-circle-left"></i> <strong>RETURNED</strong>
                            <span class="badge bg-light text-dark ms-2"><i class="fas fa-clock"></i> Duration: ${durationText}</span>
                          </div>
                          <small>${escapeHtml(record.returned_at)}</small>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="mb-2">
                          <strong>Condition:</strong> <span class="badge ${conditionBadge}">${conditionText}</span>
                        </div>
                        ${record.damage_details ? `
                          <div class="alert alert-danger mb-0">
                            <strong><i class="fas fa-exclamation-triangle"></i> Damage Details:</strong><br>
                            ${escapeHtml(record.damage_details)}
                          </div>
                        ` : ''}
                      </div>
                    </div>
                  </div>
                `;
              }
              
              // Add connecting line (except for last item)
              if (index < data.history.length - 1 || isActive) {
                html += '<div style="position: absolute; left: -21px; width: 2px; height: 20px; background: #dee2e6;"></div>';
              }
            });
            
            html += '</div>';
            content.innerHTML = html;
          } else {
            content.innerHTML = '<div class="alert alert-danger">Error loading history: ' + escapeHtml(data.error) + '</div>';
          }
        })
        .catch(error => {
          content.innerHTML = '<div class="alert alert-danger">Error loading history</div>';
        });
    }
    
    // Load Modifier History
    function loadModifierHistory(itemId) {
      const content = document.getElementById('modifierHistoryContent');
      content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_item_modifier_history.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.history.length === 0) {
              content.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No modifier edit history found</div>';
              return;
            }
            
            let html = '<div class="timeline">';
            data.history.forEach((record, index) => {
              html += `
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <div class="d-flex justify-content-between">
                      <span><i class="fas fa-clock"></i> ${escapeHtml(record.changed_at)}</span>
                      <span class="badge bg-primary">${escapeHtml(record.changed_by)}</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row">
              `;
              
              // Show changes
              if (record.changes && record.changes.length > 0) {
                record.changes.forEach(change => {
                  html += `
                    <div class="col-md-6 mb-2">
                      <strong>${escapeHtml(change.field)}:</strong><br>
                      <span class="text-danger"><del>${escapeHtml(change.old_value || 'empty')}</del></span>
                      <i class="fas fa-arrow-right mx-2"></i>
                      <span class="text-success"><strong>${escapeHtml(change.new_value || 'empty')}</strong></span>
                    </div>
                  `;
                });
              } else {
                html += '<div class="col-12"><em class="text-muted">No changes recorded</em></div>';
              }
              
              html += `
                    </div>
                  </div>
                </div>
              `;
            });
            html += '</div>';
            content.innerHTML = html;
          } else {
            content.innerHTML = '<div class="alert alert-danger">Error loading modifier history: ' + escapeHtml(data.error) + '</div>';
          }
        })
        .catch(error => {
          content.innerHTML = '<div class="alert alert-danger">Error loading modifier history</div>';
        });
    }
    
    // Delete Item Function
    function deleteItem(itemId, itemName) {
      if (confirm('Are you sure you want to delete "' + itemName + '"? This action cannot be undone.')) {
        fetch('delete_item.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'item_id=' + itemId
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Item deleted successfully');
            window.location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          alert('Error deleting item');
        });
      }
    }
    
    // Open Change Status Modal
    function openChangeStatus(itemId, itemName, currentStatus) {
      document.getElementById('changeStatusItemId').value = itemId;
      document.getElementById('changeStatusItemName').textContent = itemName;
      document.getElementById('changeStatusCurrentStatus').textContent = currentStatus;
      document.getElementById('newStatusSelect').value = '';
      document.getElementById('statusChangeNotes').value = '';
    }
    
    // Submit Status Change
    function submitStatusChange() {
      const itemId = document.getElementById('changeStatusItemId').value;
      const newStatus = document.getElementById('newStatusSelect').value;
      const notes = document.getElementById('statusChangeNotes').value;
      
      if (!newStatus) {
        alert('Please select a new status');
        return;
      }
      
      const formData = new FormData();
      formData.append('item_id', itemId);
      formData.append('new_status', newStatus);
      formData.append('notes', notes);
      
      fetch('change_item_status.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Status updated successfully');
          bootstrap.Modal.getInstance(document.getElementById('changeStatusModal')).hide();
          window.location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error updating status');
      });
    }
    
    // Load Repair History Tab
    function loadRepairHistoryTab(itemId) {
      const content = document.getElementById('repairHistoryContent');
      content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      
      fetch('get_repair_history.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.history.length === 0) {
              content.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No repair history found</div>';
              return;
            }
            
            let html = '<div class="timeline">';
            data.history.forEach((record, index) => {
              html += `
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                      <span><i class="fas fa-clock"></i> ${escapeHtml(record.changed_at)}</span>
                      <span class="badge bg-primary">${escapeHtml(record.username || 'System')}</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                      <span class="badge bg-danger">${escapeHtml(record.old_status)}</span>
                      <i class="fas fa-arrow-right mx-3"></i>
                      <span class="badge bg-success">${escapeHtml(record.new_status)}</span>
                    </div>
                    ${record.notes ? '<p class="mb-0"><strong>Notes:</strong> ' + escapeHtml(record.notes) + '</p>' : '<p class="mb-0 text-muted"><em>No notes provided</em></p>'}
                  </div>
                </div>
              `;
            });
            html += '</div>';
            content.innerHTML = html;
          } else {
            content.innerHTML = '<div class="alert alert-danger">Error loading repair history: ' + escapeHtml(data.error) + '</div>';
          }
        })
        .catch(error => {
          content.innerHTML = '<div class="alert alert-danger">Error loading repair history</div>';
        });
    }
    
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
</body>
</html>

