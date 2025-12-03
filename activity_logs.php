<?php 
$page_title = "Activity Logs";
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/dashboard_nav_wrapper_start.php'; 
require_once __DIR__ . '/config.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Area Manager Activity Logs</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Activity Logs</li>
    </ol>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filters
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="filterAreaManager" class="form-label">Area Manager</label>
                    <select id="filterAreaManager" class="form-select">
                        <option value="">All Area Managers</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterAction" class="form-label">Action Type</label>
                    <select id="filterAction" class="form-select">
                        <option value="">All Actions</option>
                        <option value="create_store">Create Store</option>
                        <option value="update_store">Update Store</option>
                        <option value="delete_store">Delete Store</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterDateFrom" class="form-label">Date From</label>
                    <input type="date" id="filterDateFrom" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="filterDateTo" class="form-label">Date To</label>
                    <input type="date" id="filterDateTo" class="form-control">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Activity Logs
        </div>
        <div class="card-body">
            <div id="logsContainer">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading logs...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Area Manager Reassignment History -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Area Manager Reassignment History
        </div>
        <div class="card-body">
            <div id="historyContainer">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading history...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logDetailsModalLabel">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let logsData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadAreaManagers();
    loadLogs();
    loadHistory();
});

function loadAreaManagers() {
    fetch('get_users.php?role=area_manager')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filterAreaManager');
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.first_name} ${user.last_name} (${user.username})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading area managers:', error));
}

function loadLogs() {
    const params = new URLSearchParams();
    
    const areaManager = document.getElementById('filterAreaManager').value;
    const action = document.getElementById('filterAction').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    if (areaManager) params.append('user_id', areaManager);
    if (action) params.append('action_type', action);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    fetch('get_activity_logs.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logsData = data.logs;
                renderLogs(data.logs);
            } else {
                document.getElementById('logsContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Error loading logs: ${data.error || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            document.getElementById('logsContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    Error loading logs
                </div>
            `;
        });
}

function renderLogs(logs) {
    const container = document.getElementById('logsContainer');
    
    if (logs.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                No logs found matching the filter criteria
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Area Manager</th>
                        <th>Action</th>
                        <th>Store</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    logs.forEach(log => {
        const actionBadge = getActionBadge(log.action_type);
        const date = new Date(log.created_at);
        const formattedDate = date.toLocaleString();
        
        html += `
            <tr>
                <td>${formattedDate}</td>
                <td>${log.user_first_name} ${log.user_last_name}</td>
                <td>${actionBadge}</td>
                <td>${log.store_name || 'N/A'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showLogDetails(${log.id})">
                        <i class="fas fa-eye me-1"></i>View
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

function getActionBadge(action) {
    const badges = {
        'create_store': '<span class="badge bg-success">Create Store</span>',
        'update_store': '<span class="badge bg-warning">Update Store</span>',
        'delete_store': '<span class="badge bg-danger">Delete Store</span>'
    };
    return badges[action] || action;
}

function showLogDetails(logId) {
    const log = logsData.find(l => l.id === logId);
    if (!log) return;
    
    let details;
    try {
        details = JSON.parse(log.details);
    } catch (e) {
        details = {};
    }
    
    let html = `
        <div class="mb-3">
            <strong>Action:</strong> ${getActionBadge(log.action_type)}
        </div>
        <div class="mb-3">
            <strong>Performed By:</strong> ${log.user_first_name} ${log.user_last_name} (${log.user_username})
        </div>
        <div class="mb-3">
            <strong>Date & Time:</strong> ${new Date(log.created_at).toLocaleString()}
        </div>
        <div class="mb-3">
            <strong>Store:</strong> ${log.store_name || 'N/A'}
        </div>
    `;
    
    if (log.action_type === 'update_store' && details.changes) {
        html += `
            <div class="mb-3">
                <strong>Changes:</strong>
                <table class="table table-sm mt-2">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        for (const [field, change] of Object.entries(details.changes)) {
            html += `
                <tr>
                    <td><strong>${field}</strong></td>
                    <td>${change.old || '<em>empty</em>'}</td>
                    <td>${change.new || '<em>empty</em>'}</td>
                </tr>
            `;
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        html += `
            <div class="mb-3">
                <strong>Details:</strong>
                <pre class="bg-light p-3 mt-2">${JSON.stringify(details, null, 2)}</pre>
            </div>
        `;
    }
    
    document.getElementById('logDetailsContent').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    modal.show();
}

function loadHistory() {
    fetch('get_area_manager_history.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderHistory(data.history);
            } else {
                document.getElementById('historyContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Error loading history
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
            document.getElementById('historyContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    Error loading history
                </div>
            `;
        });
}

function renderHistory(history) {
    const container = document.getElementById('historyContainer');
    
    if (history.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                No reassignment history found
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Area Manager</th>
                        <th>From Area</th>
                        <th>To Area</th>
                        <th>Changed By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    history.forEach(record => {
        const date = new Date(record.changed_at);
        const formattedDate = date.toLocaleString();
        
        html += `
            <tr>
                <td>${formattedDate}</td>
                <td>${record.user_first_name} ${record.user_last_name}</td>
                <td>${record.from_area_name || 'N/A'}</td>
                <td>${record.to_area_name || 'N/A'}</td>
                <td>${record.changed_by_first_name} ${record.changed_by_last_name}</td>
                <td>${record.reason || 'N/A'}</td>
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

function applyFilters() {
    loadLogs();
}

function clearFilters() {
    document.getElementById('filterAreaManager').value = '';
    document.getElementById('filterAction').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    loadLogs();
}
</script>

<?php require_once __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
