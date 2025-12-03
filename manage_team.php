<?php 
require_once __DIR__ . '/helpers.php';
require_role(['area_manager', 'admin']);
require_once __DIR__ . '/config.php';

$current_user = $_SESSION['user'];
$is_admin = $current_user['role'] === 'admin';

// Reload user data from database to get latest area_id assignment
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$current_user['id']]);
$freshUserData = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($freshUserData) {
    $current_user = $freshUserData;
    $_SESSION['user'] = $freshUserData; // Update session with fresh data
}

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
    if (isset($current_user['area_id']) && $current_user['area_id']) {
        $areaStmt = $pdo->prepare('SELECT * FROM areas WHERE area_id = ? AND is_active = 1');
        $areaStmt->execute([$current_user['area_id']]);
        $currentArea = $areaStmt->fetch(PDO::FETCH_ASSOC);
        $selectedAreaId = $current_user['area_id'];
    }
    
    // If area manager has no area, show message to assign one
    if (!$currentArea) {
        // Don't redirect - show a helpful message instead
        $showNoAreaMessage = true;
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
    
    <?php if (isset($showNoAreaMessage) && $showNoAreaMessage): ?>
        <!-- Area Manager Not Assigned Message -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>No Area Assigned</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are currently not assigned to any area. Please contact the IT administrator to assign you to an area.</p>
                        <div class="alert alert-info mb-0">
                            <strong>Note:</strong> Once you are assigned to an area, you will be able to:
                            <ul class="mb-0 mt-2">
                                <li>Manage store supervisors in your area</li>
                                <li>Assign supervisors to stores</li>
                                <li>Track supervisor movements</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($is_admin && !$currentArea): ?>
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
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestAssignmentModal">
                    <i class="fas fa-user-check me-2"></i>Request Supervisor Assignment
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

    <!-- Request Assignment Modal -->
    <div class="modal fade" id="requestAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Request Supervisor Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="requestAssignmentForm">
                    <div class="modal-body">
                        <!-- Step 1: Employee Lookup -->
                        <div id="step1">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Enter the employee number to lookup supervisor details
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Employee Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="employeeNumberInput" placeholder="e.g., EMP001" required>
                                    <button type="button" class="btn btn-primary" onclick="lookupEmployee()">
                                        <i class="fas fa-search me-2"></i>Lookup
                                    </button>
                                </div>
                                <div id="employeeLookupError" class="text-danger small mt-1" style="display:none;"></div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Terms & Conditions -->
                        <div id="step2" style="display:none;">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Before assigning this supervisor to a store, you must verify equipment conditions.
                            </div>

                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Equipment Verification Checklist</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reqCheck1" required>
                                        <label class="form-check-label" for="reqCheck1">
                                            I have verified all current equipment in the store is accounted for
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reqCheck2" required>
                                        <label class="form-check-label" for="reqCheck2">
                                            I have inspected the condition of all equipment in the store
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reqCheck3" required>
                                        <label class="form-check-label" for="reqCheck3">
                                            All equipment is in working condition or damages have been documented
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reqCheck4" required>
                                        <label class="form-check-label" for="reqCheck4">
                                            I understand the supervisor will be accountable for the equipment from this date forward
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary w-100" id="acceptTermsBtn" onclick="acceptTerms()" disabled>
                                <i class="fas fa-check me-2"></i>I Agree - Proceed to Assignment
                            </button>
                        </div>
                        
                        <!-- Step 3: Employee Details & Store Selection -->
                        <div id="step3" style="display:none;">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>Supervisor found
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>Supervisor Details</strong>
                                </div>
                                <div class="card-body">
                                    <input type="hidden" id="selectedSupervisorId">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <strong>Name:</strong> <span id="displayName"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Employee #:</strong> <span id="displayEmployeeNumber"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Username:</strong> <span id="displayUsername"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Email:</strong> <span id="displayEmail"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Department:</strong> <span id="displayDepartment"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Position:</strong> <span id="displayPosition"></span>
                                        </div>
                                    </div>
                                    <div id="currentAssignment" class="mt-2" style="display:none;">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Currently Assigned:</strong> <span id="displayCurrentStore"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Store <span class="text-danger">*</span></label>
                                <select class="form-select" id="requestStoreSelect" required>
                                    <option value="">-- Select Store --</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <select class="form-select" id="requestReason">
                                    <option value="New Assignment">New Assignment</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Replacement">Replacement</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="requestNotes" rows="2" placeholder="Add any additional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-secondary" id="backToLookup" onclick="backToStep1()" style="display:none;">
                            <i class="fas fa-arrow-left me-2"></i>Back to Lookup
                        </button>
                        <button type="button" class="btn btn-secondary" id="backToTerms" onclick="backToStep2()" style="display:none;">
                            <i class="fas fa-arrow-left me-2"></i>Back to Terms
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitRequest" style="display:none;">
                            <i class="fas fa-paper-plane me-2"></i>Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    <!-- Add Store Assignment Modal (New Simplified Flow) -->
    <div class="modal fade" id="addStoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-store me-2"></i>Assign Supervisor to Store</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="addStoreSupervisorId">
                    <input type="hidden" id="addStoreSupervisorName">
                    
                    <!-- Supervisor Info -->
                    <div class="alert alert-info">
                        <strong>Supervisor:</strong> <span id="addStoreSupervisorDisplay"></span>
                    </div>
                    
                    <!-- Step 1: Select Store -->
                    <div id="addStoreStep1">
                        <h6 class="mb-3">Step 1: Select Store</h6>
                        <div class="mb-3">
                            <label class="form-label">Available Stores in Your Area <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="addStoreSelect" onchange="loadStoreEquipment()">
                                <option value="">-- Select Store --</option>
                            </select>
                            <small class="text-muted">Only shows stores not already assigned to this supervisor</small>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-primary" id="nextToTermsBtn" onclick="showTermsStep()" disabled>
                                Next: Terms & Conditions <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Terms & Conditions -->
                    <div id="addStoreStep2" style="display:none;">
                        <h6 class="mb-3">Step 2: Equipment Verification Agreement</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Before assigning this supervisor, verify equipment conditions.
                        </div>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-clipboard-check me-2"></i>Verification Checklist</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="addCheck1">
                                    <label class="form-check-label" for="addCheck1">
                                        I have verified all equipment in this store is accounted for
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="addCheck2">
                                    <label class="form-check-label" for="addCheck2">
                                        I have inspected the physical condition of all equipment
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="addCheck3">
                                    <label class="form-check-label" for="addCheck3">
                                        All equipment is functional or damages are documented below
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="addCheck4">
                                    <label class="form-check-label" for="addCheck4">
                                        I understand the supervisor will be accountable for this equipment
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="showStoreStep()">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-primary" id="nextToEquipmentBtn" onclick="showEquipmentStep()" disabled>
                                Next: Review Equipment <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Equipment List & Condition -->
                    <div id="addStoreStep3" style="display:none;">
                        <h6 class="mb-3">Step 3: Equipment List & Condition Verification</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Review the equipment and mark condition for each item
                        </div>
                        
                        <div id="equipmentListContainer">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2">Loading equipment...</p>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label">Assignment Reason</label>
                            <select class="form-select mb-2" id="addStoreReason">
                                <option value="New Assignment">New Assignment</option>
                                <option value="Additional Store Coverage">Additional Store Coverage</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Replacement">Replacement</option>
                                <option value="Temporary">Temporary</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes / Documented Damages</label>
                            <textarea class="form-control" id="addStoreNotes" rows="3" placeholder="Document any equipment damages or special notes..."></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="showTermsStep()">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitAddStoreRequest()">
                                <i class="fas fa-paper-plane me-2"></i>Send Assignment Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reassign Store Modal -->
    <div class="modal fade" id="reassignStoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Reassign Store Supervisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reassignCurrentSupervisorId">
                    <input type="hidden" id="reassignStoreId">
                    
                    <!-- Store and Current Supervisor Info -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Store:</strong> <span id="reassignStoreName"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Current Supervisor:</strong> <span id="reassignCurrentSupervisorName"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 1: Remove Current Supervisor -->
                    <div id="reassignStep1">
                        <h6 class="mb-3">Step 1: Remove Current Supervisor</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            First, you must remove the current supervisor and verify equipment condition.
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-clipboard-check me-2"></i>Equipment Handover Verification</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignRemoveCheck1">
                                    <label class="form-check-label" for="reassignRemoveCheck1">
                                        I have conducted equipment handover inspection with current supervisor
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignRemoveCheck2">
                                    <label class="form-check-label" for="reassignRemoveCheck2">
                                        All equipment has been accounted for and documented
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignRemoveCheck3">
                                    <label class="form-check-label" for="reassignRemoveCheck3">
                                        Equipment condition has been verified and any damages documented
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignRemoveCheck4">
                                    <label class="form-check-label" for="reassignRemoveCheck4">
                                        Current supervisor has been notified of this reassignment
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for Reassignment <span class="text-danger">*</span></label>
                            <select class="form-select mb-2" id="reassignRemovalReason">
                                <option value="">-- Select Reason --</option>
                                <option value="Performance Issues">Performance Issues</option>
                                <option value="Transfer to Another Store">Transfer to Another Store</option>
                                <option value="Workload Balancing">Workload Balancing</option>
                                <option value="Supervisor Request">Supervisor Request</option>
                                <option value="Better Coverage">Better Coverage</option>
                                <option value="Other">Other</option>
                            </select>
                            <textarea class="form-control" id="reassignRemovalNotes" rows="3" placeholder="Provide detailed reason and any equipment condition notes..." required></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-warning" id="reassignRemoveBtn" onclick="processReassignRemoval()" disabled>
                                Remove Current Supervisor & Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Select New Supervisor -->
                    <div id="reassignStep2" style="display:none;">
                        <h6 class="mb-3">Step 2: Select New Supervisor</h6>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Current supervisor removed. Now select a new supervisor for this store.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Search by Employee Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="reassignEmployeeNumber" placeholder="e.g., EMP001">
                                <button type="button" class="btn btn-primary" onclick="lookupSupervisorForReassign()">
                                    <i class="fas fa-search me-2"></i>Lookup
                                </button>
                            </div>
                            <div id="reassignLookupError" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        
                        <div id="reassignSupervisorDetails" style="display:none;">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>New Supervisor Details</strong>
                                </div>
                                <div class="card-body">
                                    <input type="hidden" id="reassignNewSupervisorId">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <strong>Name:</strong> <span id="reassignNewName"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Employee #:</strong> <span id="reassignNewEmployeeNumber"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Email:</strong> <span id="reassignNewEmail"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Department:</strong> <span id="reassignNewDepartment"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-primary" onclick="proceedToReassignTerms()">
                                    Next: Terms & Equipment Review <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Terms for New Supervisor -->
                    <div id="reassignStep3" style="display:none;">
                        <h6 class="mb-3">Step 3: Terms & Equipment Review</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            The new supervisor must agree to equipment accountability terms.
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-clipboard-check me-2"></i>Equipment Acceptance Verification</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignNewCheck1">
                                    <label class="form-check-label" for="reassignNewCheck1">
                                        New supervisor will conduct equipment inspection upon acceptance
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignNewCheck2">
                                    <label class="form-check-label" for="reassignNewCheck2">
                                        New supervisor will be accountable for all equipment from acceptance date
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="reassignNewCheck3">
                                    <label class="form-check-label" for="reassignNewCheck3">
                                        Equipment list and conditions have been documented and will be shared
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Equipment List -->
                        <div id="reassignEquipmentList">
                            <h6 class="mb-2">Store Equipment</h6>
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="reassignAssignmentNotes" rows="2" placeholder="Any special instructions or notes for the new supervisor..."></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="backToReassignStep2()">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-success" id="reassignSendRequestBtn" onclick="sendReassignmentRequest()" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Send Assignment Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Removal Terms & Conditions Modal -->
    <div class="modal fade" id="removalTermsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-user-times me-2"></i>Permanently Remove Supervisor - Terms & Conditions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="removalSupervisorId">
                    <input type="hidden" id="removalSupervisorName">
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action will permanently remove the supervisor from ALL stores in your area. Use this only for resignations, terminations, or when the supervisor will no longer work as a supervisor.
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Equipment Verification Checklist</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check1" required>
                                <label class="form-check-label" for="check1">
                                    I have verified all equipment assigned to this supervisor is accounted for
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check2" required>
                                <label class="form-check-label" for="check2">
                                    I have inspected the condition of all equipment in the store
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check3" required>
                                <label class="form-check-label" for="check3">
                                    All equipment is in acceptable working condition or damages are documented
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check4" required>
                                <label class="form-check-label" for="check4">
                                    The supervisor has returned all store keys and access cards
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check5" required>
                                <label class="form-check-label" for="check5">
                                    I understand the supervisor will be notified of this removal
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason for Removal <span class="text-danger">*</span></label>
                        <select class="form-select mb-2" id="removalReasonSelect" required>
                            <option value="">-- Select Reason --</option>
                            <option value="Resignation">Resignation</option>
                            <option value="Termination">Termination</option>
                            <option value="Promotion (no longer supervisor)">Promotion (no longer supervisor)</option>
                            <option value="No show / Abandoned position">No show / Abandoned position</option>
                            <option value="Store closure (permanent)">Store closure (permanent)</option>
                            <option value="Contract ended">Contract ended</option>
                            <option value="Other">Other</option>
                        </select>
                        <textarea class="form-control" id="removalNotes" rows="3" placeholder="Additional notes or details..." required></textarea>
                        <small class="text-muted">This will PERMANENTLY remove the supervisor from ALL stores in your area</small>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-bell me-2"></i>
                        <strong>Notification:</strong> The supervisor will receive a notification about this removal and will be able to view (read-only) the equipment they previously managed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemovalBtn" onclick="confirmRemoval()" disabled>
                        <i class="fas fa-check me-2"></i>Confirm Removal
                    </button>
                </div>
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
                const storesResponse = await fetch('get_stores.php?area_id=' + currentAreaId);
                const storesData = await storesResponse.json();
                if (storesData.success) {
                    allStores = storesData.stores.filter(s => s.is_active);
                }

                // Load supervisors using dedicated API
                const supervisorsResponse = await fetch('get_supervisors.php?area_id=' + currentAreaId);
                const supervisorsData = await supervisorsResponse.json();
                if (supervisorsData.success) {
                    allSupervisors = supervisorsData.supervisors;
                    renderSupervisors(allSupervisors);
                    updateStats();
                } else {
                    console.error('Error loading supervisors:', supervisorsData.error);
                    document.getElementById('supervisorsContainer').innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>${supervisorsData.error || 'No supervisors found for this area'}
                            </div>
                        </div>
                    `;
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
                            <i class="fas fa-info-circle me-2"></i>No supervisors found in this area.
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = supervisors.map(supervisor => {
                const hasStores = supervisor.assigned_stores && supervisor.assigned_stores.length > 0;
                const badgeClass = hasStores ? 'assigned-badge' : 'unassigned-badge';
                
                // Build stores list HTML with individual remove buttons
                let storesHtml = '';
                if (hasStores) {
                    storesHtml = '<div class="mb-2">';
                    supervisor.assigned_stores.forEach(store => {
                        storesHtml += `
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <strong>${escapeHtml(store.store_name)}</strong>
                                    <br><small class="text-muted">${escapeHtml(store.store_code)}</small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning" onclick="event.stopPropagation(); reassignStore(${supervisor.id}, ${store.store_id}, '${escapeHtml(supervisor.full_name || supervisor.username)}', '${escapeHtml(store.store_name)}')" title="Reassign another supervisor to this store">
                                        <i class="fas fa-exchange-alt"></i> Reassign
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="event.stopPropagation(); removeFromStore(${supervisor.id}, ${store.store_id}, '${escapeHtml(supervisor.full_name || supervisor.username)}', '${escapeHtml(store.store_name)}')" title="Remove from this store only">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    storesHtml += '</div>';
                } else {
                    storesHtml = '<span class="text-warning">Not Assigned</span>';
                }
                
                return `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card supervisor-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">${escapeHtml(supervisor.full_name || supervisor.username)}</h6>
                                        <small class="text-muted">${escapeHtml(supervisor.employee_number)}</small>
                                    </div>
                                    <span class="badge ${badgeClass}">
                                        ${hasStores ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-exclamation-circle me-1"></i>'}
                                    </span>
                                </div>
                                <hr>
                                <div class="mb-2">
                                    <small class="text-muted">Assigned Stores (${hasStores ? supervisor.assigned_stores.length : 0}):</small>
                                    ${storesHtml}
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
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); openAddStoreModal(${supervisor.id}, '${escapeHtml(supervisor.full_name || supervisor.username)}')">
                                        <i class="fas fa-plus me-1"></i>Assign to ${hasStores ? 'Another' : 'a'} Store
                                    </button>
                                    ${hasStores ? `
                                        <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); removeAssignment(${supervisor.id}, '${escapeHtml(supervisor.full_name || supervisor.username)}')">
                                            <i class="fas fa-user-times me-1"></i>Remove from All Stores
                                        </button>
                                    ` : ''}
                                </div>
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
        
        // New Add Store Assignment Modal Functions
        let addStoreModal;
        let reassignStoreModal;
        let currentSupervisorAssignedStores = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            addStoreModal = new bootstrap.Modal(document.getElementById('addStoreModal'));
            reassignStoreModal = new bootstrap.Modal(document.getElementById('reassignStoreModal'));
            
            // Setup checkbox listeners for add store modal
            ['addCheck1', 'addCheck2', 'addCheck3', 'addCheck4'].forEach(id => {
                document.getElementById(id).addEventListener('change', updateAddStoreButtons);
            });
            
            // Setup checkbox listeners for reassign modal
            ['reassignRemoveCheck1', 'reassignRemoveCheck2', 'reassignRemoveCheck3', 'reassignRemoveCheck4'].forEach(id => {
                document.getElementById(id).addEventListener('change', updateReassignRemoveButton);
            });
            
            document.getElementById('reassignRemovalReason').addEventListener('change', updateReassignRemoveButton);
            document.getElementById('reassignRemovalNotes').addEventListener('input', updateReassignRemoveButton);
            
            ['reassignNewCheck1', 'reassignNewCheck2', 'reassignNewCheck3'].forEach(id => {
                document.getElementById(id).addEventListener('change', updateReassignSendButton);
            });
        });
        
        function updateReassignRemoveButton() {
            const allChecked = ['reassignRemoveCheck1', 'reassignRemoveCheck2', 'reassignRemoveCheck3', 'reassignRemoveCheck4']
                .every(id => document.getElementById(id).checked);
            const reasonSelected = document.getElementById('reassignRemovalReason').value !== '';
            const notesEntered = document.getElementById('reassignRemovalNotes').value.trim() !== '';
            
            document.getElementById('reassignRemoveBtn').disabled = !(allChecked && reasonSelected && notesEntered);
        }
        
        function updateReassignSendButton() {
            const allChecked = ['reassignNewCheck1', 'reassignNewCheck2', 'reassignNewCheck3']
                .every(id => document.getElementById(id).checked);
            
            document.getElementById('reassignSendRequestBtn').disabled = !allChecked;
        }
        
        // Remove supervisor from specific store
        async function removeFromStore(supervisorId, storeId, supervisorName, storeName) {
            if (!confirm(`Remove ${supervisorName} from ${storeName}?\n\nThey will be notified of this removal.`)) {
                return;
            }
            
            const reason = prompt('Reason for removal (required):', '');
            if (!reason || reason.trim() === '') {
                alert('Reason is required');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('supervisor_id', supervisorId);
                formData.append('store_id', storeId);
                formData.append('reason', reason);
                
                const response = await fetch('remove_supervisor_from_store.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>${supervisorName} removed from ${storeName}. Supervisor has been notified.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
                    
                    await loadData();
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error removing supervisor from store');
            }
        }
        
        // Reassign store to different supervisor
        async function reassignStore(currentSupervisorId, storeId, currentSupervisorName, storeName) {
            document.getElementById('reassignCurrentSupervisorId').value = currentSupervisorId;
            document.getElementById('reassignStoreId').value = storeId;
            document.getElementById('reassignCurrentSupervisorName').textContent = currentSupervisorName;
            document.getElementById('reassignStoreName').textContent = storeName;
            
            // Reset form
            ['reassignRemoveCheck1', 'reassignRemoveCheck2', 'reassignRemoveCheck3', 'reassignRemoveCheck4'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            document.getElementById('reassignRemovalReason').value = '';
            document.getElementById('reassignRemovalNotes').value = '';
            document.getElementById('reassignEmployeeNumber').value = '';
            document.getElementById('reassignLookupError').style.display = 'none';
            document.getElementById('reassignSupervisorDetails').style.display = 'none';
            
            // Show step 1
            document.getElementById('reassignStep1').style.display = 'block';
            document.getElementById('reassignStep2').style.display = 'none';
            document.getElementById('reassignStep3').style.display = 'none';
            
            updateReassignRemoveButton();
            reassignStoreModal.show();
        }
        
        async function processReassignRemoval() {
            const supervisorId = document.getElementById('reassignCurrentSupervisorId').value;
            const storeId = document.getElementById('reassignStoreId').value;
            const reason = document.getElementById('reassignRemovalReason').value + ': ' + document.getElementById('reassignRemovalNotes').value;
            
            try {
                const formData = new FormData();
                formData.append('supervisor_id', supervisorId);
                formData.append('store_id', storeId);
                formData.append('reason', reason);
                
                const response = await fetch('remove_supervisor_from_store.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Move to step 2
                    document.getElementById('reassignStep1').style.display = 'none';
                    document.getElementById('reassignStep2').style.display = 'block';
                    await loadData(); // Refresh supervisor list
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error removing current supervisor');
            }
        }
        
        async function lookupSupervisorForReassign() {
            const employeeNumber = document.getElementById('reassignEmployeeNumber').value.trim();
            const errorDiv = document.getElementById('reassignLookupError');
            
            if (!employeeNumber) {
                errorDiv.textContent = 'Please enter an employee number';
                errorDiv.style.display = 'block';
                return;
            }
            
            try {
                const response = await fetch('lookup_employee.php?employee_number=' + encodeURIComponent(employeeNumber));
                const data = await response.json();
                
                if (data.success && data.user) {
                    errorDiv.style.display = 'none';
                    
                    // Display supervisor details
                    document.getElementById('reassignNewSupervisorId').value = data.user.id;
                    document.getElementById('reassignNewName').textContent = data.user.full_name;
                    document.getElementById('reassignNewEmployeeNumber').textContent = data.user.employee_number;
                    document.getElementById('reassignNewEmail').textContent = data.user.email || 'N/A';
                    document.getElementById('reassignNewDepartment').textContent = data.user.department || 'N/A';
                    
                    document.getElementById('reassignSupervisorDetails').style.display = 'block';
                } else {
                    errorDiv.textContent = data.error || 'Supervisor not found';
                    errorDiv.style.display = 'block';
                    document.getElementById('reassignSupervisorDetails').style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error looking up supervisor';
                errorDiv.style.display = 'block';
            }
        }
        
        async function proceedToReassignTerms() {
            document.getElementById('reassignStep2').style.display = 'none';
            document.getElementById('reassignStep3').style.display = 'block';
            
            // Reset checkboxes
            ['reassignNewCheck1', 'reassignNewCheck2', 'reassignNewCheck3'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            updateReassignSendButton();
            
            // Load equipment for the store
            const storeId = document.getElementById('reassignStoreId').value;
            await loadEquipmentForReassign(storeId);
        }
        
        function backToReassignStep2() {
            document.getElementById('reassignStep3').style.display = 'none';
            document.getElementById('reassignStep2').style.display = 'block';
        }
        
        async function loadEquipmentForReassign(storeId) {
            const container = document.getElementById('reassignEquipmentList');
            
            try {
                const response = await fetch('get_store_equipment.php?store_id=' + storeId);
                const data = await response.json();
                
                if (data.success) {
                    if (data.equipment.length === 0) {
                        container.innerHTML = '<div class="alert alert-info">No equipment assigned to this store.</div>';
                    } else {
                        let html = `
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Serial #</th>
                                            <th>Qty</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.equipment.forEach(item => {
                            html += `
                                <tr>
                                    <td><strong>${escapeHtml(item.item_name)}</strong></td>
                                    <td>${escapeHtml(item.category_name || 'N/A')}</td>
                                    <td><code>${escapeHtml(item.serial_number)}</code></td>
                                    <td><span class="badge bg-info">${item.quantity}</span></td>
                                    <td><span class="badge bg-success">${escapeHtml(item.status)}</span></td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table></div>';
                        html += `<small class="text-muted">Total: ${data.equipment.length} items</small>`;
                        
                        container.innerHTML = html;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<div class="alert alert-danger">Error loading equipment</div>';
            }
        }
        
        async function sendReassignmentRequest() {
            const storeId = document.getElementById('reassignStoreId').value;
            const newSupervisorId = document.getElementById('reassignNewSupervisorId').value;
            const notes = document.getElementById('reassignAssignmentNotes').value;
            
            try {
                const formData = new FormData();
                formData.append('supervisor_user_id', newSupervisorId);
                formData.append('store_id', storeId);
                formData.append('reason', 'Reassignment');
                formData.append('notes', notes);
                
                const response = await fetch('create_supervisor_assignment_request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    reassignStoreModal.hide();
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>Reassignment request sent! The new supervisor will be notified.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
                    
                    await loadData();
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending reassignment request');
            }
        }
        
        function updateAddStoreButtons() {
            // Enable next to equipment button if all checkboxes are checked
            const allChecked = ['addCheck1', 'addCheck2', 'addCheck3', 'addCheck4']
                .every(id => document.getElementById(id).checked);
            document.getElementById('nextToEquipmentBtn').disabled = !allChecked;
        }
        
        async function openAddStoreModal(supervisorId, supervisorName) {
            const supervisor = allSupervisors.find(s => s.id === supervisorId);
            if (!supervisor) return;
            
            // Store current assignments
            currentSupervisorAssignedStores = supervisor.assigned_stores || [];
            
            document.getElementById('addStoreSupervisorId').value = supervisorId;
            document.getElementById('addStoreSupervisorName').value = supervisorName;
            document.getElementById('addStoreSupervisorDisplay').textContent = supervisorName;
            
            // Reset form
            document.getElementById('addStoreSelect').value = '';
            document.getElementById('addStoreReason').value = currentSupervisorAssignedStores.length > 0 ? 'Additional Store Coverage' : 'New Assignment';
            document.getElementById('addStoreNotes').value = '';
            ['addCheck1', 'addCheck2', 'addCheck3', 'addCheck4'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            
            // Show step 1
            showStoreStep();
            
            // Load available stores (exclude already assigned ones)
            await loadAvailableStores(supervisorId);
            
            addStoreModal.show();
        }
        
        async function loadAvailableStores(supervisorId) {
            try {
                const response = await fetch('get_stores.php?area_id=' + currentAreaId);
                const data = await response.json();
                
                if (data.success) {
                    const assignedStoreIds = currentSupervisorAssignedStores.map(s => s.store_id);
                    const availableStores = data.stores.filter(s => !assignedStoreIds.includes(s.store_id));
                    
                    const select = document.getElementById('addStoreSelect');
                    select.innerHTML = '<option value="">-- Select Store --</option>';
                    
                    if (availableStores.length === 0) {
                        select.innerHTML = '<option value="">No available stores (already assigned to all)</option>';
                        select.disabled = true;
                    } else {
                        availableStores.forEach(store => {
                            const option = document.createElement('option');
                            option.value = store.store_id;
                            option.textContent = `${store.store_name} (${store.store_code})`;
                            select.appendChild(option);
                        });
                        select.disabled = false;
                        
                        // Enable/disable next button
                        select.addEventListener('change', function() {
                            document.getElementById('nextToTermsBtn').disabled = this.value === '';
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading stores:', error);
            }
        }
        
        function showStoreStep() {
            document.getElementById('addStoreStep1').style.display = 'block';
            document.getElementById('addStoreStep2').style.display = 'none';
            document.getElementById('addStoreStep3').style.display = 'none';
        }
        
        function showTermsStep() {
            document.getElementById('addStoreStep1').style.display = 'none';
            document.getElementById('addStoreStep2').style.display = 'block';
            document.getElementById('addStoreStep3').style.display = 'none';
            updateAddStoreButtons();
        }
        
        async function showEquipmentStep() {
            document.getElementById('addStoreStep1').style.display = 'none';
            document.getElementById('addStoreStep2').style.display = 'none';
            document.getElementById('addStoreStep3').style.display = 'block';
            
            await loadStoreEquipment();
        }
        
        async function loadStoreEquipment() {
            const storeId = document.getElementById('addStoreSelect').value;
            if (!storeId) return;
            
            const container = document.getElementById('equipmentListContainer');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
            
            try {
                const response = await fetch('get_store_equipment.php?store_id=' + storeId);
                const data = await response.json();
                
                if (data.success) {
                    if (data.equipment.length === 0) {
                        container.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No equipment currently assigned to this store.
                            </div>
                        `;
                    } else {
                        let html = `
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Serial #</th>
                                            <th>Brand/Model</th>
                                            <th>Qty</th>
                                            <th>Status</th>
                                            <th width="150">Condition</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.equipment.forEach((item, index) => {
                            html += `
                                <tr>
                                    <td><strong>${escapeHtml(item.item_name)}</strong></td>
                                    <td><span class="badge bg-secondary">${escapeHtml(item.category_name || 'N/A')}</span></td>
                                    <td><code>${escapeHtml(item.serial_number)}</code></td>
                                    <td><small>${escapeHtml(item.brand || '')} ${escapeHtml(item.model || '')}</small></td>
                                    <td><span class="badge bg-info">${item.quantity}</span></td>
                                    <td><span class="badge bg-success">${escapeHtml(item.status)}</span></td>
                                    <td>
                                        <select class="form-select form-select-sm equipment-condition" data-item-id="${item.id}">
                                            <option value="Good">Good</option>
                                            <option value="Fair">Fair</option>
                                            <option value="Needs Repair">Needs Repair</option>
                                            <option value="Damaged">Damaged</option>
                                        </select>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Total: ${data.equipment.length} items</small>
                        `;
                        
                        container.innerHTML = html;
                    }
                } else {
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading equipment: ${data.error}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading equipment
                    </div>
                `;
            }
        }
        
        async function submitAddStoreRequest() {
            const supervisorId = document.getElementById('addStoreSupervisorId').value;
            const storeId = document.getElementById('addStoreSelect').value;
            const reason = document.getElementById('addStoreReason').value;
            const notes = document.getElementById('addStoreNotes').value;
            
            // Collect equipment conditions
            const equipmentConditions = [];
            document.querySelectorAll('.equipment-condition').forEach(select => {
                equipmentConditions.push({
                    item_id: select.dataset.itemId,
                    condition: select.value
                });
            });
            
            try {
                const formData = new FormData();
                formData.append('supervisor_user_id', supervisorId);
                formData.append('store_id', storeId);
                formData.append('reason', reason);
                formData.append('notes', notes + '\n\nEquipment Conditions: ' + JSON.stringify(equipmentConditions));
                
                const response = await fetch('create_supervisor_assignment_request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addStoreModal.hide();
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>Assignment request sent successfully! The supervisor will be notified.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
                    
                    await loadData();
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending request');
            }
        }
        
        // Remove assignment function - show terms modal
        let removalModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            removalModal = new bootstrap.Modal(document.getElementById('removalTermsModal'));
            
            // Enable/disable confirm button based on checkboxes
            const checkboxes = ['check1', 'check2', 'check3', 'check4', 'check5'];
            checkboxes.forEach(id => {
                document.getElementById(id).addEventListener('change', updateRemovalButton);
            });
            
            document.getElementById('removalReasonSelect').addEventListener('change', updateRemovalButton);
            document.getElementById('removalNotes').addEventListener('input', updateRemovalButton);
        });
        
        function updateRemovalButton() {
            const checkboxes = ['check1', 'check2', 'check3', 'check4', 'check5'];
            const allChecked = checkboxes.every(id => document.getElementById(id).checked);
            const reasonSelected = document.getElementById('removalReasonSelect').value !== '';
            const notesEntered = document.getElementById('removalNotes').value.trim() !== '';
            
            document.getElementById('confirmRemovalBtn').disabled = !(allChecked && reasonSelected && notesEntered);
        }
        
        function removeAssignment(supervisorId, supervisorName) {
            // Store data for later use
            document.getElementById('removalSupervisorId').value = supervisorId;
            document.getElementById('removalSupervisorName').value = supervisorName;
            
            // Reset checkboxes and form
            ['check1', 'check2', 'check3', 'check4', 'check5'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            document.getElementById('removalReasonSelect').value = '';
            document.getElementById('removalNotes').value = '';
            updateRemovalButton();
            
            // Show modal
            removalModal.show();
        }
        
        async function confirmRemoval() {
            const supervisorId = document.getElementById('removalSupervisorId').value;
            const supervisorName = document.getElementById('removalSupervisorName').value;
            const reasonSelect = document.getElementById('removalReasonSelect').value;
            const notes = document.getElementById('removalNotes').value;
            const reason = reasonSelect + (notes ? ': ' + notes : '');
            
            const confirmBtn = document.getElementById('confirmRemovalBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Removing...';
            
            try {
                const formData = new FormData();
                formData.append('supervisor_id', supervisorId);
                if (reason) {
                    formData.append('reason', reason);
                }
                
                const response = await fetch('remove_supervisor_assignment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Hide modal
                    removalModal.hide();
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>${data.message}. The supervisor has been notified.
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
                console.error('Error removing assignment:', error);
                alert('Error: ' + error.message);
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm Removal';
            }
        }
        
        // Employee lookup and assignment request functions
        let requestModal;
        let selectedSupervisorData = null;
        let isReassignment = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            requestModal = new bootstrap.Modal(document.getElementById('requestAssignmentModal'));
            
            // Load stores for request modal
            loadStoresForRequest();
            
            // Enable/disable terms accept button based on checkboxes
            const reqCheckboxes = ['reqCheck1', 'reqCheck2', 'reqCheck3', 'reqCheck4'];
            reqCheckboxes.forEach(id => {
                document.getElementById(id).addEventListener('change', updateTermsButton);
            });
            
            // Reset modal on close
            document.getElementById('requestAssignmentModal').addEventListener('hidden.bs.modal', function() {
                resetRequestModal();
            });
        });
        
        function updateTermsButton() {
            const reqCheckboxes = ['reqCheck1', 'reqCheck2', 'reqCheck3', 'reqCheck4'];
            const allChecked = reqCheckboxes.every(id => document.getElementById(id).checked);
            document.getElementById('acceptTermsBtn').disabled = !allChecked;
        }
        
        function acceptTerms() {
            // Move to step 3 (assignment form)
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'block';
            document.getElementById('backToLookup').style.display = 'none';
            document.getElementById('backToTerms').style.display = 'inline-block';
            document.getElementById('submitRequest').style.display = 'inline-block';
        }
        
        function backToStep2() {
            document.getElementById('step3').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            document.getElementById('backToTerms').style.display = 'none';
            document.getElementById('backToLookup').style.display = 'inline-block';
            document.getElementById('submitRequest').style.display = 'none';
        }
        
        async function loadStoresForRequest() {
            try {
                const response = await fetch('get_stores.php?area_id=' + currentAreaId);
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('requestStoreSelect');
                    select.innerHTML = '<option value="">-- Select Store --</option>';
                    data.stores.forEach(store => {
                        const option = document.createElement('option');
                        option.value = store.store_id;
                        option.textContent = `${store.store_name} (${store.store_code})`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading stores:', error);
            }
        }
        
        async function lookupEmployee() {
            const employeeNumber = document.getElementById('employeeNumberInput').value.trim();
            const errorDiv = document.getElementById('employeeLookupError');
            
            if (!employeeNumber) {
                errorDiv.textContent = 'Please enter an employee number';
                errorDiv.style.display = 'block';
                return;
            }
            
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch(`lookup_employee.php?employee_number=${encodeURIComponent(employeeNumber)}`);
                const data = await response.json();
                
                if (data.success) {
                    selectedSupervisorData = data.user;
                    displaySupervisorDetails(data.user);
                } else {
                    errorDiv.textContent = data.error || 'Employee not found';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'Error looking up employee';
                errorDiv.style.display = 'block';
                console.error('Lookup error:', error);
            }
        }
        
        function displaySupervisorDetails(user) {
            selectedSupervisorData = user;
            document.getElementById('selectedSupervisorId').value = user.id;
            document.getElementById('displayName').textContent = user.full_name;
            document.getElementById('displayEmployeeNumber').textContent = user.employee_number;
            document.getElementById('displayUsername').textContent = user.username;
            document.getElementById('displayEmail').textContent = user.email || 'N/A';
            document.getElementById('displayDepartment').textContent = user.department || 'N/A';
            document.getElementById('displayPosition').textContent = user.position || 'N/A';
            
            // Show current assignment if any
            if (user.is_assigned && user.current_store) {
                document.getElementById('displayCurrentStore').textContent = 
                    `${user.current_store.store_name} (${user.current_store.store_code})`;
                document.getElementById('currentAssignment').style.display = 'block';
                isReassignment = true;
                document.getElementById('requestReason').value = 'Transfer';
            } else {
                document.getElementById('currentAssignment').style.display = 'none';
                isReassignment = false;
                document.getElementById('requestReason').value = 'New Assignment';
            }
            
            // Show step 2 (terms)
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            document.getElementById('backToLookup').style.display = 'inline-block';
        }
        
        function backToStep1() {
            document.getElementById('step1').style.display = 'block';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'none';
            document.getElementById('backToLookup').style.display = 'none';
            document.getElementById('backToTerms').style.display = 'none';
            document.getElementById('submitRequest').style.display = 'none';
            selectedSupervisorData = null;
            
            // Reset checkboxes
            ['reqCheck1', 'reqCheck2', 'reqCheck3', 'reqCheck4'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            updateTermsButton();
        }
        
        function resetRequestModal() {
            document.getElementById('requestAssignmentForm').reset();
            document.getElementById('employeeLookupError').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'none';
            document.getElementById('backToLookup').style.display = 'none';
            document.getElementById('backToTerms').style.display = 'none';
            document.getElementById('submitRequest').style.display = 'none';
            selectedSupervisorData = null;
            isReassignment = false;
            
            // Reset checkboxes
            ['reqCheck1', 'reqCheck2', 'reqCheck3', 'reqCheck4'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            updateTermsButton();
        }
        
        // Handle request assignment form submission
        document.getElementById('requestAssignmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!selectedSupervisorData) {
                alert('Please lookup a supervisor first');
                return;
            }
            
            const storeId = document.getElementById('requestStoreSelect').value;
            const reason = document.getElementById('requestReason').value;
            const notes = document.getElementById('requestNotes').value;
            
            if (!storeId) {
                alert('Please select a store');
                return;
            }
            
            const submitBtn = document.getElementById('submitRequest');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            
            try {
                const formData = new FormData();
                formData.append('supervisor_user_id', selectedSupervisorData.id);
                formData.append('store_id', storeId);
                formData.append('reason', reason);
                formData.append('notes', notes);
                
                const response = await fetch('create_supervisor_assignment_request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Assignment request sent successfully! The supervisor will receive a notification.');
                    requestModal.hide();
                    resetRequestModal();
                    loadData(); // Reload supervisor list
                } else if (data.error_type === 'already_assigned' && data.requires_confirmation) {
                    // Supervisor already assigned to another store
                    if (confirm(`${data.error}\\n\\nDo you want to proceed with reassignment?`)) {
                        formData.append('force_reassign', 'true');
                        const retryResponse = await fetch('create_supervisor_assignment_request.php', {
                            method: 'POST',
                            body: formData
                        });
                        const retryData = await retryResponse.json();
                        
                        if (retryData.success) {
                            alert('Reassignment request sent successfully!');
                            requestModal.hide();
                            resetRequestModal();
                            loadData();
                        } else {
                            alert('Error: ' + retryData.error);
                        }
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error sending request');
                console.error('Error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Request';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
