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
    
    // Get quantity (default to 1 for items without quantity tracking)
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    
    try {
      if ($hasItemsTable) {
        $ins = $pdo->prepare('INSERT INTO items (category_id, display_name, attributes, total_quantity, available_quantity, assigned_user_id) VALUES (:cid, :name, :attrs, :qty, :qty, NULL)');
        $ins->execute([':cid'=>$category_id,':name'=>$display_name,':attrs'=>$attributes_json,':qty'=>$quantity]);
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
    <div>
      <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
        📊 Bulk Import
      </button>
      <a href="inventory.php" class="btn btn-secondary">Back to Inventory</a>
    </div>
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
        <div class="mb-3">
          <label class="form-label">Quantity <small class="text-muted">(Optional - for bulk items like flash drives)</small></label>
          <input type="number" name="quantity" class="form-control" min="1" value="1" placeholder="Enter quantity (default: 1)">
          <small class="text-muted">For individual items (laptop, tablet), leave as 1. For bulk items (flash drives), enter total quantity.</small>
        </div>
        <div class="d-grid">
          <button class="btn btn-primary">Create Item</button>
        </div>
      </form>
    </div>
  </div>
<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

<!-- Bulk Import Modal -->
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">📊 Bulk Import from Google Sheets</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> Click "Sync & Preview" to load data from the Google Spreadsheet and preview what will be imported.
        </div>
        
        <!-- Preview Section -->
        <div id="previewSection" style="display: none;">
          <div class="alert alert-warning" id="previewSummary"></div>
          
          <!-- Tabs for Valid, Duplicates, Invalid -->
          <ul class="nav nav-tabs" id="previewTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="valid-tab" data-bs-toggle="tab" data-bs-target="#valid" type="button" role="tab">
                <span class="badge bg-success" id="validCount">0</span> Valid Items
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="duplicates-tab" data-bs-toggle="tab" data-bs-target="#duplicates" type="button" role="tab">
                <span class="badge bg-warning text-dark" id="duplicatesCount">0</span> Duplicates
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="invalid-tab" data-bs-toggle="tab" data-bs-target="#invalid" type="button" role="tab">
                <span class="badge bg-danger" id="invalidCount">0</span> Invalid
              </button>
            </li>
          </ul>
          
          <div class="tab-content" id="previewTabContent">
            <!-- Valid Items Tab -->
            <div class="tab-pane fade show active" id="valid" role="tabpanel">
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th>Row</th>
                      <th>Category</th>
                      <th>Item Name</th>
                      <th>S/N</th>
                      <th>Details</th>
                    </tr>
                  </thead>
                  <tbody id="validItemsBody"></tbody>
                </table>
              </div>
            </div>
            
            <!-- Duplicates Tab -->
            <div class="tab-pane fade" id="duplicates" role="tabpanel">
              <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle"></i> These items have duplicate S/N in the database and will NOT be imported.
              </div>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                  <thead class="table-light">
                    <tr>
                      <th>Row</th>
                      <th>Category</th>
                      <th>Item Name</th>
                      <th>S/N</th>
                    </tr>
                  </thead>
                  <tbody id="duplicatesBody"></tbody>
                </table>
              </div>
            </div>
            
            <!-- Invalid Tab -->
            <div class="tab-pane fade" id="invalid" role="tabpanel">
              <div class="alert alert-danger mt-3">
                <i class="fas fa-times-circle"></i> These rows have errors and cannot be imported.
              </div>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                  <thead class="table-light">
                    <tr>
                      <th>Row</th>
                      <th>Reason</th>
                    </tr>
                  </thead>
                  <tbody id="invalidBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Import Progress -->
        <div id="importProgress" style="display: none;">
          <div class="progress mb-3">
            <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
          </div>
          <div id="importStatus" class="small text-muted"></div>
        </div>
        
        <!-- Import Results -->
        <div id="importResults" style="display: none;">
          <h6>Import Results:</h6>
          <div id="importResultsContent"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="syncPreviewBtn" onclick="syncAndPreview()">
          <i class="fas fa-sync"></i> Sync & Preview
        </button>
        <button type="button" class="btn btn-success" id="confirmImportBtn" onclick="confirmImport()" style="display: none;">
          <i class="fas fa-check"></i> Confirm Import
        </button>
      </div>
    </div>
  </div>
</div>

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
  
  // Sync and Preview Function
  function syncAndPreview() {
    const syncBtn = document.getElementById('syncPreviewBtn');
    const confirmBtn = document.getElementById('confirmImportBtn');
    const previewSection = document.getElementById('previewSection');
    const progressDiv = document.getElementById('importProgress');
    const resultsDiv = document.getElementById('importResults');
    
    // Reset UI
    syncBtn.disabled = true;
    syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    confirmBtn.style.display = 'none';
    previewSection.style.display = 'none';
    progressDiv.style.display = 'none';
    resultsDiv.style.display = 'none';
    
    // Fetch preview data
    fetch('preview_bulk_import.php')
    .then(response => response.json())
    .then(data => {
      syncBtn.disabled = false;
      syncBtn.innerHTML = '<i class="fas fa-sync"></i> Sync & Preview';
      
      if (!data.success) {
        alert('Error: ' + data.message);
        return;
      }
      
      // Show preview section
      previewSection.style.display = 'block';
      
      // Update summary
      const summary = data.summary;
      document.getElementById('previewSummary').innerHTML = `
        <strong>Spreadsheet synced successfully!</strong><br>
        Found ${summary.total} valid items, ${summary.duplicates} duplicates, and ${summary.invalid} invalid rows.
      `;
      
      // Update counts
      document.getElementById('validCount').textContent = summary.total;
      document.getElementById('duplicatesCount').textContent = summary.duplicates;
      document.getElementById('invalidCount').textContent = summary.invalid;
      
      // Populate Valid Items
      const validBody = document.getElementById('validItemsBody');
      validBody.innerHTML = '';
      data.items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${item.row}</td>
          <td>${item.category}</td>
          <td>${item.name}</td>
          <td>${item.sn}</td>
          <td><small class="text-muted">${Object.keys(item.attributes).length} fields</small></td>
        `;
        validBody.appendChild(row);
      });
      
      // Populate Duplicates
      const duplicatesBody = document.getElementById('duplicatesBody');
      duplicatesBody.innerHTML = '';
      data.duplicates.forEach(dup => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${dup.row}</td>
          <td>${dup.category}</td>
          <td>${dup.name}</td>
          <td><span class="badge bg-warning text-dark">${dup.sn}</span></td>
        `;
        duplicatesBody.appendChild(row);
      });
      
      // Populate Invalid
      const invalidBody = document.getElementById('invalidBody');
      invalidBody.innerHTML = '';
      data.invalid.forEach(inv => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${inv.row}</td>
          <td>${inv.reason}</td>
        `;
        invalidBody.appendChild(row);
      });
      
      // Show confirm button if there are items to import
      if (summary.total > 0) {
        confirmBtn.style.display = 'inline-block';
      }
    })
    .catch(error => {
      syncBtn.disabled = false;
      syncBtn.innerHTML = '<i class="fas fa-sync"></i> Sync & Preview';
      alert('Error loading preview: ' + error.message);
    });
  }
  
  // Confirm Import Function
  function confirmImport() {
    const confirmBtn = document.getElementById('confirmImportBtn');
    const syncBtn = document.getElementById('syncPreviewBtn');
    const progressDiv = document.getElementById('importProgress');
    const resultsDiv = document.getElementById('importResults');
    const progressBar = document.getElementById('importProgressBar');
    const statusDiv = document.getElementById('importStatus');
    const resultsContent = document.getElementById('importResultsContent');
    const previewSection = document.getElementById('previewSection');
    
    // Confirm action
    if (!confirm('Are you sure you want to import these items? Duplicates will be skipped and successfully imported rows will be deleted from the spreadsheet.')) {
      return;
    }
    
    // Disable buttons
    confirmBtn.disabled = true;
    syncBtn.disabled = true;
    previewSection.style.display = 'none';
    
    // Show progress
    progressDiv.style.display = 'block';
    resultsDiv.style.display = 'none';
    progressBar.style.width = '0%';
    statusDiv.textContent = 'Importing items...';
    
    // Call the import script with confirmation
    fetch('bulk_import_items.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ confirmed: true })
    })
    .then(response => response.json())
    .then(data => {
      progressBar.style.width = '100%';
      progressBar.classList.remove('progress-bar-animated');
      
      if (data.success) {
        progressBar.classList.add('bg-success');
        statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Import completed!</span>';
      } else {
        progressBar.classList.add('bg-danger');
        statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Import failed: ' + (data.message || 'Unknown error') + '</span>';
      }
      
      // Show results
      resultsDiv.style.display = 'block';
      resultsContent.innerHTML = `
        <div class="alert alert-${data.success ? 'success' : 'warning'}">
          <strong>Total Rows Processed:</strong> ${data.total || 0}<br>
          <strong>Successfully Imported:</strong> ${data.imported || 0}<br>
          <strong>Skipped:</strong> ${data.skipped || 0}<br>
          <strong>Errors:</strong> ${data.errors || 0}
        </div>
        ${data.details ? '<div class="small"><strong>Details:</strong><br>' + data.details.join('<br>') + '</div>' : ''}
      `;
      
      confirmBtn.disabled = false;
      syncBtn.disabled = false;
      
      // Refresh page after 2 seconds if successful
      if (data.success && data.imported > 0) {
        setTimeout(() => {
          window.location.href = 'inventory.php';
        }, 2000);
      }
    })
    .catch(error => {
      progressBar.style.width = '100%';
      progressBar.classList.remove('progress-bar-animated');
      progressBar.classList.add('bg-danger');
      statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Error: ' + error.message + '</span>';
      confirmBtn.disabled = false;
      syncBtn.disabled = false;
    });
  }
</script>

