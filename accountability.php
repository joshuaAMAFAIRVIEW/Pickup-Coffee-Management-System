<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

$user = $_SESSION['user'];

include __DIR__ . '/dashboard_nav_wrapper_start.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-contract"></i> Release/Return Accountability</h2>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="accountabilityTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="release-tab" data-bs-toggle="tab" data-bs-target="#release" type="button" role="tab">
        <i class="fas fa-share-square"></i> Release Logs
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="return-tab" data-bs-toggle="tab" data-bs-target="#return" type="button" role="tab">
        <i class="fas fa-undo"></i> Return Logs
      </button>
    </li>
  </ul>

  <div class="tab-content" id="accountabilityTabContent">
    <!-- Release Tab -->
    <div class="tab-pane fade show active" id="release" role="tabpanel">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <input type="text" id="releaseSearch" class="form-control" placeholder="Search releases...">
            </div>
            <button class="btn btn-success" onclick="refreshReleaseData()">
              <i class="fas fa-sync-alt"></i> Refresh
            </button>
          </div>
          <div id="releaseLoading" class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div class="table-responsive" id="releaseTableContainer" style="display:none;">
            <table class="table table-hover table-sm" id="releaseTable">
              <thead class="table-light">
                <tr>
                  <th>Timestamp</th>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>User</th>
                  <th>Department</th>
                  <th>Region</th>
                  <th>Assigned Date</th>
                  <th>Condition</th>
                  <th>Serial Number</th>
                  <th>Attributes</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody id="releaseTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Return Tab -->
    <div class="tab-pane fade" id="return" role="tabpanel">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <input type="text" id="returnSearch" class="form-control" placeholder="Search returns...">
            </div>
            <button class="btn btn-success" onclick="refreshReturnData()">
              <i class="fas fa-sync-alt"></i> Refresh
            </button>
          </div>
          <div id="returnLoading" class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div class="table-responsive" id="returnTableContainer" style="display:none;">
            <table class="table table-hover table-sm" id="returnTable">
              <thead class="table-light">
                <tr>
                  <th>Timestamp</th>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>User</th>
                  <th>Department</th>
                  <th>Region</th>
                  <th>Assigned Date</th>
                  <th>Returned Date</th>
                  <th>Condition</th>
                  <th>Issue/Damage Details</th>
                  <th>Serial Number</th>
                  <th>Attributes</th>
                  <th>Photo</th>
                </tr>
              </thead>
              <tbody id="returnTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Incident Report Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalPhoto" src="" class="img-fluid" alt="Incident Photo" style="max-height: 70vh;">
      </div>
      <div class="modal-footer">
        <a id="downloadPhotoBtn" href="" download class="btn btn-primary">
          <i class="fas fa-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
let releaseData = [];
let returnData = [];
let photoModal;

// Load data when page loads
document.addEventListener('DOMContentLoaded', function() {
  // Initialize photo modal
  photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
  
  loadReleaseData();
  
  // Load return data when tab is clicked
  document.getElementById('return-tab').addEventListener('shown.bs.tab', function() {
    if (returnData.length === 0) {
      loadReturnData();
    }
  });
});

// Load Release Data
function loadReleaseData() {
  document.getElementById('releaseLoading').style.display = 'block';
  document.getElementById('releaseTableContainer').style.display = 'none';
  
  fetch('get_sheets_logs.php?type=release')
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        releaseData = result.data;
        renderReleaseTable(releaseData);
      } else {
        alert('Error loading release data: ' + result.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error loading release data');
    })
    .finally(() => {
      document.getElementById('releaseLoading').style.display = 'none';
      document.getElementById('releaseTableContainer').style.display = 'block';
    });
}

// Load Return Data
function loadReturnData() {
  document.getElementById('returnLoading').style.display = 'block';
  document.getElementById('returnTableContainer').style.display = 'none';
  
  fetch('get_sheets_logs.php?type=return')
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        returnData = result.data;
        renderReturnTable(returnData);
      } else {
        alert('Error loading return data: ' + result.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error loading return data');
    })
    .finally(() => {
      document.getElementById('returnLoading').style.display = 'none';
      document.getElementById('returnTableContainer').style.display = 'block';
    });
}

// Render Release Table
function renderReleaseTable(data) {
  const tbody = document.getElementById('releaseTableBody');
  tbody.innerHTML = '';
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" class="text-center">No release records found</td></tr>';
    return;
  }
  
  data.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.Timestamp || ''}</td>
      <td><strong>${row['Item Name'] || ''}</strong></td>
      <td><span class="badge bg-secondary">${row.Category || ''}</span></td>
      <td>
        <strong>${row['User Name'] || ''}</strong><br>
        <small class="text-muted">${row.Username || ''}</small>
      </td>
      <td>${row.Department || ''}</td>
      <td>${row.Region || ''}</td>
      <td>${row['Assigned At'] || ''}</td>
      <td>
        ${row['Item Condition'] === 'Brand New' ? 
          '<span class="badge bg-success">Brand New</span>' : 
          '<span class="badge bg-info text-dark">Re-Issue</span>'}
      </td>
      <td><code>${row['Serial Number'] || 'N/A'}</code></td>
      <td><small>${row['Other Attributes'] || '-'}</small></td>
      <td><small>${row.Notes || '-'}</small></td>
    `;
    tbody.appendChild(tr);
  });
}

// Render Return Table
function renderReturnTable(data) {
  const tbody = document.getElementById('returnTableBody');
  tbody.innerHTML = '';
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="13" class="text-center">No return records found</td></tr>';
    return;
  }
  
  data.forEach(row => {
    // Determine condition badge
    let conditionBadge = '<span class="badge bg-success">Perfectly Working</span>';
    if (row['Issue Details']) {
      conditionBadge = '<span class="badge bg-warning text-dark">Minor Issue</span>';
    } else if (row['Damage Details']) {
      conditionBadge = '<span class="badge bg-danger">Damaged</span>';
    }
    
    // Handle photo link
    let photoCell = '-';
    let photoUrl = '';
    if (row['Incident Photo'] && row['Incident Photo'].includes('HYPERLINK')) {
      // Extract URL from HYPERLINK formula
      const match = row['Incident Photo'].match(/HYPERLINK\("([^"]+)"/);
      if (match) {
        photoUrl = match[1];
        photoCell = `<button onclick="showPhoto('${photoUrl}')" class="btn btn-sm btn-info"><i class="fas fa-image"></i> View</button>`;
      }
    } else if (row['Incident Photo']) {
      photoUrl = row['Incident Photo'];
      photoCell = `<button onclick="showPhoto('${photoUrl}')" class="btn btn-sm btn-info"><i class="fas fa-image"></i> View</button>`;
    }
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.Timestamp || ''}</td>
      <td><strong>${row['Item Name'] || ''}</strong></td>
      <td><span class="badge bg-secondary">${row.Category || ''}</span></td>
      <td>
        <strong>${row['User Name'] || ''}</strong><br>
        <small class="text-muted">${row.Username || ''}</small>
      </td>
      <td>${row.Department || ''}</td>
      <td>${row.Region || ''}</td>
      <td>${row['Assigned At'] || ''}</td>
      <td>${row['Returned At'] || ''}</td>
      <td>${conditionBadge}</td>
      <td><small>${row['Issue Details'] || row['Damage Details'] || '-'}</small></td>
      <td><code>${row['Serial Number'] || 'N/A'}</code></td>
      <td><small>${row['Other Attributes'] || '-'}</small></td>
      <td>${photoCell}</td>
    `;
    tbody.appendChild(tr);
  });
}

// Refresh functions
function refreshReleaseData() {
  loadReleaseData();
}

function refreshReturnData() {
  loadReturnData();
}

// Search functionality
document.getElementById('releaseSearch').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const filtered = releaseData.filter(row => {
    return Object.values(row).some(val => 
      String(val).toLowerCase().includes(searchTerm)
    );
  });
  renderReleaseTable(filtered);
});

document.getElementById('returnSearch').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const filtered = returnData.filter(row => {
    return Object.values(row).some(val => 
      String(val).toLowerCase().includes(searchTerm)
    );
  });
  renderReturnTable(filtered);
});

// Show photo in modal
function showPhoto(photoUrl) {
  document.getElementById('modalPhoto').src = photoUrl;
  document.getElementById('downloadPhotoBtn').href = photoUrl;
  photoModal.show();
}
</script>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
