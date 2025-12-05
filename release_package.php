<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
  .select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    min-height: 38px;
  }
  .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  }
  .package-status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    font-weight: 600;
  }
  .filter-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }
</style>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4"><i class="fas fa-dolly"></i> Store Equipment Release</h1>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="packageTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="release-tab" data-bs-toggle="tab" data-bs-target="#release-pane" type="button" role="tab">
      <i class="fas fa-plus-circle"></i> New Release
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
      <i class="fas fa-history"></i> Release History
    </button>
  </li>
</ul>

<div class="tab-content" id="packageTabsContent">
  
  <!-- NEW RELEASE TAB -->
  <div class="tab-pane fade show active" id="release-pane" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header" style="background: url('assets/img/backgound-login.jpg') center/cover; color: white; position: relative;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(170, 194, 127, 0.85);"></div>
        <h5 class="mb-0" style="position: relative; z-index: 1;"><i class="fas fa-dolly"></i> Create New Store Equipment Release</h5>
      </div>
      <div class="card-body">
        <form id="releasePackageForm">
          
          <!-- Store and Employee Info -->
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label"><i class="fas fa-store text-primary"></i> Destination Store *</label>
              <select class="form-select" id="releaseStoreId" required>
                <option value="">Select store...</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="fas fa-user text-primary"></i> Received By (Employee Number) *</label>
              <div class="input-group">
                <input type="text" class="form-control" id="releaseEmployeeNumber" placeholder="Enter employee number" required>
                <button type="button" class="btn btn-outline-secondary" onclick="verifyEmployee()">
                  <i class="fas fa-search"></i> Verify
                </button>
              </div>
              <small class="text-muted">Employee must have an account in the system</small>
              <div id="employeeVerifyResult" class="mt-2"></div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label"><i class="fas fa-sticky-note text-primary"></i> Notes</label>
            <textarea class="form-control" id="releaseNotes" rows="2" placeholder="Add any notes about this release..."></textarea>
          </div>

          <hr class="my-4">

          <!-- Item Selection by Category -->
          <div class="mb-3">
            <h5 class="text-primary"><i class="fas fa-boxes"></i> Select Equipment Items by Category</h5>
            <p class="text-muted small mb-3">Select items from each category. Click the ✕ button to remove unwanted categories.</p>
          </div>

          <div id="categoriesContainer">
            <!-- Categories will be loaded here -->
            <div class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-3" id="selectedItemsSummary" style="display: none;">
            <strong><i class="fas fa-info-circle"></i> Selected Items:</strong>
            <span id="selectedItemsCount">0</span> item(s) selected
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4">
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
              <i class="fas fa-redo"></i> Reset Form
            </button>
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
              <i class="fas fa-paper-plane"></i> Create Store Release
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

  <!-- HISTORY TAB -->
  <div class="tab-pane fade" id="history-pane" role="tabpanel">
    
    <!-- Filter Section -->
    <div class="filter-section">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label small fw-bold">Store</label>
          <select class="form-select form-select-sm" id="filterStore">
            <option value="">All Stores</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold">Status</label>
          <select class="form-select form-select-sm" id="filterStatus">
            <option value="">All Status</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
            <option value="in_transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold">From Date</label>
          <input type="date" class="form-control form-control-sm" id="filterDateFrom">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold">To Date</label>
          <input type="date" class="form-control form-control-sm" id="filterDateTo">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Package Release History</h5>
        <button class="btn btn-outline-secondary btn-sm" onclick="exportHistory()">
          <i class="fas fa-download"></i> Export
        </button>
      </div>
      <div class="card-body">
        <div id="historyTableContainer">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading history...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- View Package Details Modal -->
<div class="modal fade" id="packageDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: url('assets/img/backgound-login.jpg') center/cover; color: white; position: relative;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(170, 194, 127, 0.85);"></div>
        <h5 class="modal-title" style="position: relative; z-index: 1;"><i class="fas fa-dolly"></i> Store Release Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position: relative; z-index: 1;"></button>
      </div>
      <div class="modal-body" id="packageDetailsContent">
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

<script>
let packageDetailsModal;
let verifiedEmployeeId = null;

document.addEventListener('DOMContentLoaded', function() {
  packageDetailsModal = new bootstrap.Modal(document.getElementById('packageDetailsModal'));
  
  loadStores();
  loadItemsForSelect2();
  loadReleaseHistory();
  $('#itemsSelect').on('change', function() {
    updateSelectedItemsSummary();
  });
  
  // Form submission
  document.getElementById('releasePackageForm').addEventListener('submit', handleFormSubmit);
});

async function loadStores() {
  try {
    const response = await fetch('get_stores.php?active_only=1');
    const data = await response.json();
    
    if (data.success) {
      const storeSelect = document.getElementById('releaseStoreId');
      const filterSelect = document.getElementById('filterStore');
      
      data.stores.forEach(store => {
        const option = document.createElement('option');
        option.value = store.store_id;
        option.textContent = `${store.store_name} (${store.store_code})`;
        storeSelect.appendChild(option.cloneNode(true));
        filterSelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading stores:', error);
  }
}

async function loadItemsForSelect2() {
  try {
    const response = await fetch('get_categories_items.php');
    const data = await response.json();
    
    if (data.success) {
      renderCategoryBoxes(data.categories);
    }
  } catch (error) {
    console.error('Error loading items:', error);
    document.getElementById('categoriesContainer').innerHTML = 
      '<div class="alert alert-danger">Error loading items</div>';
  }
}

function renderCategoryBoxes(categories) {
  const container = document.getElementById('categoriesContainer');
  container.innerHTML = '';
  
  categories.forEach((category, index) => {
    const availableItems = category.items.filter(item => item.status === 'available');
    
    if (availableItems.length === 0) return; // Skip categories with no available items
    
    const categoryBox = document.createElement('div');
    categoryBox.className = 'card mb-3 category-box';
    categoryBox.setAttribute('data-category-id', category.category_id);
    categoryBox.innerHTML = `
      <div class="card-header d-flex justify-content-between align-items-center" 
           style="background: url('assets/img/backgound-login.jpg') center/cover; color: white; position: relative;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(170, 194, 127, 0.85);"></div>
        <h6 class="mb-0" style="position: relative; z-index: 1;">
          <i class="fas fa-box"></i> ${category.category_name} 
          <span class="badge bg-light text-dark">${availableItems.length} available</span>
        </h6>
        <button type="button" class="btn btn-sm btn-light" onclick="removeCategory(${category.category_id})" 
                title="Remove this category" style="position: relative; z-index: 1;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="card-body">
        <select class="form-select category-select" multiple size="5" data-category-id="${category.category_id}">
          ${availableItems.map(item => `
            <option value="${item.item_id}">
              ${item.item_name} ${item.serial_number ? '(' + item.serial_number + ')' : ''} 
              [Available: ${item.available_count || 1}]
            </option>
          `).join('')}
        </select>
        <small class="text-muted d-block mt-2">
          <i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple items
        </small>
      </div>
    `;
    
    container.appendChild(categoryBox);
  });
  
  // Add change event listeners to all selects
  document.querySelectorAll('.category-select').forEach(select => {
    select.addEventListener('change', updateSelectedCount);
  });
  
  updateSelectedCount();
}

function removeCategory(categoryId) {
  const categoryBox = document.querySelector(`[data-category-id="${categoryId}"]`);
  if (categoryBox) {
    categoryBox.remove();
    updateSelectedCount();
  }
}

function updateSelectedCount() {
  const allSelects = document.querySelectorAll('.category-select');
  let totalSelected = 0;
  
  allSelects.forEach(select => {
    totalSelected += select.selectedOptions.length;
  });
  
  const summaryDiv = document.getElementById('selectedItemsSummary');
  const countSpan = document.getElementById('selectedItemsCount');
  const submitBtn = document.getElementById('submitBtn');
  
  countSpan.textContent = totalSelected;
  
  if (totalSelected > 0) {
    summaryDiv.style.display = 'block';
    if (verifiedEmployeeId) {
      submitBtn.disabled = false;
    }
  } else {
    summaryDiv.style.display = 'none';
    submitBtn.disabled = true;
  }
}

async function verifyEmployee() {
  const employeeNumber = document.getElementById('releaseEmployeeNumber').value.trim();
  const resultDiv = document.getElementById('employeeVerifyResult');
  const submitBtn = document.getElementById('submitBtn');
  
  if (!employeeNumber) {
    resultDiv.innerHTML = '<div class="alert alert-warning small">Please enter an employee number</div>';
    verifiedEmployeeId = null;
    submitBtn.disabled = true;
    return;
  }
  
  resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div>';
  
  try {
    const response = await fetch(`lookup_employee.php?employee_number=${encodeURIComponent(employeeNumber)}`);
    const data = await response.json();
    
    if (data.success && data.employee) {
      verifiedEmployeeId = data.employee.id;
      resultDiv.innerHTML = `
        <div class="alert alert-success small mb-0">
          <i class="fas fa-check-circle"></i> <strong>Verified:</strong> ${escapeHtml(data.employee.full_name)} 
          (${escapeHtml(data.employee.employee_number)}) - ${escapeHtml(data.employee.department)}
        </div>
      `;
      submitBtn.disabled = false;
    } else {
      verifiedEmployeeId = null;
      submitBtn.disabled = true;
      resultDiv.innerHTML = `
        <div class="alert alert-danger small mb-0">
          <i class="fas fa-times-circle"></i> Employee not found in system
        </div>
      `;
    }
  } catch (error) {
    console.error('Error:', error);
    verifiedEmployeeId = null;
    submitBtn.disabled = true;
    resultDiv.innerHTML = '<div class="alert alert-danger small mb-0">Error verifying employee</div>';
  }
}

function updateSelectedItemsSummary() {
  const selectedItems = $('#itemsSelect').val() || [];
  const summary = document.getElementById('selectedItemsSummary');
  const count = document.getElementById('selectedItemsCount');
  
  count.textContent = selectedItems.length;
  
  if (selectedItems.length > 0) {
    summary.style.display = 'block';
  } else {
    summary.style.display = 'none';
  }
}

async function handleFormSubmit(e) {
  e.preventDefault();
  
  // Collect all selected items from all category boxes
  const selectedItems = [];
  document.querySelectorAll('.category-select').forEach(select => {
    const selected = Array.from(select.selectedOptions).map(opt => opt.value);
    selectedItems.push(...selected);
  });
  
  if (selectedItems.length === 0) {
    alert('Please select at least one item');
    return;
  }
  
  if (!verifiedEmployeeId) {
    alert('Please verify the employee number first');
    return;
  }
  
  const storeId = document.getElementById('releaseStoreId').value;
  const notes = document.getElementById('releaseNotes').value;
  
  const formData = new FormData();
  formData.append('store_id', storeId);
  formData.append('received_by_user_id', verifiedEmployeeId);
  formData.append('notes', notes);
  formData.append('item_ids', JSON.stringify(selectedItems));
  
  try {
    const response = await fetch('create_package_release.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Store release created successfully!');
      resetForm();
      
      // Switch to history tab
      const historyTab = new bootstrap.Tab(document.getElementById('history-tab'));
      historyTab.show();
      loadReleaseHistory();
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error creating store release');
  }
}

function resetForm() {
  document.getElementById('releasePackageForm').reset();
  document.getElementById('employeeVerifyResult').innerHTML = '';
  document.getElementById('submitBtn').disabled = true;
  verifiedEmployeeId = null;
  loadItemsForSelect2(); // Reload all categories
  verifiedEmployeeId = null;
  updateSelectedItemsSummary();
}

async function loadReleaseHistory() {
  try {
    const response = await fetch('get_package_releases.php');
    const data = await response.json();
    
    if (data.success) {
      renderHistory(data.packages);
    }
  } catch (error) {
    console.error('Error loading history:', error);
    document.getElementById('historyTableContainer').innerHTML = 
      '<div class="alert alert-danger">Error loading history</div>';
  }
}

function renderHistory(packages) {
  const container = document.getElementById('historyTableContainer');
  
  if (packages.length === 0) {
    container.innerHTML = '<div class="alert alert-info">No releases found</div>';
    return;
  }
  
  let html = `
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Release Code</th>
            <th>Store</th>
            <th>Items</th>
            <th>Received By</th>
            <th>Prepared By</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  packages.forEach(pkg => {
    html += `
      <tr>
        <td><strong>${escapeHtml(pkg.package_code)}</strong></td>
        <td>${escapeHtml(pkg.store_name)}</td>
        <td><span class="badge bg-secondary">${pkg.total_items} items</span></td>
        <td>${pkg.received_by_name ? escapeHtml(pkg.received_by_name) : '-'}</td>
        <td>${escapeHtml(pkg.prepared_by_name)}</td>
        <td>${formatDate(pkg.created_at)}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="viewPackageDetails(${pkg.package_id})">
            <i class="fas fa-eye"></i> View
          </button>
        </td>
      </tr>
    `;
  });
  
  html += `
        </tbody>
      </table>
    </div>
  `;
  
  container.innerHTML = html;
}

async function viewPackageDetails(packageId) {
  try {
    const response = await fetch(`get_package_details.php?package_id=${packageId}`);
    const data = await response.json();
    
    if (data.success) {
      renderPackageDetails(data.package);
      packageDetailsModal.show();
    } else {
      alert('Error loading details');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error loading details');
  }
}

function renderPackageDetails(pkg) {
  const content = document.getElementById('packageDetailsContent');
  
  let html = `
    <div class="row mb-3">
      <div class="col-md-6">
        <strong>Release Code:</strong> ${escapeHtml(pkg.package_code)}
      </div>
      <div class="col-md-6">
        <strong>Total Items:</strong> ${pkg.total_items}
      </div>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-6">
        <strong>Store:</strong> ${escapeHtml(pkg.store_name)}
      </div>
      <div class="col-md-6">
        <strong>Date:</strong> ${formatDate(pkg.created_at)}
      </div>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-6">
        <strong>Prepared By:</strong> ${escapeHtml(pkg.prepared_by_name)}
      </div>
      <div class="col-md-6">
        <strong>Received By:</strong> ${pkg.received_by_name ? escapeHtml(pkg.received_by_name) : '-'}
      </div>
    </div>
    
    ${pkg.notes ? `<div class="mb-3"><strong>Notes:</strong><br>${escapeHtml(pkg.notes)}</div>` : ''}
    
    <hr>
    
    <h6 class="mb-3">Released Items:</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Attributes</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  pkg.items.forEach((item, index) => {
    html += `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(item.display_name)}</td>
        <td><span class="badge bg-secondary">${escapeHtml(item.category_name)}</span></td>
        <td>${item.attributes ? escapeHtml(item.attributes) : '-'}</td>
      </tr>
    `;
  });
  
  html += `
        </tbody>
      </table>
    </div>
  `;
  
  content.innerHTML = html;
}

function applyFilters() {
  loadReleaseHistory();
}

function exportHistory() {
  alert('Export functionality will be implemented');
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
