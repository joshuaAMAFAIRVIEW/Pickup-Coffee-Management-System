<?php 
require_once __DIR__ . '/helpers.php';
require_role(['area_manager', 'admin']);
require_once __DIR__ . '/config.php';

$current_user = $_SESSION['user'];
$is_admin = $current_user['role'] === 'admin';

// For admins, allow viewing all areas via dropdown selection
// For area managers, get their assigned area
$currentArea = null;
$selectedAreaId = null;

if ($is_admin) {
    // Admin can select area via query parameter
    $selectedAreaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : null;
    
    if ($selectedAreaId) {
        $areaStmt = $pdo->prepare('SELECT * FROM areas WHERE area_id = ?');
        $areaStmt->execute([$selectedAreaId]);
        $currentArea = $areaStmt->fetch(PDO::FETCH_ASSOC);
    }
} else {
    // Area manager - must have area_id assigned
    if ($current_user['area_id']) {
        $areaStmt = $pdo->prepare('SELECT * FROM areas WHERE area_id = ?');
        $areaStmt->execute([$current_user['area_id']]);
        $currentArea = $areaStmt->fetch(PDO::FETCH_ASSOC);
        $selectedAreaId = $current_user['area_id'];
    }
    
    // If area manager has no area, redirect with error
    if (!$currentArea) {
        $_SESSION['flash_error'] = 'You are not assigned to any area. Please contact administrator.';
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team - Pickup Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .supervisor-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        .supervisor-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .unassigned-badge {
            background: #ffc107;
            color: #000;
        }
        .assigned-badge {
            background: #198754;
        }
    </style>
</head>
<body>
    <?php include 'dashboard_nav_wrapper_start.php'; ?>
    
    <?php if ($is_admin && !$currentArea): ?>
        <!-- Admin Area Selection -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Select Area to Manage</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">As an administrator, please select which area's team you want to manage:</p>
                        <div class="mb-3">
                            <label class="form-label">Select Area</label>
                            <select class="form-select" id="adminAreaSelect" onchange="selectArea(this.value)">
                                <option value="">-- Choose an Area --</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Load areas for admin selection
            document.addEventListener('DOMContentLoaded', async function() {
                try {
                    const response = await fetch('get_areas.php');
                    const data = await response.json();
                    if (data.success) {
                        const select = document.getElementById('adminAreaSelect');
                        data.areas.forEach(area => {
                            const option = document.createElement('option');
                            option.value = area.area_id;
                            option.textContent = area.area_name;
                            select.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error('Error loading areas:', error);
                }
            });
            
            function selectArea(areaId) {
                if (areaId) {
                    window.location.href = 'manage_team.php?area_id=' + areaId;
                }
            }
        </script>
    <?php else: ?>
        <!-- Team Management Interface -->
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-users me-2"></i>Manage My Team</h2>
                <p class="text-muted">
                    Assign store supervisors to stores in 
                    <span class="badge bg-info"><?php echo htmlspecialchars($currentArea['area_name']); ?></span>
                    <?php if ($is_admin): ?>
                        <a href="manage_team.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i>Change Area
                        </a>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" onclick="window.location.href='users.php'">
                    <i class="fas fa-user-plus me-2"></i>Create New Supervisor
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Total Supervisors</h6>
                        <h3 id="statTotal">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Assigned</h6>
                        <h3 id="statAssigned" class="text-success">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Unassigned</h6>
                        <h3 id="statUnassigned" class="text-warning">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supervisors List -->
        <div class="card">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Store Supervisors</h5>
                    </div>
                    <div class="col-auto">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search supervisor...">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="supervisorsContainer">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading supervisors...</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Assign Store Modal -->
    <div class="modal fade" id="assignStoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-store me-2"></i>Assign Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="assignStoreForm">
                    <div class="modal-body">
                        <input type="hidden" id="assignUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">Supervisor</label>
                            <input type="text" class="form-control" id="assignUserName" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Store <span class="text-danger">*</span></label>
                            <select class="form-select" id="assignStoreSelect" required>
                                <option value="">-- Select Store --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" id="assignReason">
                                <option value="New Assignment">New Assignment</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Replacement">Replacement</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="assignNotes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Assign Store
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'dashboard_nav_wrapper_end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($currentArea): ?>
    <script>
        const currentAreaId = <?php echo $selectedAreaId ?? 'null'; ?>;
        const currentUserId = <?php echo $current_user['id']; ?>;
        const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
        let allSupervisors = [];
        let allStores = [];
        let assignModal;

        document.addEventListener('DOMContentLoaded', function() {
            assignModal = new bootstrap.Modal(document.getElementById('assignStoreModal'));
            loadData();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                renderSupervisors(allSupervisors.filter(s => 
                    s.full_name?.toLowerCase().includes(searchTerm) ||
                    s.username?.toLowerCase().includes(searchTerm) ||
                    s.employee_number?.toLowerCase().includes(searchTerm)
                ));
            });
        });

        async function loadData() {
            try {
                // Load stores in this area
                const storesResponse = await fetch('get_stores.php');
                const storesData = await storesResponse.json();
                if (storesData.success) {
                    allStores = storesData.stores.filter(s => s.area_id == currentAreaId && s.is_active);
                }

                // Load supervisors
                const supervisorsResponse = await fetch('get_users.php');
                const supervisorsData = await supervisorsResponse.json();
                if (supervisorsData.success) {
                    // Filter only store supervisors in the selected area
                    if (isAdmin) {
                        // Admin sees all supervisors in selected area
                        allSupervisors = supervisorsData.users.filter(u => 
                            u.role === 'store_supervisor' && u.area_id == currentAreaId
                        );
                    } else {
                        // Area manager sees supervisors they manage or in their area
                        allSupervisors = supervisorsData.users.filter(u => 
                            u.role === 'store_supervisor' && 
                            (u.managed_by_user_id == currentUserId || u.area_id == currentAreaId)
                        );
                    }
                    renderSupervisors(allSupervisors);
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading data:', error);
                document.getElementById('supervisorsContainer').innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading supervisors
                        </div>
                    </div>
                `;
            }
        }

        function renderSupervisors(supervisors) {
            const container = document.getElementById('supervisorsContainer');
            
            if (supervisors.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No supervisors found. 
                            <a href="users.php" class="alert-link">Create a new supervisor</a>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = supervisors.map(supervisor => {
                const isAssigned = supervisor.store_id && supervisor.store_name;
                const badgeClass = isAssigned ? 'assigned-badge' : 'unassigned-badge';
                const statusText = isAssigned ? supervisor.store_name : 'Not Assigned';
                
                return `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card supervisor-card" onclick="openAssignModal(${supervisor.id})">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">${escapeHtml(supervisor.full_name || supervisor.username)}</h6>
                                        <small class="text-muted">${escapeHtml(supervisor.employee_number)}</small>
                                    </div>
                                    <span class="badge ${badgeClass}">
                                        ${isAssigned ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-exclamation-circle me-1"></i>'}
                                    </span>
                                </div>
                                <hr>
                                <div class="mb-2">
                                    <small class="text-muted">Current Store:</small><br>
                                    <strong>${isAssigned ? escapeHtml(statusText) : '<span class="text-warning">' + statusText + '</span>'}</strong>
                                </div>
                                ${supervisor.email ? `
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>${escapeHtml(supervisor.email)}
                                    </small>
                                </div>
                                ` : ''}
                            </div>
                            <div class="card-footer bg-light">
                                <button class="btn btn-sm btn-primary w-100" onclick="event.stopPropagation(); openAssignModal(${supervisor.id})">
                                    <i class="fas fa-edit me-1"></i>${isAssigned ? 'Change Store' : 'Assign Store'}
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateStats() {
            const total = allSupervisors.length;
            const assigned = allSupervisors.filter(s => s.store_id).length;
            const unassigned = total - assigned;
            
            document.getElementById('statTotal').textContent = total;
            document.getElementById('statAssigned').textContent = assigned;
            document.getElementById('statUnassigned').textContent = unassigned;
        }

        function openAssignModal(userId) {
            const supervisor = allSupervisors.find(s => s.id == userId);
            if (!supervisor) return;

            document.getElementById('assignUserId').value = supervisor.id;
            document.getElementById('assignUserName').value = supervisor.full_name || supervisor.username;

            // Populate stores dropdown
            const storeSelect = document.getElementById('assignStoreSelect');
            storeSelect.innerHTML = '<option value="">-- Select Store --</option>';
            allStores.forEach(store => {
                const option = document.createElement('option');
                option.value = store.store_id;
                option.textContent = store.store_name;
                if (store.store_id == supervisor.store_id) {
                    option.selected = true;
                }
                storeSelect.appendChild(option);
            });

            assignModal.show();
        }

        document.getElementById('assignStoreForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const userId = document.getElementById('assignUserId').value;
            const storeId = document.getElementById('assignStoreSelect').value;
            const reason = document.getElementById('assignReason').value;
            const notes = document.getElementById('assignNotes').value;

            if (!storeId) {
                alert('Please select a store');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Assigning...';

            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('store_id', storeId);
                formData.append('reason', reason);
                formData.append('notes', notes);

                const response = await fetch('change_supervisor_store.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    assignModal.hide();
                    this.reset();
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
                    
                    // Reload data
                    await loadData();
                    
                    // Auto dismiss alert
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error assigning store:', error);
                alert('Error: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Assign Store';
            }
        });

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php endif; ?>
</body>
</html>
