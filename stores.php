<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager']);
require_once __DIR__ . '/config.php';
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

<style>
  .store-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  .area-split-item {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
  }
  .store-assignment-list {
    max-height: 300px;
    overflow-y: auto;
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
  <h1 class="h4"><i class="fas fa-store"></i> Stores Management</h1>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="storesTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="stores-tab" data-bs-toggle="tab" data-bs-target="#stores-pane" type="button" role="tab">
      <i class="fas fa-store"></i> Stores
    </button>
  </li>
  <?php if (user_has_role('admin')): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="areas-tab" data-bs-toggle="tab" data-bs-target="#areas-pane" type="button" role="tab">
      <i class="fas fa-map-marked-alt"></i> Areas
    </button>
  </li>
  <?php endif; ?>
</ul>

<div class="tab-content" id="storesTabsContent">
  
  <!-- STORES TAB -->
  <div class="tab-pane fade show active" id="stores-pane" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Stores</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStoreModal">
          <i class="fas fa-plus"></i> Add Store
        </button>
      </div>
      <div class="card-body">
        <div id="storesTableContainer">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- AREAS TAB -->
  <div class="tab-pane fade" id="areas-pane" role="tabpanel">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Geographic Areas</h5>
        <?php if (user_has_role('admin')): ?>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAreaModal">
            <i class="fas fa-plus"></i> Add Area
          </button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div id="areasTableContainer">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


</div>

<!-- Add Store Modal -->
<div class="modal fade" id="addStoreModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="addStoreForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Store</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Store Name <span class="text-danger">*</span></label>
            <input type="text" name="store_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Store Code <span class="text-danger">*</span></label>
            <input type="text" name="store_code" class="form-control" placeholder="e.g., ST-001" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Area</label>
            <select name="area_id" class="form-select" id="addStoreAreaSelect">
              <option value="">-- Select Area --</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Opening Date</label>
            <input type="date" name="opening_date" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact_number" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Create Store
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Store Modal -->
<div class="modal fade" id="editStoreModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="editStoreForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Store</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="store_id" id="editStoreId">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Store Name <span class="text-danger">*</span></label>
            <input type="text" name="store_name" id="editStoreName" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Store Code <span class="text-danger">*</span></label>
            <input type="text" name="store_code" id="editStoreCode" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Area</label>
            <select name="area_id" id="editStoreArea" class="form-select">
              <option value="">-- Select Area --</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Opening Date</label>
            <input type="date" name="opening_date" id="editStoreOpeningDate" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea name="address" id="editStoreAddress" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" id="editStoreContactPerson" class="form-control" maxlength="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Employee Number</label>
            <input type="text" name="contact_employee_number" id="editStoreContactEmployeeNumber" class="form-control" maxlength="50">
          </div>
          <div class="col-md-4">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact_number" id="editStoreContactNumber" class="form-control" maxlength="20">
          </div>
          <div class="col-12">
            <div class="form-check">
              <input type="checkbox" name="is_active" id="editStoreIsActive" class="form-check-input" value="1">
              <label class="form-check-label" for="editStoreIsActive">Active</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="viberContactBtn" style="display: none;" onclick="contactViaViber()">
          <i class="fab fa-viber"></i> Contact via Viber
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Add Area Modal -->
<div class="modal fade" id="addAreaModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="addAreaForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Area</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Area Name <span class="text-danger">*</span></label>
        <input type="text" name="area_name" class="form-control" placeholder="e.g., Area 1, Metro Manila, etc." required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Create Area
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Split Area Modal -->
<div class="modal fade" id="splitAreaModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form id="splitAreaForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-code-branch"></i> Split Area</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="splitParentAreaId" name="parent_area_id">
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> Splitting an area will deactivate the parent area and create new child areas. You'll need to reassign stores to the new areas.
        </div>
        
        <h6 class="mb-3">Parent Area: <strong id="splitParentAreaName"></strong></h6>
        
        <div class="mb-4">
          <label class="form-label fw-bold">New Areas</label>
          <div id="newAreasContainer"></div>
          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addNewAreaBtn">
            <i class="fas fa-plus"></i> Add Area
          </button>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-bold">Assign Stores to New Areas</label>
          <div id="storeAssignmentContainer" class="store-assignment-list">
            <!-- Will be populated dynamically -->
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">
          <i class="fas fa-code-branch"></i> Split Area
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let areas = [];
let stores = [];

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
  loadStores();
  loadAreas();
  
  // Tab change handlers
  document.getElementById('areas-tab').addEventListener('shown.bs.tab', loadAreas);
  document.getElementById('stores-tab').addEventListener('shown.bs.tab', loadStores);
});

// ========================================
// STORES FUNCTIONS
// ========================================

function loadStores() {
  const container = document.getElementById('storesTableContainer');
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  
  fetch('get_stores.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) throw new Error(data.error || 'Failed to load stores');
      
      stores = data.stores;
      renderStoresTable(stores);
    })
    .catch(err => {
      container.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    });
}

function renderStoresTable(stores) {
  const container = document.getElementById('storesTableContainer');
  
  if (stores.length === 0) {
    container.innerHTML = '<p class="text-muted">No stores found.</p>';
    return;
  }
  
  let html = `
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Code</th>
            <th>Store Name</th>
            <th>Area</th>
            <th>Contact</th>
            <th>Equipment</th>
            <th>Supervisors</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  stores.forEach(store => {
    const statusBadge = store.is_active == 1 
      ? '<span class="badge bg-success">Active</span>' 
      : '<span class="badge bg-secondary">Inactive</span>';
    
    const supervisorDisplay = store.supervisor_names 
      ? store.supervisor_names 
      : '<span class="text-muted">Unassigned</span>';
    
    html += `
      <tr>
        <td><code>${store.store_code}</code></td>
        <td><strong>${store.store_name}</strong></td>
        <td>${store.area_name || '-'}</td>
        <td>
          ${store.contact_person ? store.contact_person + '<br>' : ''}
          ${store.contact_number ? '<small class="text-muted">' + store.contact_number + '</small>' : '-'}
        </td>
        <td><span class="badge bg-info">${store.equipment_count} items</span></td>
        <td>${supervisorDisplay}</td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="openEditStoreModal(${store.store_id})" 
                  data-store-contact-number="${store.contact_number || ''}"
                  data-store-contact-person="${store.contact_person || ''}">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      </tr>
    `;
  });
  
  html += '</tbody></table></div>';
  container.innerHTML = html;
}

function openEditStoreModal(storeId) {
  const store = stores.find(s => s.store_id == storeId);
  if (!store) return;
  
  document.getElementById('editStoreId').value = store.store_id;
  document.getElementById('editStoreName').value = store.store_name;
  document.getElementById('editStoreCode').value = store.store_code;
  document.getElementById('editStoreAddress').value = store.address || '';
  document.getElementById('editStoreContactPerson').value = store.contact_person || '';
  document.getElementById('editStoreContactEmployeeNumber').value = store.contact_employee_number || '';
  document.getElementById('editStoreContactNumber').value = store.contact_number || '';
  document.getElementById('editStoreOpeningDate').value = store.opening_date || '';
  document.getElementById('editStoreIsActive').checked = store.is_active == 1;
  
  // Show/hide Viber button based on contact number
  const viberBtn = document.getElementById('viberContactBtn');
  if (store.contact_number && store.contact_number.trim()) {
    viberBtn.style.display = 'inline-block';
    viberBtn.setAttribute('data-contact-number', store.contact_number);
    viberBtn.setAttribute('data-contact-person', store.contact_person || 'Contact Person');
  } else {
    viberBtn.style.display = 'none';
  }
  
  // Populate areas dropdown
  populateAreaDropdown('editStoreArea', store.area_id);
  
  new bootstrap.Modal(document.getElementById('editStoreModal')).show();
}

function contactViaViber() {
  const viberBtn = document.getElementById('viberContactBtn');
  const contactNumber = viberBtn.getAttribute('data-contact-number');
  const contactPerson = viberBtn.getAttribute('data-contact-person');
  
  if (!contactNumber) {
    alert('No contact number available');
    return;
  }
  
  // Format number for Viber (remove spaces, dashes, and ensure it starts with +63)
  let formattedNumber = contactNumber.replace(/[\s\-\(\)]/g, '');
  
  // If number starts with 0, replace with +63
  if (formattedNumber.startsWith('0')) {
    formattedNumber = '+63' + formattedNumber.substring(1);
  }
  // If number doesn't start with +, assume Philippines and add +63
  else if (!formattedNumber.startsWith('+')) {
    formattedNumber = '+63' + formattedNumber;
  }
  
  // Create Viber deep link
  const viberUrl = `viber://chat?number=${encodeURIComponent(formattedNumber)}`;
  
  // Open Viber
  window.location.href = viberUrl;
}

// ========================================
// AREAS FUNCTIONS
// ========================================

function loadAreas() {
  const container = document.getElementById('areasTableContainer');
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  
  fetch('get_areas.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) throw new Error(data.error || 'Failed to load areas');
      
      areas = data.areas;
      renderAreasTable(areas);
      populateAreaDropdown('addStoreAreaSelect');
    })
    .catch(err => {
      container.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    });
}

function renderAreasTable(areas) {
  const container = document.getElementById('areasTableContainer');
  
  if (areas.length === 0) {
    container.innerHTML = '<p class="text-muted">No areas found.</p>';
    return;
  }
  
  let html = `
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Area Name</th>
            <th>Stores</th>
            <th>Managers</th>
            <th>Parent/Split From</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  areas.forEach(area => {
    const statusBadge = area.is_active == 1 
      ? '<span class="badge bg-success">Active</span>' 
      : '<span class="badge bg-secondary">Inactive</span>';
    
    let parentInfo = '-';
    if (area.parent_area_name || area.split_from_area_name) {
      parentInfo = area.split_from_area_name 
        ? `<small class="text-muted">Split from: ${area.split_from_area_name}</small>` 
        : `<small class="text-muted">Parent: ${area.parent_area_name}</small>`;
    }
    
    html += `
      <tr>
        <td><strong>${area.area_name}</strong></td>
        <td><span class="badge bg-info">${area.store_count} stores</span></td>
        <td><span class="badge bg-secondary">${area.manager_count}</span></td>
        <td>${parentInfo}</td>
        <td>${statusBadge}</td>
        <td>
          ${area.is_active == 1 && area.store_count > 0 ? `
            <button class="btn btn-sm btn-outline-warning" onclick="openSplitAreaModal(${area.area_id})">
              <i class="fas fa-code-branch"></i> Split
            </button>
          ` : ''}
        </td>
      </tr>
    `;
  });
  
  html += '</tbody></table></div>';
  container.innerHTML = html;
}

function populateAreaDropdown(selectId, selectedAreaId = null) {
  const select = document.getElementById(selectId);
  if (!select) return;
  
  const activeAreas = areas.filter(a => a.is_active == 1);
  
  select.innerHTML = '<option value="">-- Select Area --</option>';
  activeAreas.forEach(area => {
    const option = document.createElement('option');
    option.value = area.area_id;
    option.textContent = area.area_name;
    if (selectedAreaId && area.area_id == selectedAreaId) {
      option.selected = true;
    }
    select.appendChild(option);
  });
}

let newAreaCounter = 2;

function openSplitAreaModal(areaId) {
  const area = areas.find(a => a.area_id == areaId);
  if (!area) return;
  
  // Get stores in this area
  const areaStores = stores.filter(s => s.area_id == areaId);
  
  if (areaStores.length === 0) {
    alert('This area has no stores to reassign.');
    return;
  }
  
  document.getElementById('splitParentAreaId').value = area.area_id;
  document.getElementById('splitParentAreaName').textContent = area.area_name;
  
  // Reset and add 2 default new areas
  const container = document.getElementById('newAreasContainer');
  container.innerHTML = '';
  newAreaCounter = 2;
  
  addNewAreaInput(`${area.area_name}A`);
  addNewAreaInput(`${area.area_name}B`);
  
  // Populate store assignments
  renderStoreAssignments(areaStores, [`${area.area_name}A`, `${area.area_name}B`]);
  
  new bootstrap.Modal(document.getElementById('splitAreaModal')).show();
}

function addNewAreaInput(defaultName = '') {
  const container = document.getElementById('newAreasContainer');
  const areaDiv = document.createElement('div');
  areaDiv.className = 'area-split-item';
  areaDiv.innerHTML = `
    <div class="input-group">
      <span class="input-group-text">New Area ${newAreaCounter}</span>
      <input type="text" class="form-control new-area-name" value="${defaultName}" placeholder="Area name" required>
      <button type="button" class="btn btn-outline-danger" onclick="this.closest('.area-split-item').remove(); updateStoreAssignments();">
        <i class="fas fa-trash"></i>
      </button>
    </div>
  `;
  container.appendChild(areaDiv);
  newAreaCounter++;
}

document.getElementById('addNewAreaBtn').addEventListener('click', () => addNewAreaInput());

function renderStoreAssignments(areaStores, areaNames) {
  const container = document.getElementById('storeAssignmentContainer');
  container.innerHTML = '';
  
  areaStores.forEach(store => {
    const storeDiv = document.createElement('div');
    storeDiv.className = 'area-split-item';
    storeDiv.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <strong>${store.store_name}</strong>
          <br><small class="text-muted">${store.store_code}</small>
        </div>
        <select class="form-select form-select-sm w-auto store-area-select" data-store-id="${store.store_id}" required>
          ${areaNames.map((name, idx) => `<option value="${name}"${idx === 0 ? ' selected' : ''}>${name}</option>`).join('')}
        </select>
      </div>
    `;
    container.appendChild(storeDiv);
  });
}

function updateStoreAssignments() {
  const areaInputs = document.querySelectorAll('.new-area-name');
  const areaNames = Array.from(areaInputs).map(input => input.value.trim()).filter(name => name);
  
  const areaId = document.getElementById('splitParentAreaId').value;
  const areaStores = stores.filter(s => s.area_id == areaId);
  
  renderStoreAssignments(areaStores, areaNames);
}

// ========================================
// FORM SUBMISSIONS
// ========================================

document.getElementById('addStoreForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  
  try {
    const res = await fetch('add_store.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (!data.success) throw new Error(data.error);
    
    bootstrap.Modal.getInstance(document.getElementById('addStoreModal')).hide();
    e.target.reset();
    loadStores();
    alert('Store created successfully!');
  } catch (err) {
    alert('Error: ' + err.message);
  }
});

document.getElementById('editStoreForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  
  try {
    const res = await fetch('update_store.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (!data.success) throw new Error(data.error);
    
    bootstrap.Modal.getInstance(document.getElementById('editStoreModal')).hide();
    loadStores();
    alert('Store updated successfully!');
  } catch (err) {
    alert('Error: ' + err.message);
  }
});

document.getElementById('addAreaForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  
  try {
    const res = await fetch('add_area.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (!data.success) throw new Error(data.error);
    
    bootstrap.Modal.getInstance(document.getElementById('addAreaModal')).hide();
    e.target.reset();
    loadAreas();
    alert('Area created successfully!');
  } catch (err) {
    alert('Error: ' + err.message);
  }
});

document.getElementById('splitAreaForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const parentAreaId = document.getElementById('splitParentAreaId').value;
  
  // Collect new area names
  const areaInputs = document.querySelectorAll('.new-area-name');
  const newAreas = Array.from(areaInputs).map(input => ({ area_name: input.value.trim() })).filter(a => a.area_name);
  
  if (newAreas.length < 2) {
    alert('Please create at least 2 new areas.');
    return;
  }
  
  // Collect store assignments
  const storeSelects = document.querySelectorAll('.store-area-select');
  const storeAssignments = {};
  storeSelects.forEach(select => {
    storeAssignments[select.dataset.storeId] = select.value;
  });
  
  const formData = new FormData();
  formData.append('parent_area_id', parentAreaId);
  formData.append('new_areas', JSON.stringify(newAreas));
  formData.append('store_assignments', JSON.stringify(storeAssignments));
  
  try {
    const res = await fetch('split_area.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (!data.success) throw new Error(data.error);
    
    bootstrap.Modal.getInstance(document.getElementById('splitAreaModal')).hide();
    loadAreas();
    loadStores();
    alert('Area split successfully!');
  } catch (err) {
    alert('Error: ' + err.message);
  }
});
</script>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
