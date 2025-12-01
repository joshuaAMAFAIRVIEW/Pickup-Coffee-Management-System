<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

// Get search parameter
$search = trim($_GET['search'] ?? '');

// Build query with search
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT id, username, employee_number, role, created_at, first_name, last_name, department 
                           FROM users 
                           WHERE username LIKE :search 
                              OR employee_number LIKE :search 
                              OR first_name LIKE :search 
                              OR last_name LIKE :search
                           ORDER BY created_at DESC');
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT id, username, employee_number, role, created_at, first_name, last_name, department FROM users ORDER BY created_at DESC');
}
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

  <?php if (isset($_SESSION['success_message']) || isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message'] ?? $_SESSION['flash_success']; unset($_SESSION['success_message'], $_SESSION['flash_success']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message']) || isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message'] ?? $_SESSION['flash_error']; unset($_SESSION['error_message'], $_SESSION['flash_error']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Users</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
  </div>

  <!-- Search Bar -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-10">
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by username, employee number, or name..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search"></i> Search
          </button>
          <?php if ($search): ?>
            <a href="users.php" class="btn btn-secondary w-100 mt-2">
              <i class="fas fa-times"></i> Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Employee #</th>
              <th>Username</th>
              <th>Name</th>
              <th>Department</th>
              <th>Role</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="8" class="text-muted">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
              <?php 
                $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                $fullName = $fullName ?: '-';
              ?>
              <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><code><?php echo htmlspecialchars($u['employee_number'] ?? 'N/A'); ?></code></td>
                <td>
                  <a href="#" class="text-decoration-none fw-bold" 
                     data-bs-toggle="modal" 
                     data-bs-target="#userDetailModal"
                     data-user-id="<?php echo (int)$u['id']; ?>"
                     data-user-username="<?php echo htmlspecialchars($u['username']); ?>"
                     data-user-empnum="<?php echo htmlspecialchars($u['employee_number'] ?? ''); ?>"
                     data-user-firstname="<?php echo htmlspecialchars($u['first_name'] ?? ''); ?>"
                     data-user-lastname="<?php echo htmlspecialchars($u['last_name'] ?? ''); ?>"
                     data-user-department="<?php echo htmlspecialchars($u['department'] ?? ''); ?>"
                     data-user-role="<?php echo htmlspecialchars($u['role']); ?>">
                    <?php echo htmlspecialchars($u['username']); ?>
                  </a>
                </td>
                <td><?php echo htmlspecialchars($fullName); ?></td>
                <td><?php echo htmlspecialchars($u['department'] ?? '-'); ?></td>
                <td><span class="badge bg-primary"><?php echo htmlspecialchars($u['role']); ?></span></td>
                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small></td>
                <td>
                  <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#assignEquipmentModal"
                            data-user-id="<?php echo (int)$u['id']; ?>"
                            data-user-username="<?php echo htmlspecialchars($u['username']); ?>"
                            data-user-empnum="<?php echo htmlspecialchars($u['employee_number'] ?? ''); ?>"
                            data-user-fullname="<?php echo htmlspecialchars($fullName); ?>"
                            data-user-department="<?php echo htmlspecialchars($u['department'] ?? ''); ?>"
                            title="Release Equipment">
                      📦
                    </button>
                    <button class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editUserModal"
                            data-user-id="<?php echo (int)$u['id']; ?>"
                            data-user-username="<?php echo htmlspecialchars($u['username']); ?>"
                            data-user-empnum="<?php echo htmlspecialchars($u['employee_number'] ?? ''); ?>"
                            data-user-firstname="<?php echo htmlspecialchars($u['first_name'] ?? ''); ?>"
                            data-user-lastname="<?php echo htmlspecialchars($u['last_name'] ?? ''); ?>"
                            data-user-department="<?php echo htmlspecialchars($u['department'] ?? ''); ?>"
                            data-user-role="<?php echo htmlspecialchars($u['role']); ?>"
                            title="Edit User">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#changePasswordModal"
                            data-user-id="<?php echo (int)$u['id']; ?>"
                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                            title="Reset Password">
                      <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteUserModal"
                            data-user-id="<?php echo (int)$u['id']; ?>"
                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                            data-employee-number="<?php echo htmlspecialchars($u['employee_number'] ?? 'N/A'); ?>"
                            title="Delete User">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" action="edit_user.php" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="editUserId">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Employee Number</label>
              <input name="employee_number" id="editEmployeeNumber" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input name="username" id="editUsername" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input name="first_name" id="editFirstName" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input name="last_name" id="editLastName" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <select name="department" id="editDepartment" class="form-select">
                <option value="">-- Select Department --</option>
                <option value="HR">HR</option>
                <option value="RSQM">RSQM</option>
                <option value="OPERATION">OPERATION</option>
                <option value="IT">IT</option>
                <option value="FINANCE">FINANCE</option>
                <option value="MARKETING">MARKETING</option>
                <option value="BD">BD</option>
                <option value="ADMIN">ADMIN</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select name="role" id="editRole" class="form-select">
                <option value="staff">Staff</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" action="change_user_password.php" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="changePasswordUserId">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Changing password for: <strong id="changePasswordUsername"></strong>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input name="new_password" type="password" class="form-control" required minlength="6" 
                   placeholder="Enter new password (min 6 characters)">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input name="confirm_password" type="password" class="form-control" required minlength="6" 
                   placeholder="Confirm new password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-key"></i> Change Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" action="create_user.php" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Employee Number</label>
            <input name="employee_number" class="form-control" required placeholder="e.g. EMP-001">
            <small class="text-muted">Unique employee identifier</small>
          </div>
          <div class="mb-2">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" required>
          </div>
          <div class="row g-2">
            <div class="col">
              <label class="form-label">First name</label>
              <input name="first_name" class="form-control">
            </div>
            <div class="col">
              <label class="form-label">Middle name</label>
              <input name="middle_name" class="form-control">
            </div>
            <div class="col">
              <label class="form-label">Last name</label>
              <input name="last_name" class="form-control">
            </div>
          </div>
          <div class="mb-2 mt-2">
            <label class="form-label">Position</label>
            <input name="position" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Department</label>
            <select name="department" id="departmentSelect" class="form-select">
              <option value="HR">HR</option>
              <option value="RSQM">RSQM</option>
              <option value="OPERATION">OPERATION</option>
              <option value="IT">IT</option>
              <option value="FINANCE">FINANCE</option>
              <option value="MARKETING">MARKETING</option>
              <option value="BD">BD</option>
              <option value="ADMIN">ADMIN</option>
            </select>
          </div>
          <div class="mb-2" id="storeRow" style="display:none;">
            <label class="form-label">Store</label>
            <input name="store" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Region</label>
            <select name="region" class="form-select" required>
              <option value="">-- Select Region --</option>
              <option value="NCR">NCR - National Capital Region</option>
              <option value="CAR">CAR - Cordillera Administrative Region</option>
              <option value="Region I">Region I - Ilocos Region</option>
              <option value="Region II">Region II - Cagayan Valley</option>
              <option value="Region III">Region III - Central Luzon</option>
              <option value="Region IV-A">Region IV-A - CALABARZON</option>
              <option value="Region IV-B">Region IV-B - MIMAROPA</option>
              <option value="Region V">Region V - Bicol Region</option>
              <option value="Region VI">Region VI - Western Visayas</option>
              <option value="Region VII">Region VII - Central Visayas</option>
              <option value="Region VIII">Region VIII - Eastern Visayas</option>
              <option value="Region IX">Region IX - Zamboanga Peninsula</option>
              <option value="Region X">Region X - Northern Mindanao</option>
              <option value="Region XI">Region XI - Davao Region</option>
              <option value="Region XII">Region XII - SOCCSKSARGEN</option>
              <option value="Region XIII">Region XIII - Caraga</option>
              <option value="BARMM">BARMM - Bangsamoro</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="staff">staff</option>
              <option value="manager">manager</option>
              <option value="admin">admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- User Detail Modal -->
  <div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #A9C27F 0%, #8BA862 100%); color: white;">
          <h5 class="modal-title"><i class="fas fa-user"></i> <span id="detailUsername"></span> - Equipment Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- User Info Card -->
          <div class="card mb-3">
            <div class="card-body">
              <div class="row">
                <div class="col-md-3">
                  <strong>Employee #:</strong><br>
                  <code id="detailEmpNum"></code>
                </div>
                <div class="col-md-3">
                  <strong>Name:</strong><br>
                  <span id="detailFullName"></span>
                </div>
                <div class="col-md-3">
                  <strong>Department:</strong><br>
                  <span id="detailDepartment"></span>
                </div>
                <div class="col-md-3">
                  <strong>Role:</strong><br>
                  <span id="detailRole" class="badge bg-primary"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Currently Borrowed Equipment -->
          <h6 class="mb-3"><i class="fas fa-box-open"></i> Currently Borrowed Equipment</h6>
          <div id="currentEquipmentContainer">
            <div class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Assignment History -->
          <h6 class="mb-3"><i class="fas fa-history"></i> Assignment History</h6>
          <div id="historyContainer">
            <div class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Equipment Modal -->
  <div class="modal fade" id="assignEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" action="assign_equipment.php" class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #A9C27F 0%, #8BA862 100%); color: white;">
          <h5 class="modal-title">📦 Release Equipment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="assignUserId">
          
          <!-- User Info -->
          <div class="alert alert-info">
            <strong>Assigning equipment to:</strong><br>
            <i class="fas fa-user"></i> <span id="assignUsername"></span> 
            (<code id="assignEmpNum"></code>) - 
            <span id="assignDepartment"></span>
          </div>

          <div class="row g-3">
            <!-- Category Selection -->
            <div class="col-md-6">
              <label class="form-label"><i class="fas fa-th-large"></i> Category *</label>
              <select name="category_id" id="assignCategory" class="form-select" required>
                <option value="">-- Select Category --</option>
                <?php
                $catStmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
                $categories = $catStmt->fetchAll();
                foreach ($categories as $cat) {
                  echo '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                }
                ?>
              </select>
            </div>

            <!-- Equipment Selection -->
            <div class="col-md-6">
              <label class="form-label"><i class="fas fa-laptop"></i> Equipment *</label>
              <input type="text" id="equipmentSearch" class="form-control mb-1" placeholder="Type to search..." style="display:none;">
              <select name="item_id" id="assignEquipment" class="form-select" required size="5" style="display:none;">
                <option value="">-- Select category first --</option>
              </select>
              <div id="equipmentListDisplay"></div>
              <small class="text-muted">Only available equipment shown</small>
            </div>

            <!-- Condition Selection -->
            <div class="col-md-6">
              <label class="form-label"><i class="fas fa-tag"></i> Condition *</label>
              <select name="item_condition" id="assignCondition" class="form-select" required>
                <option value="Brand New">Brand New</option>
                <option value="Re-Issue">Re-Issue</option>
              </select>
              <small class="text-muted">Current condition of the equipment</small>
            </div>

            <!-- Equipment Details Display -->
            <div class="col-12" id="equipmentDetailsCard" style="display: none;">
              <div class="card bg-light border-primary">
                <div class="card-body">
                  <h6 class="card-title text-primary"><i class="fas fa-info-circle"></i> Equipment Specifications (Read-Only)</h6>
                  <div id="equipmentDetailsContent"></div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div class="col-12">
              <label class="form-label"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this assignment..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Assign Equipment
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete User Modal -->
  <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-trash-alt"></i> Delete User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" action="delete_user.php">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle"></i> <strong>Warning!</strong> This action cannot be undone.
            </div>
            <p>Are you sure you want to delete this user?</p>
            <div class="card bg-light">
              <div class="card-body">
                <p class="mb-2"><strong>Employee Number:</strong> <span id="deleteEmployeeNumber"></span></p>
                <p class="mb-0"><strong>Username:</strong> <span id="deleteUsername"></span></p>
              </div>
            </div>
            <p class="text-danger mt-3 mb-0"><small><strong>Note:</strong> All data associated with this user will be permanently deleted.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">
              <i class="fas fa-trash-alt"></i> Delete User
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

  <!-- jQuery (required for Select2) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    // Handle Edit User Modal
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('editUserId').value = button.getAttribute('data-user-id');
      document.getElementById('editEmployeeNumber').value = button.getAttribute('data-user-empnum');
      document.getElementById('editUsername').value = button.getAttribute('data-user-username');
      document.getElementById('editFirstName').value = button.getAttribute('data-user-firstname');
      document.getElementById('editLastName').value = button.getAttribute('data-user-lastname');
      document.getElementById('editDepartment').value = button.getAttribute('data-user-department');
      document.getElementById('editRole').value = button.getAttribute('data-user-role');
    });

    // Handle Change Password Modal
    const changePasswordModal = document.getElementById('changePasswordModal');
    changePasswordModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const userId = button.getAttribute('data-user-id');
      const username = button.getAttribute('data-username');
      
      document.getElementById('changePasswordUserId').value = userId;
      document.getElementById('changePasswordUsername').textContent = username;
    });

    // Handle Delete User Modal
    const deleteUserModal = document.getElementById('deleteUserModal');
    deleteUserModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('deleteUserId').value = button.getAttribute('data-user-id');
      document.getElementById('deleteEmployeeNumber').textContent = button.getAttribute('data-employee-number');
      document.getElementById('deleteUsername').textContent = button.getAttribute('data-username');
    });

    // Department change handler for Add User
    document.getElementById('departmentSelect').addEventListener('change', function (){
      var v = this.value;
      var show = (v === 'OPERATION' || v === 'ADMIN');
      document.getElementById('storeRow').style.display = show ? 'block' : 'none';
    });

    // Handle User Detail Modal
    const userDetailModal = document.getElementById('userDetailModal');
    userDetailModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const userId = button.getAttribute('data-user-id');
      const username = button.getAttribute('data-user-username');
      const empNum = button.getAttribute('data-user-empnum');
      const firstName = button.getAttribute('data-user-firstname');
      const lastName = button.getAttribute('data-user-lastname');
      const department = button.getAttribute('data-user-department');
      const role = button.getAttribute('data-user-role');
      
      document.getElementById('detailUsername').textContent = username;
      document.getElementById('detailEmpNum').textContent = empNum;
      document.getElementById('detailFullName').textContent = (firstName + ' ' + lastName).trim() || '-';
      document.getElementById('detailDepartment').textContent = department || '-';
      document.getElementById('detailRole').textContent = role;
      
      // Load current equipment and history via AJAX
      loadUserEquipment(userId);
    });

    function loadUserEquipment(userId) {
      console.log('Loading equipment for user ID:', userId);
      // Load current borrowed equipment
      fetch('get_user_equipment.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
          console.log('Equipment data received:', data);
          const currentContainer = document.getElementById('currentEquipmentContainer');
          const historyContainer = document.getElementById('historyContainer');
          
          // Current equipment
          if (data.current && data.current.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Equipment</th><th>Category</th><th>Condition</th><th>Details</th><th>Assigned</th><th>Notes</th><th>Action</th></tr></thead><tbody>';
            data.current.forEach(item => {
              html += '<tr>';
              html += '<td><strong>' + escapeHtml(item.item_name) + '</strong></td>';
              html += '<td><span class="badge bg-secondary">' + escapeHtml(item.category_name) + '</span></td>';
              
              // Condition badge
              if (item.item_condition === 'Brand New') {
                html += '<td><span class="badge bg-success"><i class="fas fa-star"></i> Brand New</span></td>';
              } else {
                html += '<td><span class="badge bg-info text-dark"><i class="fas fa-recycle"></i> Re-Issue</span></td>';
              }
              
              html += '<td><small>' + escapeHtml(item.details || '-') + '</small></td>';
              html += '<td><small>' + escapeHtml(item.assigned_at) + '</small></td>';
              html += '<td><small>' + escapeHtml(item.notes || '-') + '</small></td>';
              html += '<td><button class="btn btn-sm btn-warning" onclick="returnEquipment(' + item.assignment_id + ', \'' + escapeHtml(item.item_name) + '\')"><i class="fas fa-undo"></i> Return</button></td>';
              html += '</tr>';
            });
            html += '</tbody></table></div>';
            currentContainer.innerHTML = html;
          } else {
            currentContainer.innerHTML = '<p class="text-muted"><i class="fas fa-inbox"></i> No equipment currently borrowed</p>';
          }
          
          // History
          if (data.history && data.history.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Equipment</th><th>Category</th><th>Condition</th><th>Assigned</th><th>Returned</th><th>Duration</th><th>Notes</th></tr></thead><tbody>';
            data.history.forEach(item => {
              html += '<tr>';
              html += '<td>' + escapeHtml(item.item_name) + '</td>';
              html += '<td><span class="badge bg-secondary">' + escapeHtml(item.category_name) + '</span></td>';
              
              // Condition badge
              if (item.item_condition === 'Brand New') {
                html += '<td><span class="badge bg-success"><i class="fas fa-star"></i> Brand New</span></td>';
              } else {
                html += '<td><span class="badge bg-info text-dark"><i class="fas fa-recycle"></i> Re-Issue</span></td>';
              }
              
              html += '<td><small>' + escapeHtml(item.assigned_at) + '</small></td>';
              html += '<td><small>' + escapeHtml(item.returned_at) + '</small></td>';
              html += '<td><small>' + escapeHtml(item.duration) + '</small></td>';
              html += '<td><small>' + escapeHtml(item.notes || '-') + '</small></td>';
              html += '</tr>';
            });
            html += '</tbody></table></div>';
            historyContainer.innerHTML = html;
          } else {
            historyContainer.innerHTML = '<p class="text-muted"><i class="fas fa-inbox"></i> No assignment history</p>';
          }
        })
        .catch(error => {
          console.error('Error loading equipment:', error);
          document.getElementById('currentEquipmentContainer').innerHTML = '<div class="alert alert-danger">Error loading equipment data: ' + error.message + '</div>';
          document.getElementById('historyContainer').innerHTML = '<div class="alert alert-danger">Error loading history data</div>';
        });
    }

    function returnEquipment(assignmentId, itemName) {
      // Set modal data
      document.getElementById('returnAssignmentId').value = assignmentId;
      document.getElementById('returnItemName').textContent = itemName;
      document.getElementById('returnDamageDetails').value = '';
      document.getElementById('damageDetailsContainer').style.display = 'none';
      document.getElementById('returnCondition').value = 'perfectly-working';
      
      // Show modal
      const returnModal = new bootstrap.Modal(document.getElementById('returnEquipmentModal'));
      returnModal.show();
    }
    
    // Handle return condition change
    function handleReturnConditionChange() {
      const condition = document.getElementById('returnCondition').value;
      const damageContainer = document.getElementById('damageDetailsContainer');
      if (condition === 'damaged') {
        damageContainer.style.display = 'block';
        document.getElementById('returnDamageDetails').required = true;
      } else {
        damageContainer.style.display = 'none';
        document.getElementById('returnDamageDetails').required = false;
      }
    }
    
    // Submit return form
    function submitReturnEquipment() {
      const assignmentId = document.getElementById('returnAssignmentId').value;
      const condition = document.getElementById('returnCondition').value;
      const damageDetails = document.getElementById('returnDamageDetails').value;
      
      if (condition === 'damaged' && !damageDetails.trim()) {
        alert('Please describe the damage details');
        return;
      }
      
      const formData = new URLSearchParams();
      formData.append('assignment_id', assignmentId);
      formData.append('return_condition', condition);
      formData.append('damage_details', damageDetails);
      
      fetch('return_equipment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Close return modal
          const returnModal = bootstrap.Modal.getInstance(document.getElementById('returnEquipmentModal'));
          returnModal.hide();
          
          // Reload the equipment list
          const userId = document.getElementById('detailUsername').getAttribute('data-user-id');
          loadUserEquipment(userId);
          alert('Equipment returned successfully!');
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error returning equipment');
      });
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Handle Assign Equipment Modal
    const assignEquipmentModal = document.getElementById('assignEquipmentModal');
    assignEquipmentModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const userId = button.getAttribute('data-user-id');
      const username = button.getAttribute('data-user-username');
      const empNum = button.getAttribute('data-user-empnum');
      const fullName = button.getAttribute('data-user-fullname');
      const department = button.getAttribute('data-user-department');
      
      document.getElementById('assignUserId').value = userId;
      document.getElementById('assignUsername').textContent = username;
      document.getElementById('assignEmpNum').textContent = empNum;
      document.getElementById('assignDepartment').textContent = department || '-';
      
      // Reset form
      document.getElementById('assignCategory').value = '';
      document.getElementById('equipmentSearch').style.display = 'none';
      document.getElementById('equipmentListDisplay').innerHTML = '';
      $('#assignEquipment').val('');
      document.getElementById('equipmentDetailsCard').style.display = 'none';
      document.getElementById('assignCondition').value = 'Brand New';
    });

    // Category change handler - load equipment for selected category
    document.getElementById('assignCategory').addEventListener('change', function() {
      const categoryId = this.value;
      const equipmentSelect = $('#assignEquipment');
      
      // Destroy Select2 if exists
      if (equipmentSelect.data('select2')) {
        equipmentSelect.select2('destroy');
      }
      
      if (!categoryId) {
        equipmentSelect.prop('disabled', true);
        equipmentSelect.html('<option value="">-- Select category first --</option>');
        document.getElementById('equipmentDetailsCard').style.display = 'none';
        return;
      }
      
      // Load available equipment for this category
      fetch('get_available_equipment.php?category_id=' + categoryId)
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          console.log('=== EQUIPMENT API RESPONSE ===');
          console.log('Full data:', data);
          console.log('data.items exists?', data.items ? 'YES' : 'NO');
          console.log('data.items length:', data.items ? data.items.length : 'N/A');
          console.log('data.items content:', data.items);
          
          // Log each item individually
          if (data.items) {
            data.items.forEach((item, index) => {
              console.log(`Item ${index}:`, item);
              console.log(`  - id: ${item.id}`);
              console.log(`  - name: ${item.name}`);
              console.log(`  - details:`, item.details);
              console.log(`  - item_condition: ${item.item_condition}`);
            });
          }
          console.log('============================');
          
          if (data.items && data.items.length > 0) {
            console.log('Building dropdown options...');
            let html = '<option value="">-- Select equipment --</option>';
            data.items.forEach(item => {
              console.log('Processing item:', item.name);
              // Get S/N from details
              let serialNum = '';
              if (item.details && typeof item.details === 'object') {
                serialNum = item.details.s_n || item.details.S_N || item.details.SN || item.details.sn || '';
                console.log('  Serial number found:', serialNum);
              }
              
              // Build display text with S/N
              let displayText = escapeHtml(item.name);
              if (serialNum) {
                displayText += ' (S/N: ' + escapeHtml(serialNum) + ')';
              }
              displayText += ' [' + (item.item_condition || 'Brand New') + ']';
              console.log('  Display text:', displayText);
              
              html += '<option value="' + item.id + '" data-details=\'' + JSON.stringify(item.details) + '\' data-condition="' + (item.item_condition || 'Brand New') + '">' + displayText + '</option>';
            });
            console.log('Final HTML:', html);
            
            // Skip Select2 completely - use custom searchable list
            const listDisplay = document.getElementById('equipmentListDisplay');
            const searchBox = document.getElementById('equipmentSearch');
            const hiddenSelect = $('#assignEquipment');
            
            // Populate hidden select with options (for form validation)
            hiddenSelect.html(html);
            
            // Store items data globally for searching
            window.equipmentItems = data.items;
            
            // Show search box
            searchBox.style.display = 'block';
            
            // Function to show equipment details
            function showEquipmentDetails(details, condition) {
              console.log('showEquipmentDetails called');
              console.log('Details:', details);
              console.log('Condition:', condition);
              
              const detailsCard = document.getElementById('equipmentDetailsCard');
              const detailsContent = document.getElementById('equipmentDetailsContent');
              
              console.log('detailsCard:', detailsCard);
              console.log('detailsContent:', detailsContent);
              
              // Auto-fill condition
              document.getElementById('assignCondition').value = condition || 'Brand New';
              
              if (details && Object.keys(details).length > 0) {
                let html = '<div class="row g-2">';
                
                Object.entries(details).forEach(([key, value]) => {
                  let label = key.replace(/_/g, ' ').toUpperCase();
                  if (label === 'S N') label = 'S/N';
                  
                  html += '<div class="col-md-6">';
                  html += '<label class="form-label small fw-bold text-muted">' + escapeHtml(label) + '</label>';
                  html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(value) + '" readonly>';
                  html += '</div>';
                });
                
                html += '</div>';
                detailsContent.innerHTML = html;
                detailsCard.style.display = 'block';
                console.log('Details card shown with HTML:', html);
              } else {
                console.log('No details or empty details object');
              }
            }
            
            // Create clickable list
            function renderEquipmentList(items, filterText = '') {
              let listHtml = '<div class="list-group" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem;">';
              
              items.forEach(item => {
                let serialNum = '';
                if (item.details && typeof item.details === 'object') {
                  serialNum = item.details.s_n || item.details.S_N || item.details.SN || item.details.sn || '';
                }
                
                let displayText = escapeHtml(item.name);
                if (serialNum) {
                  displayText += ' (S/N: ' + escapeHtml(serialNum) + ')';
                }
                displayText += ' [' + (item.item_condition || 'Brand New') + ']';
                
                // Check if matches filter
                if (filterText && !displayText.toLowerCase().includes(filterText.toLowerCase())) {
                  return; // Skip this item
                }
                
                listHtml += '<a href="#" class="list-group-item list-group-item-action equipment-item" ';
                listHtml += 'data-item-id="' + item.id + '" ';
                listHtml += 'data-details=\'' + JSON.stringify(item.details).replace(/'/g, "&apos;") + '\' ';
                listHtml += 'data-condition="' + (item.item_condition || 'Brand New') + '">';
                listHtml += displayText;
                listHtml += '</a>';
              });
              
              listHtml += '</div>';
              listDisplay.innerHTML = listHtml;
              
              // Add click handlers
              document.querySelectorAll('.equipment-item').forEach(el => {
                el.addEventListener('click', function(e) {
                  e.preventDefault();
                  console.log('Equipment item clicked');
                  
                  // Remove active class from all
                  document.querySelectorAll('.equipment-item').forEach(x => x.classList.remove('active'));
                  
                  // Add active to clicked
                  this.classList.add('active');
                  
                  // Set hidden select value
                  const itemId = this.getAttribute('data-item-id');
                  hiddenSelect.val(itemId);
                  console.log('Item ID:', itemId);
                  
                  // Show equipment details
                  const detailsStr = this.getAttribute('data-details');
                  console.log('Details string:', detailsStr);
                  const details = JSON.parse(detailsStr);
                  const condition = this.getAttribute('data-condition');
                  console.log('Parsed details:', details);
                  console.log('Condition:', condition);
                  
                  showEquipmentDetails(details, condition);
                });
              });
            }
            
            // Initial render
            renderEquipmentList(data.items);
            
            // Search functionality
            searchBox.value = '';
            searchBox.addEventListener('input', function() {
              renderEquipmentList(window.equipmentItems, this.value);
            });
            
            // Show list when search box is clicked or focused
            searchBox.addEventListener('click', function() {
              listDisplay.style.display = 'block';
            });
            searchBox.addEventListener('focus', function() {
              listDisplay.style.display = 'block';
            });
            
            console.log('Custom searchable list created with ' + data.items.length + ' items');
            
          } else {
            console.log('No items found in response');
            document.getElementById('equipmentSearch').style.display = 'none';
            document.getElementById('equipmentListDisplay').innerHTML = '<p class="text-muted">No available equipment in this category</p>';
          }
          document.getElementById('equipmentDetailsCard').style.display = 'none';
        })
        .catch(error => {
          console.error('Error loading equipment:', error);
          alert('Error loading equipment: ' + error.message);
          document.getElementById('equipmentSearch').style.display = 'none';
          document.getElementById('equipmentListDisplay').innerHTML = '<p class="text-danger">Error loading equipment</p>';
        });
    });

    // Equipment selection handler - show details as readonly form fields (works with Select2)
    $('#assignEquipment').on('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const detailsData = selectedOption.getAttribute('data-details');
      const itemCondition = selectedOption.getAttribute('data-condition');
      const detailsCard = document.getElementById('equipmentDetailsCard');
      const detailsContent = document.getElementById('equipmentDetailsContent');
      
      // Auto-fill condition based on item's current condition
      if (itemCondition) {
        document.getElementById('assignCondition').value = itemCondition;
      }
      
      if (this.value && detailsData) {
        try {
          const details = JSON.parse(detailsData);
          
          // Build readonly form fields for each modifier
          let html = '<div class="row g-2">';
          const entries = Object.entries(details);
          
          if (entries.length > 0) {
            entries.forEach(([key, value]) => {
              // Format the key to be more readable (e.g., "s_n" -> "S/N", "imei_1" -> "IMEI 1")
              let label = key.replace(/_/g, ' ').toUpperCase();
              if (label === 'S N') label = 'S/N';
              if (label.includes('IMEI')) label = label.replace('IMEI ', 'IMEI ');
              
              html += '<div class="col-md-6">';
              html += '<label class="form-label small fw-bold text-muted">' + escapeHtml(label) + '</label>';
              html += '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(value) + '" readonly>';
              html += '</div>';
            });
          } else {
            html += '<div class="col-12"><p class="text-muted mb-0">No additional details available</p></div>';
          }
          
          html += '</div>';
          detailsContent.innerHTML = html;
          detailsCard.style.display = 'block';
        } catch (e) {
          console.error('Error parsing equipment details:', e);
          detailsCard.style.display = 'none';
        }
      } else {
        detailsCard.style.display = 'none';
      }
    });

    // Store userId in the username element for return function
    userDetailModal.addEventListener('shown.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (button) {
        document.getElementById('detailUsername').setAttribute('data-user-id', button.getAttribute('data-user-id'));
      }
    });
  </script>

  <!-- Return Equipment Modal -->
  <div class="modal fade" id="returnEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form onsubmit="event.preventDefault(); submitReturnEquipment();" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Return Equipment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="returnAssignmentId">
          <div class="alert alert-info">
            <i class="fas fa-undo"></i> Returning: <strong id="returnItemName"></strong>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Equipment Condition <span class="text-danger">*</span></label>
            <select id="returnCondition" class="form-select" required onchange="handleReturnConditionChange()">
              <option value="perfectly-working">Perfectly Working</option>
              <option value="minor-issue">Minor Dent/Minor Problem</option>
              <option value="damaged">Damaged</option>
            </select>
          </div>
          
          <div id="damageDetailsContainer" style="display: none;">
            <div class="mb-3">
              <label class="form-label">Damage Details <span class="text-danger">*</span></label>
              <textarea id="returnDamageDetails" class="form-control" rows="3" placeholder="Describe the damage in detail..."></textarea>
              <small class="text-muted">Please provide details about the damage</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-check"></i> Confirm Return
          </button>
        </div>
      </form>
    </div>
  </div>

