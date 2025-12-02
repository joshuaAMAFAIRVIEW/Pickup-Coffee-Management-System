<?php 
require_once __DIR__ . '/helpers.php';
require_role(['store_supervisor']);
require_once __DIR__ . '/config.php';

$current_user = $_SESSION['user'];

// Get current store info
$storeStmt = $pdo->prepare('
    SELECT s.*, a.area_name, a.area_id
    FROM users u
    LEFT JOIN stores s ON u.store_id = s.store_id
    LEFT JOIN areas a ON s.area_id = a.area_id
    WHERE u.id = ?
');
$storeStmt->execute([$current_user['id']]);
$currentStore = $storeStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Store - Pickup Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'dashboard_nav_wrapper_start.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-store me-2"></i>My Store Assignment</h2>
                <p class="text-muted">View and update your current store assignment</p>
            </div>
        </div>

        <div class="row">
            <!-- Current Store Info -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current Assignment</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($currentStore && $currentStore['store_id']): ?>
                            <div class="mb-3">
                                <label class="text-muted small">Store Name</label>
                                <h4><?php echo htmlspecialchars($currentStore['store_name']); ?></h4>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Area</label>
                                <p class="mb-0">
                                    <span class="badge bg-info fs-6">
                                        <?php echo htmlspecialchars($currentStore['area_name']); ?>
                                    </span>
                                </p>
                            </div>
                            <?php if ($currentStore['store_address']): ?>
                            <div class="mb-3">
                                <label class="text-muted small">Address</label>
                                <p class="mb-0"><?php echo htmlspecialchars($currentStore['store_address']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($currentStore['store_phone']): ?>
                            <div class="mb-3">
                                <label class="text-muted small">Phone</label>
                                <p class="mb-0"><?php echo htmlspecialchars($currentStore['store_phone']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="mb-0">
                                <label class="text-muted small">Status</label>
                                <p class="mb-0">
                                    <?php if ($currentStore['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                You are not currently assigned to any store.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Change Store Form -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Request Store Change</h5>
                    </div>
                    <div class="card-body">
                        <form id="changeStoreForm">
                            <div class="mb-3">
                                <label for="newStore" class="form-label">New Store <span class="text-danger">*</span></label>
                                <select class="form-select" id="newStore" required>
                                    <option value="">Select a store...</option>
                                </select>
                                <small class="text-muted">Select the store you want to be assigned to</small>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                <select class="form-select" id="reason" required>
                                    <option value="">Select reason...</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Promotion">Promotion</option>
                                    <option value="Coverage">Coverage/Temporary</option>
                                    <option value="Relocation">Relocation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" rows="3" 
                                          placeholder="Provide any additional details..."></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Your area manager will be notified of this change.</small>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>Submit Store Change
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>My Movement History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>From Store</th>
                                        <th></th>
                                        <th>To Store</th>
                                        <th>Reason</th>
                                        <th>Changed By</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTable">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i>Loading history...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'dashboard_nav_wrapper_end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentUserId = <?php echo $current_user['id']; ?>;
        const currentAreaId = <?php echo $currentStore['area_id'] ?? 'null'; ?>;
        const currentStoreId = <?php echo $currentStore['store_id'] ?? 'null'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            loadStores();
            loadHistory();
        });

        async function loadStores() {
            try {
                const response = await fetch('get_stores.php');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('newStore');
                    
                    // Group stores by area
                    const storesByArea = {};
                    data.stores.filter(s => s.is_active).forEach(store => {
                        if (!storesByArea[store.area_name]) {
                            storesByArea[store.area_name] = [];
                        }
                        storesByArea[store.area_name].push(store);
                    });
                    
                    // Add stores grouped by area
                    Object.keys(storesByArea).sort().forEach(areaName => {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = areaName;
                        
                        storesByArea[areaName].forEach(store => {
                            // Don't show current store
                            if (store.store_id == currentStoreId) return;
                            
                            const option = document.createElement('option');
                            option.value = store.store_id;
                            option.textContent = store.store_name;
                            optgroup.appendChild(option);
                        });
                        
                        select.appendChild(optgroup);
                    });
                }
            } catch (error) {
                console.error('Error loading stores:', error);
                alert('Error loading stores. Please refresh the page.');
            }
        }

        async function loadHistory() {
            try {
                const response = await fetch('get_supervisor_movements.php');
                const data = await response.json();
                
                if (data.success) {
                    renderHistory(data.movements);
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error loading history:', error);
                const tbody = document.getElementById('historyTable');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading history
                        </td>
                    </tr>
                `;
            }
        }

        function renderHistory(movements) {
            const tbody = document.getElementById('historyTable');
            
            if (movements.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            <i class="fas fa-info-circle me-2"></i>No movement history yet
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = movements.slice(0, 10).map(m => `
                <tr>
                    <td>${formatDate(m.changed_date)}</td>
                    <td>
                        ${m.from_store_name || '<span class="text-muted">None</span>'}
                        ${m.from_area_name ? `<br><small class="text-muted">${escapeHtml(m.from_area_name)}</small>` : ''}
                    </td>
                    <td class="text-center">
                        <i class="fas fa-arrow-right text-primary"></i>
                    </td>
                    <td>
                        ${m.to_store_name || '<span class="text-muted">None</span>'}
                        ${m.to_area_name ? `<br><small class="text-muted">${escapeHtml(m.to_area_name)}</small>` : ''}
                    </td>
                    <td>${escapeHtml(m.reason || '-')}</td>
                    <td><small>${escapeHtml(m.changed_by_name || m.changed_by_username)}</small></td>
                </tr>
            `).join('');
        }

        document.getElementById('changeStoreForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newStore = document.getElementById('newStore').value;
            const reason = document.getElementById('reason').value;
            const notes = document.getElementById('notes').value;
            
            if (!newStore || !reason) {
                alert('Please fill in all required fields');
                return;
            }
            
            if (newStore == currentStoreId) {
                alert('You are already assigned to this store');
                return;
            }
            
            if (!confirm('Are you sure you want to change your store assignment?')) {
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            
            try {
                const formData = new FormData();
                formData.append('user_id', currentUserId);
                formData.append('store_id', newStore);
                formData.append('reason', reason);
                formData.append('notes', notes);
                
                const response = await fetch('change_supervisor_store.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Store assignment updated successfully! The page will now reload.');
                    window.location.reload();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error changing store:', error);
                alert('Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Submit Store Change';
            }
        });

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
