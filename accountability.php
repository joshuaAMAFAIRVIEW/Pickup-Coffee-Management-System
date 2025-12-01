<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

$user = $_SESSION['user'];

// Get all assignments
$sql = "SELECT 
    ia.id as assignment_id,
    ia.assigned_at,
    ia.unassigned_at,
    ia.notes,
    i.id as item_id,
    i.display_name as item_name,
    i.attributes,
    i.item_condition,
    c.name as category_name,
    u.id as user_id,
    u.username,
    u.employee_number,
    u.first_name,
    u.last_name,
    u.department
FROM item_assignments ia
JOIN items i ON ia.item_id = i.id
JOIN categories c ON i.category_id = c.id
JOIN users u ON ia.user_id = u.id
ORDER BY ia.assigned_at DESC
LIMIT 100";

$stmt = $pdo->query($sql);
$assignments = $stmt->fetchAll();

include __DIR__ . '/dashboard_nav_wrapper_start.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-contract"></i> Release/Return Accountability</h2>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date Assigned</th>
              <th>Employee</th>
              <th>Department</th>
              <th>Equipment</th>
              <th>Category</th>
              <th>Condition</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $assignment): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($assignment['assigned_at'])); ?></td>
              <td>
                <strong><?php echo htmlspecialchars($assignment['username']); ?></strong><br>
                <small class="text-muted">
                  <?php echo htmlspecialchars(trim($assignment['first_name'] . ' ' . $assignment['last_name'])); ?><br>
                  EMP #<?php echo htmlspecialchars($assignment['employee_number']); ?>
                </small>
              </td>
              <td><?php echo htmlspecialchars($assignment['department'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($assignment['item_name']); ?></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars($assignment['category_name']); ?></span></td>
              <td>
                <?php if ($assignment['item_condition'] === 'Brand New'): ?>
                  <span class="badge bg-success"><i class="fas fa-star"></i> Brand New</span>
                <?php else: ?>
                  <span class="badge bg-info text-dark"><i class="fas fa-recycle"></i> Re-Issue</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($assignment['unassigned_at']): ?>
                  <span class="badge bg-secondary">Returned</span><br>
                  <small class="text-muted"><?php echo date('M d, Y', strtotime($assignment['unassigned_at'])); ?></small>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Currently Borrowed</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm btn-primary" onclick="printAccountability(<?php echo $assignment['assignment_id']; ?>)">
                  <i class="fas fa-print"></i> Print
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function printAccountability(assignmentId) {
  window.open('print_accountability.php?assignment_id=' + assignmentId, '_blank');
}
</script>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
