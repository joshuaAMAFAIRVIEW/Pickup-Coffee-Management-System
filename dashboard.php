<?php
// Dashboard page using the shared wrapper for sidebar/navbar
require_once __DIR__ . '/config.php';

// Refresh user session to get latest data (e.g., newly assigned area_id)
if (isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($freshUser) {
        $_SESSION['user'] = $freshUser;
    }
}

include __DIR__ . '/dashboard_nav_wrapper_start.php';
// $user is available from the wrapper

// Check if user is area manager
$isAreaManager = isset($user['role']) && $user['role'] === 'area_manager';

if (!$isAreaManager) {
  // Fetch stats for regular dashboard
  try {
    // Total items (check if items table exists)
    $tblCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'items'");
    $tblCheck->execute([':db' => DB_NAME]);
    $hasItemsTable = (bool)$tblCheck->fetchColumn();
    
    $totalItems = 0;
    $borrowedItems = 0;
    if ($hasItemsTable) {
      $totalItems = $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
      $borrowedItems = $pdo->query('SELECT COUNT(*) FROM items WHERE assigned_user_id IS NOT NULL')->fetchColumn();
    } else {
      $totalItems = $pdo->query('SELECT COUNT(*) FROM inventory')->fetchColumn();
    }
    
    // Total users
    $totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    // Total categories
    $catCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'categories'");
    $catCheck->execute([':db' => DB_NAME]);
    $hasCategoriesTable = (bool)$catCheck->fetchColumn();
    $totalCategories = $hasCategoriesTable ? $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() : 0;
    
    // Borrowed items by region (for graph)
    $regionCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'region'");
    $regionCheck->execute([':db' => DB_NAME]);
    $hasRegionColumn = (bool)$regionCheck->fetchColumn();
    
    $regionData = [];
    $selectedRegion = $_GET['region'] ?? '';
    if ($hasItemsTable && $hasRegionColumn) {
      if ($selectedRegion && $selectedRegion !== '') {
        $stmt = $pdo->prepare("
          SELECT u.region, COUNT(i.id) as count
          FROM items i
          INNER JOIN users u ON i.assigned_user_id = u.id
          WHERE u.region = :region
          GROUP BY u.region
          ORDER BY u.region
        ");
        $stmt->execute([':region' => $selectedRegion]);
        $regionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
        $stmt = $pdo->query("
          SELECT u.region, COUNT(i.id) as count
          FROM items i
          INNER JOIN users u ON i.assigned_user_id = u.id
          WHERE u.region IS NOT NULL AND u.region != ''
          GROUP BY u.region
          ORDER BY u.region
        ");
        $regionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }
    
  } catch (Exception $e) {
    $totalItems = 0;
    $borrowedItems = 0;
    $totalUsers = 0;
    $totalCategories = 0;
    $regionData = [];
  }

  // Prepare data for Chart.js
  $regionLabels = array_column($regionData, 'region');
  $regionCounts = array_column($regionData, 'count');
}
?>

  <div class="mb-4">
    <h1 class="h3 mb-1">Dashboard</h1>
    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['username']); ?>. Role: <?php echo htmlspecialchars($user['role']); ?></p>
  </div>

<?php if ($isAreaManager): ?>
  <!-- Area Manager Dashboard: Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Store Active</h6>
              <h3 class="mb-0" id="totalStores">-</h3>
            </div>
            <div class="ms-3">
              <div class="bg-primary bg-opacity-10 rounded p-3">
                <i class="fas fa-store fa-2x text-primary"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Area Assigned</h6>
              <h3 class="mb-0" id="areaName">-</h3>
            </div>
            <div class="ms-3">
              <div class="bg-info bg-opacity-10 rounded p-3">
                <i class="fas fa-map-marked-alt fa-2x text-info"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Store Supervisors</h6>
              <h3 class="mb-0" id="totalSupervisors">-</h3>
            </div>
            <div class="ms-3">
              <div class="bg-success bg-opacity-10 rounded p-3">
                <i class="fas fa-users fa-2x text-success"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Store Cards -->
  <div class="mb-3">
    <h5>Your Stores</h5>
    <p class="text-muted small">Click on a store card to view details and manage supervisors</p>
  </div>
  
  <div id="storeCardsContainer">
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading stores...</span>
      </div>
    </div>
  </div>

  <!-- Modals for Area Manager -->
  <!-- Reassign Store Modal (from manage_team.php) -->
  <div class="modal fade" id="reassignStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Reassign Store Supervisor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="reassignCurrentSupervisorId">
          <input type="hidden" id="reassignStoreId">
          <input type="hidden" id="reassignNewSupervisorId">
          
          <!-- Current Assignment Alert -->
          <div class="alert alert-info">
            <strong>Current:</strong> <span id="reassignCurrentSupervisorName"></span> → 
            <strong>Store:</strong> <span id="reassignStoreName"></span>
          </div>
          
          <!-- Step 1: Remove Current Supervisor -->
          <div id="reassignStep1">
            <h6 class="mb-3">Step 1: Remove Current Supervisor</h6>
            <p class="text-muted small">Verify equipment handover before removing supervisor</p>
            
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignRemoveCheck1">
                <label class="form-check-label" for="reassignRemoveCheck1">
                  All equipment items have been accounted for
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignRemoveCheck2">
                <label class="form-check-label" for="reassignRemoveCheck2">
                  Equipment condition has been documented
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignRemoveCheck3">
                <label class="form-check-label" for="reassignRemoveCheck3">
                  Store keys and access cards have been returned
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignRemoveCheck4">
                <label class="form-check-label" for="reassignRemoveCheck4">
                  Supervisor has signed handover acknowledgment
                </label>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Reason for Removal <span class="text-danger">*</span></label>
              <select class="form-select" id="reassignRemovalReason" required>
                <option value="">Select reason...</option>
                <option value="Reassignment">Reassignment to Another Store</option>
                <option value="Performance Issues">Performance Issues</option>
                <option value="Relocation">Employee Relocation</option>
                <option value="Promotion">Promotion</option>
                <option value="Other">Other</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Additional Notes <span class="text-danger">*</span></label>
              <textarea class="form-control" id="reassignRemovalNotes" rows="3" required></textarea>
            </div>
            
            <button type="button" class="btn btn-primary" id="reassignRemoveBtn" disabled onclick="processReassignRemoval()">
              Remove & Continue
            </button>
          </div>
          
          <!-- Step 2: Lookup New Supervisor -->
          <div id="reassignStep2" style="display:none;">
            <h6 class="mb-3">Step 2: Select New Supervisor</h6>
            <p class="text-muted small">Search for the new supervisor by employee number</p>
            
            <div class="mb-3">
              <label class="form-label">Employee Number</label>
              <div class="input-group">
                <input type="text" class="form-control" id="reassignEmployeeNumber" placeholder="Enter employee number">
                <button class="btn btn-primary" type="button" onclick="lookupSupervisorForReassign()">
                  <i class="fas fa-search"></i> Search
                </button>
              </div>
              <div class="alert alert-danger mt-2" id="reassignLookupError" style="display:none;"></div>
            </div>
            
            <div id="reassignSupervisorDetails" style="display:none;">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Supervisor Details</h6>
                  <table class="table table-sm table-borderless mb-0">
                    <tr>
                      <td class="text-muted">Name:</td>
                      <td><strong id="reassignNewName"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Employee #:</td>
                      <td><strong id="reassignNewEmployeeNumber"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Email:</td>
                      <td id="reassignNewEmail"></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Department:</td>
                      <td id="reassignNewDepartment"></td>
                    </tr>
                  </table>
                </div>
              </div>
              <button type="button" class="btn btn-primary mt-3" onclick="proceedToReassignTerms()">
                Continue to Terms <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </div>
          
          <!-- Step 3: New Supervisor Terms & Equipment -->
          <div id="reassignStep3" style="display:none;">
            <h6 class="mb-3">Step 3: Assignment Terms & Equipment Review</h6>
            <p class="text-muted small">New supervisor must agree to terms and review equipment</p>
            
            <div class="alert alert-warning">
              <strong>New Supervisor:</strong> <span id="reassignNewSupervisorDisplayName"></span>
            </div>
            
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignNewCheck1">
                <label class="form-check-label" for="reassignNewCheck1">
                  I acknowledge full accountability for all equipment at this store
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignNewCheck2">
                <label class="form-check-label" for="reassignNewCheck2">
                  I will conduct inventory verification within 24 hours
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reassignNewCheck3">
                <label class="form-check-label" for="reassignNewCheck3">
                  I agree to maintain and safeguard all store equipment
                </label>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Equipment Assigned to This Store</label>
              <div id="reassignEquipmentList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                <div class="text-center text-muted">Loading equipment...</div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Assignment Notes (Optional)</label>
              <textarea class="form-control" id="reassignAssignmentNotes" rows="3"></textarea>
            </div>
            
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-secondary" onclick="backToReassignStep2()">
                <i class="fas fa-arrow-left"></i> Back
              </button>
              <button type="button" class="btn btn-primary" id="reassignSendRequestBtn" disabled onclick="sendReassignmentRequest()">
                Send Assignment Request
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Store Details Modal -->
  <div class="modal fade" id="storeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #AAC27F; color: white;">
          <div class="d-flex justify-content-between align-items-start flex-grow-1 me-3">
            <div>
              <h5 class="modal-title mb-2" id="storeDetailsTitle">Store Details</h5>
              <div class="text-muted small">
                <span id="headerStoreCode"></span> | <span id="headerAddress"></span>
              </div>
            </div>
            <div class="text-end">
              <div class="text-muted small mb-1">OPERATION HOURS (12 hours format)</div>
              <div class="d-flex align-items-center justify-content-end">
                <h6 class="mb-0 text-primary" id="detailOperationHours">Not set</h6>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="editOperationHours()" title="Edit Operation Hours">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Contact Information -->
          <div class="row mt-3 pt-3 border-top">
            <div class="col-md-4">
              <div class="text-muted small mb-1">CONTACT PERSON</div>
              <div class="d-flex align-items-center">
                <h6 class="mb-0" id="detailContactPerson">Not set</h6>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="editContactInfo()" title="Edit Contact Info">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-muted small mb-1">EMPLOYEE NUMBER</div>
              <h6 class="mb-0 text-info" id="detailContactEmployeeNumber">Not set</h6>
            </div>
            <div class="col-md-4">
              <div class="text-muted small mb-1">CONTACT NUMBER</div>
              <h6 class="mb-0 text-primary" id="detailContactNumber">Not set</h6>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="currentStoreId">
          
          <!-- Assigned Personnel Section -->
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0"><i class="fas fa-users me-2"></i>Assigned Personnel</h6>
              <button id="assignButtonTop" class="btn btn-sm btn-success" onclick="openAssignSupervisorModal()" style="display: none;">
                <i class="fas fa-plus me-1"></i>Assign Supervisor
              </button>
            </div>
            <div id="assignedPersonnelList" class="d-flex flex-wrap gap-2">
              <!-- Supervisor badges will be inserted here -->
            </div>
          </div>
          
          <!-- Store Information -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="card-title mb-3"><i class="fas fa-info-circle me-2"></i>Store Information</h6>
                  <table class="table table-sm table-borderless mb-0">
                    <tr>
                      <td class="text-muted" style="width: 150px;">Store Name:</td>
                      <td><strong id="detailStoreName"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Area:</td>
                      <td><span class="badge bg-primary" id="detailAreaName"></span></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="card-title mb-3"><i class="fas fa-box me-2"></i>Store Equipment</h6>
                  <div id="detailEquipmentSummary">
                    <p class="text-muted">Loading...</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Equipment Section -->
          <div class="card">
            <div class="card-header bg-white">
              <h6 class="mb-0"><i class="fas fa-box me-2"></i>Equipment Assigned to This Store</h6>
            </div>
            <div class="card-body">
              <div id="detailEquipmentList">
                <div class="text-center py-3">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading equipment...</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Operation Hours Modal -->
  <div class="modal fade" id="editOperationHoursModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #AAC27F; color: white;">
          <h5 class="modal-title">Edit Operation Hours</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editOperationStoreId">
          <div class="alert alert-info">
            <strong>Store:</strong> <span id="editOperationStoreName"></span>
          </div>
          <p class="text-muted small">Set the daily operation hours (same hours every day)</p>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Opening Time</label>
              <input type="time" class="form-control" id="editOpeningTime" value="07:00">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Closing Time</label>
              <input type="time" class="form-control" id="editClosingTime" value="22:00">
            </div>
          </div>
          <small class="text-muted">Use 24-hour time format (e.g., 07:00 for 7 AM, 19:00 for 7 PM)</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveOperationHours()">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Contact Info Modal -->
  <div class="modal fade" id="editContactInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #AAC27F; color: white;">
          <h5 class="modal-title">Edit Contact Information</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editContactStoreId">
          <div class="alert alert-info">
            <strong>Store:</strong> <span id="editContactStoreName"></span>
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Person Name</label>
            <input type="text" class="form-control" id="editContactPersonInput" placeholder="Enter contact person name" maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label">Employee Number</label>
            <input type="text" class="form-control" id="editContactEmployeeNumberInput" placeholder="e.g., EMP-001" maxlength="50">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="editContactNumberInput" placeholder="e.g., 09171234567" maxlength="20">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveContactInfo()">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Supervisor (IN) Modal -->
  <div class="modal fade" id="assignSupervisorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #AAC27F; color: white;">
          <h5 class="modal-title">
            <i class="fas fa-user-plus me-2"></i>Assign Supervisor (IN)
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Step 1: Terms and Conditions -->
          <div id="assignStep1">
            <div class="alert alert-info mb-3">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Store:</strong> <span id="assignStoreName"></span>
            </div>
            
            <h6 class="fw-bold mb-3">Asset Management Terms and Conditions</h6>
            <div class="border rounded p-3 mb-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
              <h6 class="fw-bold">1. Purpose</h6>
              <p class="small">These Terms and Conditions outline the responsibilities and obligations regarding the proper handling, monitoring, and accountability of all company-owned assets assigned to employees ("Personnel").</p>
              
              <h6 class="fw-bold">2. Recording and Logging of Asset Changes</h6>
              <p class="small mb-1">2.1. All changes, updates, or modifications to any company asset or device shall be recorded and logged exclusively by the designated Asset Management Administrator (Admin).</p>
              <p class="small">2.2. Personnel are prohibited from altering, deleting, or falsifying any logged information.</p>
              
              <h6 class="fw-bold">3. Device Inspection and Tagging</h6>
              <p class="small mb-1">3.1. All devices must undergo proper inspection prior to issuance, return, or reassignment.</p>
              <p class="small mb-1">3.2. Each device must be properly tagged to reflect its accurate and current condition (e.g., Good, For Repair, Damaged, Under Warranty Check).</p>
              <p class="small">3.3. Personnel receiving the device must acknowledge and confirm the stated device condition upon issuance.</p>
              
              <h6 class="fw-bold">4. Accountability of Assigned Personnel</h6>
              <p class="small mb-1">4.1. The personnel to whom the device is tagged and assigned shall be held fully accountable for the safekeeping, proper use, and timely reporting of any issues concerning the device.</p>
              <p class="small">4.2. Any loss, damage, or misuse of an assigned device may result in administrative evaluation and possible liability, subject to company policies.</p>
              
              <h6 class="fw-bold">5. Falsification of Device Condition</h6>
              <p class="small mb-1">5.1. Any falsification, misrepresentation, or intentional omission of device condition—whether upon issuance, return, or audit—will result in an official investigation.</p>
              <p class="small mb-1">5.2. Personnel found responsible for falsification will be subject to corrective action or disciplinary measures as deemed appropriate by the Human Resources (HR) Department.</p>
              <p class="small">5.3. Disciplinary action may include written warnings, suspension, restitution for damages, or other sanctions based on company policy.</p>
              
              <h6 class="fw-bold">6. Compliance</h6>
              <p class="small mb-1">6.1. All personnel are required to comply with this Asset Management policy at all times.</p>
              <p class="small">6.2. Failure to adhere to the processes and responsibilities outlined herein may lead to administrative action.</p>
              
              <h6 class="fw-bold">7. Acknowledgment</h6>
              <p class="small mb-0">By receiving or using any company-issued asset, the personnel acknowledges that they have read, understood, and agreed to abide by these Terms and Conditions.</p>
            </div>
            
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="assignTermsAccept">
              <label class="form-check-label" for="assignTermsAccept">
                <strong>I have read and agree to the Terms and Conditions</strong>
              </label>
            </div>
          </div>

          <!-- Step 2: Employee Search -->
          <div id="assignStep2" style="display: none;">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Store:</strong> <span id="assignStoreName2"></span>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Employee Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" class="form-control" id="assignEmployeeNumber" placeholder="Enter employee number">
                <button class="btn btn-primary" onclick="searchEmployee()">
                  <i class="fas fa-search"></i> Search
                </button>
              </div>
              <small class="text-muted">Only OPERATION department employees can be assigned</small>
            </div>

            <div id="assignEmployeeDetails" style="display: none;">
              <div class="card bg-light mb-3">
                <div class="card-body">
                  <h6 class="card-title">Employee Details</h6>
                  <table class="table table-sm table-borderless mb-0">
                    <tr>
                      <td class="text-muted" style="width: 150px;">Name:</td>
                      <td><strong id="assignEmployeeName"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Employee #:</td>
                      <td><code id="assignEmployeeNum"></code></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Department:</td>
                      <td><span class="badge bg-info" id="assignEmployeeRole"></span></td>
                    </tr>
                  </table>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Assign As <span class="text-danger">*</span></label>
                <select class="form-select" id="assignSupervisorRole">
                  <option value="">-- Select Role --</option>
                  <option value="store_supervisor">Store Supervisor</option>
                  <option value="oic">Officer In Charge (OIC)</option>
                </select>
                <small class="text-muted">Select the role for this assignment</small>
              </div>

              <input type="hidden" id="assignEmployeeId">
            </div>
          </div>

          <!-- Step 3: Equipment Condition -->
          <div id="assignStep3" style="display: none;">
            <div class="alert alert-warning">
              <i class="fas fa-clipboard-check me-2"></i>
              <strong>Equipment Condition Check (IN)</strong><br>
              <small>Mark the condition of each equipment before supervisor takes over</small>
            </div>
            
            <div id="assignEquipmentList">
              <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <p class="text-muted small mt-2">Loading equipment...</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="assignNextBtn" class="btn btn-primary" onclick="assignNextStep()" style="display: none;">
            Next <i class="fas fa-arrow-right ms-1"></i>
          </button>
          <button type="button" id="assignSubmitBtn" class="btn btn-success" onclick="submitAssignment()" style="display: none;">
            <i class="fas fa-check"></i> Submit Assignment
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Remove Supervisor (OUT) Modal -->
  <div class="modal fade" id="removeSupervisorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #AAC27F; color: white;">
          <h5 class="modal-title">
            <i class="fas fa-user-minus me-2"></i>Remove Supervisor (OUT)
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Step 1: Terms and Conditions -->
          <div id="removeStep1">
            <div class="alert alert-warning mb-3">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Removing:</strong> <span id="removeSupervisorName"></span><br>
              <strong>From Store:</strong> <span id="removeStoreName"></span>
            </div>
            
            <h6 class="fw-bold mb-3">Asset Management Terms and Conditions</h6>
            <div class="border rounded p-3 mb-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
              <h6 class="fw-bold">1. Purpose</h6>
              <p class="small">These Terms and Conditions outline the responsibilities and obligations regarding the proper handling, monitoring, and accountability of all company-owned assets assigned to employees ("Personnel").</p>
              
              <h6 class="fw-bold">2. Recording and Logging of Asset Changes</h6>
              <p class="small mb-1">2.1. All changes, updates, or modifications to any company asset or device shall be recorded and logged exclusively by the designated Asset Management Administrator (Admin).</p>
              <p class="small">2.2. Personnel are prohibited from altering, deleting, or falsifying any logged information.</p>
              
              <h6 class="fw-bold">3. Device Inspection and Tagging</h6>
              <p class="small mb-1">3.1. All devices must undergo proper inspection prior to issuance, return, or reassignment.</p>
              <p class="small mb-1">3.2. Each device must be properly tagged to reflect its accurate and current condition (e.g., Good, For Repair, Damaged, Under Warranty Check).</p>
              <p class="small">3.3. Personnel receiving the device must acknowledge and confirm the stated device condition upon issuance.</p>
              
              <h6 class="fw-bold">4. Accountability of Assigned Personnel</h6>
              <p class="small mb-1">4.1. The personnel to whom the device is tagged and assigned shall be held fully accountable for the safekeeping, proper use, and timely reporting of any issues concerning the device.</p>
              <p class="small">4.2. Any loss, damage, or misuse of an assigned device may result in administrative evaluation and possible liability, subject to company policies.</p>
              
              <h6 class="fw-bold">5. Falsification of Device Condition</h6>
              <p class="small mb-1">5.1. Any falsification, misrepresentation, or intentional omission of device condition—whether upon issuance, return, or audit—will result in an official investigation.</p>
              <p class="small mb-1">5.2. Personnel found responsible for falsification will be subject to corrective action or disciplinary measures as deemed appropriate by the Human Resources (HR) Department.</p>
              <p class="small">5.3. Disciplinary action may include written warnings, suspension, restitution for damages, or other sanctions based on company policy.</p>
              
              <h6 class="fw-bold">6. Compliance</h6>
              <p class="small mb-1">6.1. All personnel are required to comply with this Asset Management policy at all times.</p>
              <p class="small">6.2. Failure to adhere to the processes and responsibilities outlined herein may lead to administrative action.</p>
              
              <h6 class="fw-bold">7. Acknowledgment</h6>
              <p class="small mb-0">By receiving or using any company-issued asset, the personnel acknowledges that they have read, understood, and agreed to abide by these Terms and Conditions.</p>
            </div>
            
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="removeTermsAccept">
              <label class="form-check-label" for="removeTermsAccept">
                <strong>I have read and agree to the Terms and Conditions</strong>
              </label>
            </div>

            <input type="hidden" id="removeSupervisorId">
            <input type="hidden" id="removeStoreId">
          </div>

          <!-- Step 2: Removal Reason -->
          <div id="removeStep2" style="display: none;">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Removing:</strong> <span id="removeSupervisorName2"></span><br>
              <strong>From Store:</strong> <span id="removeStoreName2"></span>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Reason for Removal <span class="text-danger">*</span></label>
              <select class="form-select" id="removeReason">
                <option value="">-- Select Reason --</option>
                <option value="re-assign">Re-assign</option>
                <option value="resign">Resign</option>
                <option value="force_remove">Force to Remove</option>
              </select>
            </div>
          </div>

          <!-- Step 3: Equipment Condition -->
          <div id="removeStep3" style="display: none;">
            <div class="alert alert-danger">
              <i class="fas fa-clipboard-check me-2"></i>
              <strong>Equipment Condition Check (OUT)</strong><br>
              <small>Mark the condition of each equipment upon supervisor turnover</small>
            </div>
            
            <div id="removeEquipmentList">
              <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <p class="text-muted small mt-2">Loading equipment...</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="removeNextBtn" class="btn btn-primary" onclick="removeNextStep()" style="display: none;">
            Next <i class="fas fa-arrow-right ms-1"></i>
          </button>
          <button type="button" id="removeSubmitBtn" class="btn btn-danger" onclick="submitRemoval()" style="display: none;">
            <i class="fas fa-check"></i> Submit Removal
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Store Assignment Modal -->
  <div class="modal fade" id="addStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Assign Supervisor to Store</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="addStoreSupervisorId">
          <input type="hidden" id="addStoreStoreId">
          
          <!-- Store Info Alert -->
          <div class="alert alert-info" id="addStoreAlert">
            <strong>Store:</strong> <span id="addStoreStoreName"></span>
          </div>
          
          <!-- Step 1: Employee Lookup -->
          <div id="addStep1">
            <h6 class="mb-3">Step 1: Find Supervisor</h6>
            <div class="mb-3">
              <label class="form-label">Employee Number</label>
              <div class="input-group">
                <input type="text" class="form-control" id="addEmployeeNumber" placeholder="Enter employee number">
                <button class="btn btn-primary" type="button" onclick="lookupEmployeeForAdd()">
                  <i class="fas fa-search"></i> Search
                </button>
              </div>
              <div class="alert alert-danger mt-2" id="addLookupError" style="display:none;"></div>
            </div>
            
            <div id="addSupervisorDetails" style="display:none;">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Supervisor Details</h6>
                  <table class="table table-sm table-borderless mb-0">
                    <tr>
                      <td class="text-muted">Name:</td>
                      <td><strong id="addSupervisorName"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Employee #:</td>
                      <td><strong id="addSupervisorEmpNum"></strong></td>
                    </tr>
                    <tr>
                      <td class="text-muted">Email:</td>
                      <td id="addSupervisorEmail"></td>
                    </tr>
                  </table>
                </div>
              </div>
              <button type="button" class="btn btn-primary mt-3" onclick="proceedToAddTerms()">
                Continue <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </div>
          
          <!-- Step 2: Terms & Equipment -->
          <div id="addStep2" style="display:none;">
            <h6 class="mb-3">Step 2: Assignment Terms</h6>
            
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="addCheck1">
                <label class="form-check-label" for="addCheck1">
                  Supervisor acknowledges accountability for store equipment
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="addCheck2">
                <label class="form-check-label" for="addCheck2">
                  Supervisor will conduct inventory within 24 hours
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="addCheck3">
                <label class="form-check-label" for="addCheck3">
                  Supervisor agrees to safeguard all equipment
                </label>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Equipment at This Store</label>
              <div id="addEquipmentList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                <div class="text-center text-muted">Loading...</div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes (Optional)</label>
              <textarea class="form-control" id="addAssignmentNotes" rows="3"></textarea>
            </div>
            
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-secondary" onclick="backToAddStep1()">
                <i class="fas fa-arrow-left"></i> Back
              </button>
              <button type="button" class="btn btn-primary" id="addSendRequestBtn" disabled onclick="sendAddRequest()">
                Send Assignment Request
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- Regular Dashboard: Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Total Items</h6>
              <h3 class="mb-0"><?php echo number_format($totalItems); ?></h3>
            </div>
            <div class="ms-3">
              <div class="bg-primary bg-opacity-10 rounded p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                  <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Borrowed</h6>
              <h3 class="mb-0"><?php echo number_format($borrowedItems); ?></h3>
            </div>
            <div class="ms-3">
              <div class="bg-warning bg-opacity-10 rounded p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-warning" viewBox="0 0 16 16">
                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Users</h6>
              <h3 class="mb-0"><?php echo number_format($totalUsers); ?></h3>
            </div>
            <div class="ms-3">
              <div class="bg-success bg-opacity-10 rounded p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-success" viewBox="0 0 16 16">
                  <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Categories</h6>
              <h3 class="mb-0"><?php echo number_format($totalCategories); ?></h3>
            </div>
            <div class="ms-3">
              <div class="bg-info bg-opacity-10 rounded p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-info" viewBox="0 0 16 16">
                  <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">Borrowed Items by Region</h5>
              <small class="text-muted">Distribution of borrowed equipment across Philippine regions</small>
            </div>
            <div>
              <select class="form-select form-select-sm" style="min-width: 200px;" onchange="window.location.href='dashboard.php?region='+this.value">
                <option value="" <?php echo $selectedRegion === '' ? 'selected' : ''; ?>>All Regions</option>
                <option value="NCR" <?php echo $selectedRegion === 'NCR' ? 'selected' : ''; ?>>NCR</option>
                <option value="CAR" <?php echo $selectedRegion === 'CAR' ? 'selected' : ''; ?>>CAR</option>
                <option value="Region I" <?php echo $selectedRegion === 'Region I' ? 'selected' : ''; ?>>Region I</option>
                <option value="Region II" <?php echo $selectedRegion === 'Region II' ? 'selected' : ''; ?>>Region II</option>
                <option value="Region III" <?php echo $selectedRegion === 'Region III' ? 'selected' : ''; ?>>Region III</option>
                <option value="Region IV-A" <?php echo $selectedRegion === 'Region IV-A' ? 'selected' : ''; ?>>Region IV-A</option>
                <option value="Region IV-B" <?php echo $selectedRegion === 'Region IV-B' ? 'selected' : ''; ?>>Region IV-B</option>
                <option value="Region V" <?php echo $selectedRegion === 'Region V' ? 'selected' : ''; ?>>Region V</option>
                <option value="Region VI" <?php echo $selectedRegion === 'Region VI' ? 'selected' : ''; ?>>Region VI</option>
                <option value="Region VII" <?php echo $selectedRegion === 'Region VII' ? 'selected' : ''; ?>>Region VII</option>
                <option value="Region VIII" <?php echo $selectedRegion === 'Region VIII' ? 'selected' : ''; ?>>Region VIII</option>
                <option value="Region IX" <?php echo $selectedRegion === 'Region IX' ? 'selected' : ''; ?>>Region IX</option>
                <option value="Region X" <?php echo $selectedRegion === 'Region X' ? 'selected' : ''; ?>>Region X</option>
                <option value="Region XI" <?php echo $selectedRegion === 'Region XI' ? 'selected' : ''; ?>>Region XI</option>
                <option value="Region XII" <?php echo $selectedRegion === 'Region XII' ? 'selected' : ''; ?>>Region XII</option>
                <option value="Region XIII" <?php echo $selectedRegion === 'Region XIII' ? 'selected' : ''; ?>>Region XIII</option>
                <option value="BARMM" <?php echo $selectedRegion === 'BARMM' ? 'selected' : ''; ?>>BARMM</option>
              </select>
            </div>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($regionData)): ?>
            <div class="text-center py-5 text-muted">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="mb-3 opacity-50" viewBox="0 0 16 16">
                <path d="M4 11a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0v-1zm6-4a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V7zM7 9a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0V9z"/>
                <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
              </svg>
              <p>No borrowed items data yet</p>
              <small>Assign items to users with regions to see distribution</small>
            </div>
          <?php else: ?>
            <div class="d-flex justify-content-center align-items-center" style="min-height: 300px;">
              <canvas id="regionChart" style="max-width: 400px; max-height: 400px;"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0">Quick Stats</h5>
        </div>
        <div class="card-body">
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Available Items</span>
              <strong class="h5 mb-0"><?php echo number_format($totalItems - $borrowedItems); ?></strong>
            </div>
          </div>
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Utilization Rate</span>
              <strong class="h5 mb-0"><?php echo $totalItems > 0 ? number_format(($borrowedItems / $totalItems) * 100, 1) : 0; ?>%</strong>
            </div>
          </div>
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Active Regions</span>
              <strong class="h5 mb-0"><?php echo count($regionData); ?></strong>
            </div>
          </div>
          <div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">Equipment Types</span>
              <strong class="h5 mb-0"><?php echo number_format($totalCategories); ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

<?php if ($isAreaManager): ?>
<!-- Area Manager Store Cards JavaScript -->
<style>
.store-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.store-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.store-card:active {
  transform: translateY(-2px);
}
</style>

<script>
// Modal Functions
let addStoreModal, reassignStoreModal, storeDetailsModal, editOperationHoursModal, editContactInfoModal, assignSupervisorModal, removeSupervisorModal;

document.addEventListener('DOMContentLoaded', async function() {
  // Initialize modals
  addStoreModal = new bootstrap.Modal(document.getElementById('addStoreModal'));
  reassignStoreModal = new bootstrap.Modal(document.getElementById('reassignStoreModal'));
  storeDetailsModal = new bootstrap.Modal(document.getElementById('storeDetailsModal'));
  editOperationHoursModal = new bootstrap.Modal(document.getElementById('editOperationHoursModal'));
  editContactInfoModal = new bootstrap.Modal(document.getElementById('editContactInfoModal'));
  assignSupervisorModal = new bootstrap.Modal(document.getElementById('assignSupervisorModal'));
  removeSupervisorModal = new bootstrap.Modal(document.getElementById('removeSupervisorModal'));
  
  // Setup checkbox listeners for add store modal
  ['addCheck1', 'addCheck2', 'addCheck3'].forEach(id => {
    const elem = document.getElementById(id);
    if (elem) elem.addEventListener('change', updateAddButtons);
  });
  
  // Setup checkbox listeners for reassign modal
  ['reassignRemoveCheck1', 'reassignRemoveCheck2', 'reassignRemoveCheck3', 'reassignRemoveCheck4'].forEach(id => {
    const elem = document.getElementById(id);
    if (elem) elem.addEventListener('change', updateReassignRemoveButton);
  });
  
  const reassignReason = document.getElementById('reassignRemovalReason');
  const reassignNotes = document.getElementById('reassignRemovalNotes');
  if (reassignReason) reassignReason.addEventListener('change', updateReassignRemoveButton);
  if (reassignNotes) reassignNotes.addEventListener('input', updateReassignRemoveButton);
  
  ['reassignNewCheck1', 'reassignNewCheck2', 'reassignNewCheck3'].forEach(id => {
    const elem = document.getElementById(id);
    if (elem) elem.addEventListener('change', updateReassignSendButton);
  });
  
  // Load store cards
  await loadStoreCards();
});

async function loadStoreCards() {
  try {
    const response = await fetch('get_area_manager_stores.php');
    const data = await response.json();
    
    if (data.success && data.stores) {
      updateStats(data.stores);
      renderStoreCards(data.stores);
    } else {
      showError(data.error || 'Failed to load stores');
    }
  } catch (error) {
    console.error('Error:', error);
    showError('Error loading stores');
  }
}

function updateStats(stores) {
  // Total stores
  document.getElementById('totalStores').textContent = stores.length;
  
  // Area name (from first store)
  const areaName = stores.length > 0 ? stores[0].area_name : 'N/A';
  document.getElementById('areaName').textContent = areaName;
  
  // Total supervisors (count unique supervisors)
  const allSupervisors = new Set();
  stores.forEach(store => {
    if (store.supervisors) {
      store.supervisors.forEach(sup => allSupervisors.add(sup.id));
    }
  });
  document.getElementById('totalSupervisors').textContent = allSupervisors.size;
}

function renderStoreCards(stores) {
  const container = document.getElementById('storeCardsContainer');
  
  if (stores.length === 0) {
    container.innerHTML = `
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No stores assigned to your area yet.
      </div>
    `;
    return;
  }
  
  let html = '<div class="row g-4">';
  
  stores.forEach(store => {
    const supervisorCount = store.supervisors ? store.supervisors.length : 0;
    
    html += `
      <div class="col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm border-0 store-card" style="cursor: pointer;" onclick="openStoreDetails(${store.store_id})">
          <div class="card-body text-center py-5">
            <div class="mb-3">
              <i class="fas fa-store fa-3x text-primary"></i>
            </div>
            <h5 class="card-title mb-2">${escapeHtml(store.store_name)}</h5>
            <p class="text-muted mb-3"><code>${escapeHtml(store.store_code)}</code></p>
            
            <div class="d-flex justify-content-center gap-3 mb-2">
              <div>
                <small class="text-muted d-block">Supervisors</small>
                <strong class="h6">${supervisorCount}</strong>
              </div>
            </div>
            
            <div class="mt-3">
              <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(store.address.substring(0, 50))}${store.address.length > 50 ? '...' : ''}</small>
            </div>
          </div>
          <div class="card-footer bg-light text-center">
            <small class="text-primary"><i class="fas fa-hand-pointer me-1"></i>Click to view details</small>
          </div>
        </div>
      </div>
    `;
  });
  
  html += '</div>';
  container.innerHTML = html;
  
  // Store data for later use
  window.storesData = stores;
}

function renderAssignedPersonnel(store) {
  const container = document.getElementById('assignedPersonnelList');
  const assignButton = document.getElementById('assignButtonTop');
  let html = '';
  
  if (store.supervisors && store.supervisors.length > 0) {
    // Hide assign button if there are supervisors
    assignButton.style.display = 'none';
    
    store.supervisors.forEach(sup => {
      html += `
        <div class="border rounded p-2 d-flex align-items-center" style="min-width: 200px;">
          <div class="me-2">
            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
              <i class="fas fa-user"></i>
            </div>
          </div>
          <div class="flex-grow-1">
            <div class="small fw-bold">${escapeHtml(sup.full_name)}</div>
            <div class="text-muted" style="font-size: 0.75rem;">${escapeHtml(sup.employee_number)}</div>
          </div>
          <button class="btn btn-sm btn-link text-danger p-0 ms-2" 
                  onclick="openRemoveSupervisorModal(${sup.id}, '${escapeHtml(sup.full_name)}', ${store.store_id}, '${escapeHtml(store.store_name)}')" 
                  title="Remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `;
    });
  } else {
    // Show assign button if no supervisors
    assignButton.style.display = 'inline-block';
    html = '<p class="text-muted small mb-0">No supervisors assigned yet</p>';
  }
  
  container.innerHTML = html;
  
  // Store current store data globally for modal access
  window.currentStore = store;
}

function renderSupervisorManagement(store) {
  let html = '';
  
  if (store.supervisors && store.supervisors.length > 0) {
    html += '<div class="mb-3"><p class="small fw-bold mb-2">Current Supervisors:</p>';
    store.supervisors.forEach(sup => {
      html += `
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
          <div class="small">
            <strong>${escapeHtml(sup.full_name)}</strong><br>
            <span class="text-muted">${escapeHtml(sup.employee_number)}</span>
          </div>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-sm btn-outline-warning" onclick="reassignStore(${sup.id}, ${store.store_id}, '${escapeHtml(sup.full_name)}', '${escapeHtml(store.store_name)}')" title="Reassign">
              <i class="fas fa-exchange-alt"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="removeFromStore(${sup.id}, ${store.store_id}, '${escapeHtml(sup.full_name)}', '${escapeHtml(store.store_name)}')" title="Remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      `;
    });
    html += '</div>';
  }
  
  html += `
    <button class="btn btn-sm btn-success w-100" onclick="openAddSupervisorModal(${store.store_id}, '${escapeHtml(store.store_name)}')">
      <i class="fas fa-plus me-2"></i>Assign New Supervisor
    </button>
  `;
  
  return html;
}

function openStoreDetails(storeId) {
  console.log('Opening store details for ID:', storeId);
  console.log('Available stores:', window.storesData);
  
  const store = window.storesData.find(s => s.store_id == storeId);
  if (!store) {
    console.error('Store not found:', storeId);
    console.error('Available store IDs:', window.storesData.map(s => s.store_id));
    return;
  }
  
  console.log('Store data:', store);
  
  try {
    // Populate store information
    document.getElementById('currentStoreId').value = storeId;
    document.getElementById('storeDetailsTitle').textContent = store.store_name;
    document.getElementById('detailStoreName').textContent = store.store_name;
    document.getElementById('detailAreaName').textContent = store.area_name || 'N/A';
    
    // Populate header information
    document.getElementById('headerStoreCode').textContent = store.store_code;
    document.getElementById('headerAddress').textContent = store.address;
    
    // Format and display operation hours
    const operationHoursElement = document.getElementById('detailOperationHours');
    if (store.operation_hours) {
      try {
        // Check if it's the new simple format (opening_time-closing_time)
        if (store.operation_hours.includes('-') && !store.operation_hours.includes('{')) {
          const [opening, closing] = store.operation_hours.split('-');
          const formatTime12h = (time24) => {
            if (!time24) return '';
            const [hours, minutes] = time24.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
          };
          operationHoursElement.textContent = `${formatTime12h(opening)} - ${formatTime12h(closing)}`;
        } else {
          // Old format or just display as-is
          operationHoursElement.textContent = store.operation_hours;
        }
      } catch (e) {
        console.error('Error parsing operation hours:', e);
        operationHoursElement.textContent = store.operation_hours;
      }
    } else {
      operationHoursElement.textContent = 'Not set';
    }
    
    // Populate contact information
    document.getElementById('detailContactPerson').textContent = store.contact_person || 'Not set';
    document.getElementById('detailContactEmployeeNumber').textContent = store.contact_employee_number || 'Not set';
    document.getElementById('detailContactNumber').textContent = store.contact_number || 'Not set';
    
    // Populate assigned personnel
    renderAssignedPersonnel(store);
    
    // Load equipment summary
    loadEquipmentSummary(storeId);
    
    // Load full equipment list
    loadStoreEquipmentDetails(storeId);
    
    console.log('About to show modal, storeDetailsModal:', storeDetailsModal);
    
    // Show modal
    if (storeDetailsModal) {
      storeDetailsModal.show();
      console.log('Modal show() called');
    } else {
      console.error('storeDetailsModal is not initialized');
    }
  } catch (error) {
    console.error('Error opening store details:', error);
  }
}

async function loadEquipmentSummary(storeId) {
  const container = document.getElementById('detailEquipmentSummary');
  
  try {
    const response = await fetch(`get_store_equipment.php?store_id=${storeId}`);
    const data = await response.json();
    
    if (data.success) {
      const count = data.equipment.length;
      if (count === 0) {
        container.innerHTML = '<p class="text-muted mb-0">No equipment assigned</p>';
      } else {
        container.innerHTML = `
          <div class="d-flex align-items-center">
            <i class="fas fa-box fa-2x text-primary me-3"></i>
            <div>
              <h4 class="mb-0">${count}</h4>
              <small class="text-muted">Equipment Items</small>
            </div>
          </div>
        `;
      }
    }
  } catch (error) {
    console.error('Error loading equipment summary:', error);
    container.innerHTML = '<p class="text-muted">Error loading</p>';
  }
}

async function loadStoreEquipmentDetails(storeId) {
  const container = document.getElementById('detailEquipmentList');
  
  try {
    const response = await fetch('get_store_equipment.php?store_id=' + storeId);
    const data = await response.json();
    
    if (data.success) {
      if (data.equipment.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No equipment assigned to this store yet.</div>';
      } else {
        let html = `
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Item Name</th>
                  <th>Category</th>
                  <th>Serial Number</th>
                  <th>Model</th>
                  <th>Brand</th>
                  <th>Quantity</th>
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
              <td>${escapeHtml(item.model || 'N/A')}</td>
              <td>${escapeHtml(item.brand || 'N/A')}</td>
              <td><span class="badge bg-info">${item.quantity}</span></td>
              <td><span class="badge bg-success">${escapeHtml(item.status)}</span></td>
            </tr>
          `;
        });
        
        html += `
              </tbody>
            </table>
          </div>
          <div class="alert alert-light mb-0">
            <strong>Total Equipment:</strong> ${data.equipment.length} items
          </div>
        `;
        
        container.innerHTML = html;
      }
    }
  } catch (error) {
    console.error('Error:', error);
    container.innerHTML = '<div class="alert alert-danger">Error loading equipment</div>';
  }
}

function openAddSupervisorModalFromDetails() {
  const storeId = document.getElementById('currentStoreId').value;
  const store = window.storesData.find(s => s.store_id == storeId);
  if (!store) return;
  
  storeDetailsModal.hide();
  openAddSupervisorModal(storeId, store.store_name);
}

function reassignStoreFromDetails(supervisorId, storeId, supervisorName, storeName) {
  storeDetailsModal.hide();
  reassignStore(supervisorId, storeId, supervisorName, storeName);
}

async function removeFromStoreDetails(supervisorId, storeId, supervisorName, storeName) {
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
      storeDetailsModal.hide();
      
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${supervisorName} removed from ${storeName}. Supervisor has been notified.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      await loadStoreCards();
      setTimeout(() => alertDiv.remove(), 5000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error removing supervisor from store');
  }
}

function editOperationHours() {
  const storeId = document.getElementById('currentStoreId').value;
  const store = window.storesData.find(s => s.store_id == storeId);
  if (!store) return;
  
  document.getElementById('editOperationStoreId').value = storeId;
  document.getElementById('editOperationStoreName').textContent = store.store_name;
  
  // Parse existing operation hours if available
  if (store.operation_hours) {
    try {
      // Check if it's the simple format (opening_time-closing_time)
      if (store.operation_hours.includes('-') && !store.operation_hours.includes('{')) {
        const [opening, closing] = store.operation_hours.split('-');
        document.getElementById('editOpeningTime').value = opening || '07:00';
        document.getElementById('editClosingTime').value = closing || '22:00';
      } else {
        // Default values
        document.getElementById('editOpeningTime').value = '07:00';
        document.getElementById('editClosingTime').value = '22:00';
      }
    } catch (e) {
      console.log('Could not parse operation hours, using defaults');
      document.getElementById('editOpeningTime').value = '07:00';
      document.getElementById('editClosingTime').value = '22:00';
    }
  }
  
  storeDetailsModal.hide();
  editOperationHoursModal.show();
}

function editContactInfo() {
  const storeId = document.getElementById('currentStoreId').value;
  const store = window.storesData.find(s => s.store_id == storeId);
  if (!store) return;
  
  document.getElementById('editContactStoreId').value = storeId;
  document.getElementById('editContactStoreName').textContent = store.store_name;
  document.getElementById('editContactPersonInput').value = store.contact_person || '';
  document.getElementById('editContactEmployeeNumberInput').value = store.contact_employee_number || '';
  document.getElementById('editContactNumberInput').value = store.contact_number || '';
  
  storeDetailsModal.hide();
  editContactInfoModal.show();
}

async function saveContactInfo() {
  const storeId = document.getElementById('editContactStoreId').value;
  const contactPerson = document.getElementById('editContactPersonInput').value.trim();
  const contactEmployeeNumber = document.getElementById('editContactEmployeeNumberInput').value.trim();
  const contactNumber = document.getElementById('editContactNumberInput').value.trim();
  
  try {
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('contact_person', contactPerson);
    formData.append('contact_employee_number', contactEmployeeNumber);
    formData.append('contact_number', contactNumber);
    
    const response = await fetch('update_contact_info.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      editContactInfoModal.hide();
      
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Contact information updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      // Reload stores and reopen detail modal
      await loadStoreCards();
      setTimeout(() => {
        alertDiv.remove();
        const store = window.storesData.find(s => s.store_id == storeId);
        if (store) {
          openStoreDetails(storeId);
        }
      }, 1000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating contact information');
  }
}

async function saveOperationHours() {
  const storeId = document.getElementById('editOperationStoreId').value;
  const openingTime = document.getElementById('editOpeningTime').value;
  const closingTime = document.getElementById('editClosingTime').value;
  
  // Validate times are set
  if (!openingTime || !closingTime) {
    alert('Please set both opening and closing times.');
    return;
  }
  
  // Store as simple format: opening_time-closing_time
  const operationHours = `${openingTime}-${closingTime}`;
  
  try {
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('operation_hours', operationHours);
    
    const response = await fetch('update_operation_hours.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      editOperationHoursModal.hide();
      
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Operation hours updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      // Reload stores and reopen detail modal
      await loadStoreCards();
      setTimeout(() => {
        alertDiv.remove();
        const store = window.storesData.find(s => s.store_id == storeId);
        if (store) {
          openStoreDetails(storeId);
        }
      }, 1000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating operation hours');
  }
}

// ============= ASSIGN SUPERVISOR (IN) FUNCTIONS =============

function openAssignSupervisorModal() {
  if (!window.currentStore) {
    alert('No store selected');
    return;
  }
  
  // Reset modal - Start at step 1 (Terms and Conditions)
  document.getElementById('assignStep1').style.display = 'block';
  document.getElementById('assignStep2').style.display = 'none';
  document.getElementById('assignStep3').style.display = 'none';
  document.getElementById('assignNextBtn').style.display = 'inline-block';
  document.getElementById('assignSubmitBtn').style.display = 'none';
  document.getElementById('assignTermsAccept').checked = false;
  document.getElementById('assignEmployeeNumber').value = '';
  document.getElementById('assignEmployeeDetails').style.display = 'none';
  document.getElementById('assignSupervisorRole').value = '';
  
  // Set store name
  document.getElementById('assignStoreName').textContent = window.currentStore.store_name;
  document.getElementById('assignStoreName2').textContent = window.currentStore.store_name;
  
  assignSupervisorModal.show();
}

async function assignNextStep() {
  // Check which step we're on
  if (document.getElementById('assignStep1').style.display !== 'none') {
    // Step 1: Terms and Conditions
    if (!document.getElementById('assignTermsAccept').checked) {
      alert('Please read and accept the Terms and Conditions');
      return;
    }
    // Move to step 2 (Employee Search)
    document.getElementById('assignStep1').style.display = 'none';
    document.getElementById('assignStep2').style.display = 'block';
    document.getElementById('assignNextBtn').style.display = 'none';
  } else if (document.getElementById('assignStep2').style.display !== 'none') {
    // Step 2: Employee Search
    const supervisorRole = document.getElementById('assignSupervisorRole').value;
    
    if (!supervisorRole) {
      alert('Please select a role (Store Supervisor or OIC)');
      return;
    }
    // Move to step 3 (Equipment Condition)
    document.getElementById('assignStep2').style.display = 'none';
    document.getElementById('assignStep3').style.display = 'block';
    document.getElementById('assignNextBtn').style.display = 'none';
    document.getElementById('assignSubmitBtn').style.display = 'inline-block';
    
    // Load equipment for condition check
    await loadEquipmentForAssignment();
  }
}

async function searchEmployee() {
  const employeeNumber = document.getElementById('assignEmployeeNumber').value.trim();
  
  if (!employeeNumber) {
    alert('Please enter an employee number');
    return;
  }
  
  try {
    const response = await fetch(`lookup_employee.php?employee_number=${encodeURIComponent(employeeNumber)}`);
    const data = await response.json();
    
    if (data.success) {
      const employee = data.employee;
      
      // Check if department is OPERATION
      if (employee.department && employee.department.toLowerCase() !== 'operation') {
        alert('Only employees from OPERATION department can be assigned as supervisors');
        return;
      }
      
      // Display employee details
      document.getElementById('assignEmployeeName').textContent = employee.full_name;
      document.getElementById('assignEmployeeNum').textContent = employee.employee_number;
      document.getElementById('assignEmployeeRole').textContent = employee.department || employee.role;
      document.getElementById('assignEmployeeId').value = employee.id;
      document.getElementById('assignEmployeeDetails').style.display = 'block';
      
      // Show next button
      document.getElementById('assignNextBtn').style.display = 'inline-block';
    } else {
      alert(data.error || 'Employee not found');
      document.getElementById('assignEmployeeDetails').style.display = 'none';
      document.getElementById('assignNextBtn').style.display = 'none';
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error searching for employee');
  }
}

async function loadEquipmentForAssignment() {
  const container = document.getElementById('assignEquipmentList');
  const storeId = window.currentStore.store_id;
  
  try {
    const response = await fetch(`get_store_equipment.php?store_id=${storeId}`);
    const data = await response.json();
    
    if (data.success) {
      if (data.equipment.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No equipment assigned to this store.</div>';
      } else {
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead class="table-light"><tr><th>Item</th><th>Serial #</th><th>Qty</th><th>Condition (IN)</th></tr></thead><tbody>';
        
        data.equipment.forEach((item, index) => {
          html += `
            <tr>
              <td><strong>${escapeHtml(item.item_name)}</strong></td>
              <td><code>${escapeHtml(item.serial_number)}</code></td>
              <td>${item.quantity}</td>
              <td>
                <select class="form-select form-select-sm equipment-condition-in" data-equipment-id="${item.equipment_id}">
                  <option value="">-- Select --</option>
                  <option value="working">Working</option>
                  <option value="damaged">Damaged</option>
                </select>
              </td>
            </tr>
          `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
      }
    }
  } catch (error) {
    console.error('Error:', error);
    container.innerHTML = '<div class="alert alert-danger">Error loading equipment</div>';
  }
}

async function submitAssignment() {
  const employeeId = document.getElementById('assignEmployeeId').value;
  const supervisorRole = document.getElementById('assignSupervisorRole').value;
  const storeId = window.currentStore.store_id;
  
  // Collect equipment conditions
  const equipmentConditions = [];
  document.querySelectorAll('.equipment-condition-in').forEach(select => {
    if (select.value) {
      equipmentConditions.push({
        equipment_id: select.dataset.equipmentId,
        condition: select.value
      });
    }
  });
  
  // Validate all equipment conditions are filled
  const allSelects = document.querySelectorAll('.equipment-condition-in');
  if (allSelects.length > 0 && equipmentConditions.length !== allSelects.length) {
    alert('Please select condition for all equipment');
    return;
  }
  
  try {
    const response = await fetch('assign_supervisor.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        employee_id: employeeId,
        store_id: storeId,
        supervisor_role: supervisorRole,
        equipment_conditions: equipmentConditions
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      assignSupervisorModal.hide();
      
      // Show success message
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Supervisor assigned successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      // Reload and reopen modal
      await loadStoreCards();
      setTimeout(() => {
        alertDiv.remove();
        openStoreDetails(storeId);
      }, 1500);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error assigning supervisor');
  }
}

// ============= REMOVE SUPERVISOR (OUT) FUNCTIONS =============

function openRemoveSupervisorModal(supervisorId, supervisorName, storeId, storeName) {
  // Reset all steps
  document.getElementById('removeStep1').style.display = 'block';
  document.getElementById('removeStep2').style.display = 'none';
  document.getElementById('removeStep3').style.display = 'none';
  document.getElementById('removeNextBtn').style.display = 'inline-block';
  document.getElementById('removeSubmitBtn').style.display = 'none';
  document.getElementById('removeReason').value = '';
  document.getElementById('removeTermsAccept').checked = false;
  
  // Set data
  document.getElementById('removeSupervisorName').textContent = supervisorName;
  document.getElementById('removeStoreName').textContent = storeName;
  document.getElementById('removeSupervisorName2').textContent = supervisorName;
  document.getElementById('removeStoreName2').textContent = storeName;
  document.getElementById('removeSupervisorId').value = supervisorId;
  document.getElementById('removeStoreId').value = storeId;
  
  removeSupervisorModal.show();
}

async function removeNextStep() {
  const removeStep1 = document.getElementById('removeStep1');
  const removeStep2 = document.getElementById('removeStep2');
  const removeStep3 = document.getElementById('removeStep3');
  const removeTermsAccept = document.getElementById('removeTermsAccept');
  const removeReason = document.getElementById('removeReason');
  
  if (removeStep1.style.display !== 'none') {
    // Step 1 → Step 2: Validate Terms acceptance
    if (!removeTermsAccept.checked) {
      alert('Please read and accept the Terms and Conditions.');
      return;
    }
    
    removeStep1.style.display = 'none';
    removeStep2.style.display = 'block';
  } else if (removeStep2.style.display !== 'none') {
    // Step 2 → Step 3: Validate removal reason
    if (!removeReason.value) {
      alert('Please select a removal reason.');
      return;
    }
    
    removeStep2.style.display = 'none';
    removeStep3.style.display = 'block';
    document.getElementById('removeNextBtn').style.display = 'none';
    document.getElementById('removeSubmitBtn').style.display = 'inline-block';
    
    // Load equipment for condition check
    await loadEquipmentForRemoval();
  }
}

async function loadEquipmentForRemoval() {
  const container = document.getElementById('removeEquipmentList');
  const storeId = document.getElementById('removeStoreId').value;
  
  try {
    const response = await fetch(`get_store_equipment.php?store_id=${storeId}`);
    const data = await response.json();
    
    if (data.success) {
      if (data.equipment.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No equipment assigned to this store.</div>';
      } else {
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead class="table-light"><tr><th>Item</th><th>Serial #</th><th>Qty</th><th>Condition (OUT)</th></tr></thead><tbody>';
        
        data.equipment.forEach((item, index) => {
          html += `
            <tr>
              <td><strong>${escapeHtml(item.item_name)}</strong></td>
              <td><code>${escapeHtml(item.serial_number)}</code></td>
              <td>${item.quantity}</td>
              <td>
                <select class="form-select form-select-sm equipment-condition-out" data-equipment-id="${item.equipment_id}">
                  <option value="">-- Select --</option>
                  <option value="working">Working</option>
                  <option value="damaged">Damaged</option>
                  <option value="stolen_missing">Stolen/Missing</option>
                </select>
              </td>
            </tr>
          `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
      }
    }
  } catch (error) {
    console.error('Error:', error);
    container.innerHTML = '<div class="alert alert-danger">Error loading equipment</div>';
  }
}

async function submitRemoval() {
  const supervisorId = document.getElementById('removeSupervisorId').value;
  const storeId = document.getElementById('removeStoreId').value;
  const reason = document.getElementById('removeReason').value;
  
  // Collect equipment conditions
  const equipmentConditions = [];
  document.querySelectorAll('.equipment-condition-out').forEach(select => {
    if (select.value) {
      equipmentConditions.push({
        equipment_id: select.dataset.equipmentId,
        condition: select.value
      });
    }
  });
  
  // Validate all equipment conditions are filled
  const allSelects = document.querySelectorAll('.equipment-condition-out');
  if (allSelects.length > 0 && equipmentConditions.length !== allSelects.length) {
    alert('Please select condition for all equipment');
    return;
  }
  
  try {
    const response = await fetch('remove_supervisor.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        supervisor_id: supervisorId,
        store_id: storeId,
        reason: reason,
        equipment_conditions: equipmentConditions
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      removeSupervisorModal.hide();
      
      // Show success message
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>Supervisor removed successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      // Reload and reopen modal
      await loadStoreCards();
      setTimeout(() => {
        alertDiv.remove();
        openStoreDetails(storeId);
      }, 1500);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error removing supervisor');
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function showError(message) {
  document.getElementById('storeCardsContainer').innerHTML = `
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle me-2"></i>${escapeHtml(message)}
    </div>
  `;
}

function updateAddButtons() {
  const allChecked = ['addCheck1', 'addCheck2', 'addCheck3'].every(id => document.getElementById(id).checked);
  document.getElementById('addSendRequestBtn').disabled = !allChecked;
}

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

function openAddSupervisorModal(storeId, storeName) {
  document.getElementById('addStoreStoreId').value = storeId;
  document.getElementById('addStoreStoreName').textContent = storeName;
  
  // Reset form
  document.getElementById('addEmployeeNumber').value = '';
  document.getElementById('addLookupError').style.display = 'none';
  document.getElementById('addSupervisorDetails').style.display = 'none';
  ['addCheck1', 'addCheck2', 'addCheck3'].forEach(id => document.getElementById(id).checked = false);
  document.getElementById('addAssignmentNotes').value = '';
  
  // Show step 1
  document.getElementById('addStep1').style.display = 'block';
  document.getElementById('addStep2').style.display = 'none';
  
  addStoreModal.show();
}

async function lookupEmployeeForAdd() {
  const employeeNumber = document.getElementById('addEmployeeNumber').value.trim();
  const errorDiv = document.getElementById('addLookupError');
  
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
      
      document.getElementById('addStoreSupervisorId').value = data.user.id;
      document.getElementById('addSupervisorName').textContent = data.user.full_name;
      document.getElementById('addSupervisorEmpNum').textContent = data.user.employee_number;
      document.getElementById('addSupervisorEmail').textContent = data.user.email || 'N/A';
      
      document.getElementById('addSupervisorDetails').style.display = 'block';
    } else {
      errorDiv.textContent = data.error || 'Supervisor not found';
      errorDiv.style.display = 'block';
      document.getElementById('addSupervisorDetails').style.display = 'none';
    }
  } catch (error) {
    console.error('Error:', error);
    errorDiv.textContent = 'Error looking up supervisor';
    errorDiv.style.display = 'block';
  }
}

function proceedToAddTerms() {
  document.getElementById('addStep1').style.display = 'none';
  document.getElementById('addStep2').style.display = 'block';
  
  // Load equipment
  const storeId = document.getElementById('addStoreStoreId').value;
  loadEquipmentForAdd(storeId);
}

function backToAddStep1() {
  document.getElementById('addStep2').style.display = 'none';
  document.getElementById('addStep1').style.display = 'block';
}

async function loadEquipmentForAdd(storeId) {
  const container = document.getElementById('addEquipmentList');
  
  try {
    const response = await fetch('get_store_equipment.php?store_id=' + storeId);
    const data = await response.json();
    
    if (data.success) {
      if (data.equipment.length === 0) {
        container.innerHTML = '<div class=\"alert alert-info small\">No equipment assigned to this store.</div>';
      } else {
        let html = `
          <div class=\"table-responsive\">
            <table class=\"table table-sm mb-0\">
              <thead class=\"table-light\">
                <tr>
                  <th>Item</th>
                  <th>Serial #</th>
                  <th>Qty</th>
                </tr>
              </thead>
              <tbody>
        `;
        
        data.equipment.forEach(item => {
          html += `
            <tr>
              <td class="small"><strong>${escapeHtml(item.item_name)}</strong></td>
              <td class="small"><code>${escapeHtml(item.serial_number)}</code></td>
              <td class="small">${item.quantity}</td>
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
    container.innerHTML = '<div class=\"alert alert-danger small\">Error loading equipment</div>';
  }
}

async function sendAddRequest() {
  const supervisorId = document.getElementById('addStoreSupervisorId').value;
  const storeId = document.getElementById('addStoreStoreId').value;
  const notes = document.getElementById('addAssignmentNotes').value;
  
  try {
    const formData = new FormData();
    formData.append('supervisor_user_id', supervisorId);
    formData.append('store_id', storeId);
    formData.append('reason', 'New Assignment');
    formData.append('notes', notes);
    
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
        <i class=\"fas fa-check-circle me-2\"></i>Assignment request sent! Supervisor will be notified.
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      await loadStoreCards();
      setTimeout(() => alertDiv.remove(), 5000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error sending assignment request');
  }
}

// Reassignment functions (from manage_team.php)
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
      await loadStoreCards();
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
        container.innerHTML = '<div class=\"alert alert-info\">No equipment assigned to this store.</div>';
      } else {
        let html = `
          <div class=\"table-responsive\">
            <table class=\"table table-sm\">
              <thead class=\"table-light\">
                <tr>
                  <th>Item</th>
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
    container.innerHTML = '<div class=\"alert alert-danger\">Error loading equipment</div>';
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
        <i class=\"fas fa-check-circle me-2\"></i>Reassignment request sent! The new supervisor will be notified.
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
      `;
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      await loadStoreCards();
      setTimeout(() => alertDiv.remove(), 5000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error sending reassignment request');
  }
}

async function removeFromStore(supervisorId, storeId, supervisorName, storeName) {
  if (!confirm(`Remove ${supervisorName} from ${storeName}?\\n\\nThey will be notified of this removal.`)) {
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
      document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
      
      await loadStoreCards();
      setTimeout(() => alertDiv.remove(), 5000);
    } else {
      alert('Error: ' + data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error removing supervisor from store');
  }
}
</script>

<?php elseif (!empty($regionData)): ?>
<!-- Regular Dashboard Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  const ctx = document.getElementById('regionChart');
  // Color palette similar to the reference image
  const colors = [
    '#1B3A5F', // Dark blue
    '#2E75B6', // Medium blue
    '#5DADE2', // Light blue
    '#85C1E9', // Lighter blue
    '#A9C27F', // Brand green
    '#F39C12', // Orange
    '#E74C3C', // Red
    '#9B59B6', // Purple
    '#1ABC9C', // Teal
    '#34495E'  // Dark grey
  ];
  
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($regionLabels); ?>,
      datasets: [{
        data: <?php echo json_encode($regionCounts); ?>,
        backgroundColor: colors,
        borderColor: '#fff',
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'right',
          labels: {
            padding: 15,
            font: {
              size: 12,
              family: "'Jost', sans-serif"
            },
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 12,
          borderRadius: 6,
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return label + ': ' + value + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
</script>
<?php endif; ?>
