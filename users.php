<?php
require_once __DIR__ . '/helpers.php';
require_role(['admin']);
require_once __DIR__ . '/config.php';

$stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Users</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Role</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="4" class="text-muted">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['role']); ?></td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
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

  <?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

  <script>
    document.getElementById('departmentSelect').addEventListener('change', function (){
      var v = this.value;
      var show = (v === 'OPERATION' || v === 'ADMIN');
      document.getElementById('storeRow').style.display = show ? 'block' : 'none';
    });
  </script>
