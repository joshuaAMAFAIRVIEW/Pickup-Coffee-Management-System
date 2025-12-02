<?php 
require_once __DIR__ . '/helpers.php';
require_role(['admin', 'area_manager', 'store_supervisor']);
$current_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Supervisor Movements - Pickup Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card { border-left: 4px solid #0d6efd; }
        .movement-row { transition: background-color 0.2s; }
        .movement-row:hover { background-color: #f8f9fa; }
        .badge-area { font-size: 0.75rem; }
    </style>
</head>
<body>
    <?php include 'dashboard_nav_wrapper_start.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2><i class="fas fa-exchange-alt me-2"></i>Store Supervisor Movements</h2>
                <p class="text-muted">Track store supervisor assignment changes</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <?php if ($current_user['role'] === 'admin'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Area</label>
                        <select class="form-select" id="filterArea">
                            <option value="">All Areas</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($current_user['role'] !== 'store_supervisor'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Store</label>
                        <select class="form-select" id="filterStore">
                            <option value="">All Stores</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Supervisor</label>
                        <select class="form-select" id="filterUser">
                            <option value="">All Supervisors</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="filterFromDate">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="filterToDate">
                    </div>
                    
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="loadMovements()">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-redo me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4" id="statsSection">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Movements</h6>
                        <h3 id="statTotal">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #198754;">
                    <div class="card-body">
                        <h6 class="text-muted">This Month</h6>
                        <h3 id="statMonth">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <h6 class="text-muted">This Week</h6>
                        <h3 id="statWeek">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #dc3545;">
                    <div class="card-body">
                        <h6 class="text-muted">Active Supervisors</h6>
                        <h3 id="statSupervisors">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movements Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Movement History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Supervisor</th>
                                <th>From</th>
                                <th><i class="fas fa-arrow-right"></i></th>
                                <th>To</th>
                                <th>Changed By</th>
                                <th>Reason</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="movementsTable">
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading movements...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'dashboard_nav_wrapper_end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentUserRole = '<?php echo $current_user['role']; ?>';
        const currentUserAreaId = <?php echo $current_user['area_id'] ?? 'null'; ?>;
        
        let allAreas = [];
        let allStores = [];
        let allSupervisors = [];
        let allMovements = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            loadMovements();
        });

        async function loadFilters() {
            try {
                // Load areas (admin only)
                if (currentUserRole === 'admin') {
                    const areasResponse = await fetch('get_areas.php');
                    const areasData = await areasResponse.json();
                    allAreas = areasData.areas;
                    
                    const areaSelect = document.getElementById('filterArea');
                    allAreas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.area_id;
                        option.textContent = area.area_name;
                        areaSelect.appendChild(option);
                    });
                }
                
                // Load stores
                if (currentUserRole !== 'store_supervisor') {
                    const storesResponse = await fetch('get_stores.php');
                    const storesData = await storesResponse.json();
                    allStores = storesData.stores;
                    
                    // Filter stores for area managers
                    const filteredStores = currentUserRole === 'area_manager' 
                        ? allStores.filter(s => s.area_id == currentUserAreaId)
                        : allStores;
                    
                    const storeSelect = document.getElementById('filterStore');
                    filteredStores.forEach(store => {
                        const option = document.createElement('option');
                        option.value = store.store_id;
                        option.textContent = store.store_name;
                        storeSelect.appendChild(option);
                    });
                }
                
                // Load supervisors (not for store_supervisor role)
                if (currentUserRole !== 'store_supervisor') {
                    const usersResponse = await fetch('get_users.php');
                    const usersData = await usersResponse.json();
                    allSupervisors = usersData.users.filter(u => u.role === 'store_supervisor');
                    
                    const userSelect = document.getElementById('filterUser');
                    allSupervisors.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.full_name || user.username;
                        userSelect.appendChild(option);
                    });
                }
                
            } catch (error) {
                console.error('Error loading filters:', error);
            }
        }

        async function loadMovements() {
            try {
                const params = new URLSearchParams();
                
                const areaFilter = document.getElementById('filterArea');
                const storeFilter = document.getElementById('filterStore');
                const userFilter = document.getElementById('filterUser');
                const fromDate = document.getElementById('filterFromDate').value;
                const toDate = document.getElementById('filterToDate').value;
                
                if (areaFilter && areaFilter.value) params.append('area_id', areaFilter.value);
                if (storeFilter && storeFilter.value) params.append('store_id', storeFilter.value);
                if (userFilter && userFilter.value) params.append('user_id', userFilter.value);
                if (fromDate) params.append('from_date', fromDate);
                if (toDate) params.append('to_date', toDate);
                
                const response = await fetch('get_supervisor_movements.php?' + params.toString());
                const data = await response.json();
                
                if (data.success) {
                    allMovements = data.movements;
                    renderMovements(allMovements);
                    updateStats(allMovements);
                } else {
                    throw new Error(data.error || 'Failed to load movements');
                }
                
            } catch (error) {
                console.error('Error loading movements:', error);
                const tbody = document.getElementById('movementsTable');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading movements: ${error.message}
                        </td>
                    </tr>
                `;
            }
        }

        function renderMovements(movements) {
            const tbody = document.getElementById('movementsTable');
            
            if (movements.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-info-circle me-2"></i>No movements found
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = movements.map(m => `
                <tr class="movement-row">
                    <td>${formatDate(m.changed_date)}</td>
                    <td>
                        <strong>${escapeHtml(m.full_name || m.username)}</strong>
                        <br><small class="text-muted">${escapeHtml(m.username)}</small>
                    </td>
                    <td>
                        ${m.from_store_name ? `
                            <strong>${escapeHtml(m.from_store_name)}</strong>
                            ${m.from_area_name ? `<br><span class="badge badge-area bg-secondary">${escapeHtml(m.from_area_name)}</span>` : ''}
                        ` : '<span class="text-muted">None</span>'}
                    </td>
                    <td class="text-center">
                        <i class="fas fa-arrow-right text-primary"></i>
                    </td>
                    <td>
                        ${m.to_store_name ? `
                            <strong>${escapeHtml(m.to_store_name)}</strong>
                            ${m.to_area_name ? `<br><span class="badge badge-area bg-info">${escapeHtml(m.to_area_name)}</span>` : ''}
                        ` : '<span class="text-muted">None</span>'}
                    </td>
                    <td>
                        <small>${escapeHtml(m.changed_by_name || m.changed_by_username)}</small>
                    </td>
                    <td>${m.reason ? escapeHtml(m.reason) : '<span class="text-muted">-</span>'}</td>
                    <td>
                        ${m.notes ? `
                            <button class="btn btn-sm btn-link p-0" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="left" 
                                    title="${escapeHtml(m.notes)}">
                                <i class="fas fa-comment-alt"></i>
                            </button>
                        ` : '<span class="text-muted">-</span>'}
                    </td>
                </tr>
            `).join('');
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        function updateStats(movements) {
            document.getElementById('statTotal').textContent = movements.length;
            
            const now = new Date();
            const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            const firstDayOfWeek = new Date(now);
            firstDayOfWeek.setDate(now.getDate() - now.getDay());
            
            const thisMonth = movements.filter(m => new Date(m.changed_date) >= firstDayOfMonth).length;
            const thisWeek = movements.filter(m => new Date(m.changed_date) >= firstDayOfWeek).length;
            const uniqueSupervisors = new Set(movements.map(m => m.user_id)).size;
            
            document.getElementById('statMonth').textContent = thisMonth;
            document.getElementById('statWeek').textContent = thisWeek;
            document.getElementById('statSupervisors').textContent = uniqueSupervisors;
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            loadMovements();
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
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
