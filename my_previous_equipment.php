<?php
require_once __DIR__ . '/helpers.php';
require_role(['store_supervisor']);
require_once __DIR__ . '/config.php';

$user = $_SESSION['user'];
$removal_id = isset($_GET['removal_id']) ? (int)$_GET['removal_id'] : 0;

// Get removal notification details
$stmt = $pdo->prepare('
    SELECT 
        srn.*,
        u.first_name as manager_first_name,
        u.last_name as manager_last_name
    FROM supervisor_removal_notifications srn
    INNER JOIN users u ON srn.removed_by_user_id = u.id
    WHERE srn.id = ? AND srn.supervisor_user_id = ?
');
$stmt->execute([$removal_id, $user['id']]);
$removal = $stmt->fetch();

if (!$removal) {
    $_SESSION['error_message'] = 'Removal notification not found';
    header('Location: supervisor_notifications.php');
    exit;
}

// Get equipment that was assigned to this store
// Note: This shows equipment that was in the store at the time of removal
$equipmentStmt = $pdo->prepare('
    SELECT 
        i.id,
        i.item_name,
        i.serial_number,
        i.model,
        i.brand,
        i.status,
        c.category_name,
        sia.quantity,
        sia.assigned_date
    FROM store_item_assignments sia
    INNER JOIN items i ON sia.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE sia.store_id = ?
    ORDER BY sia.assigned_date DESC
');
$equipmentStmt->execute([$removal['store_id']]);
$equipment = $equipmentStmt->fetchAll();
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="supervisor_notifications.php">Notifications</a></li>
            <li class="breadcrumb-item active">Previous Equipment</li>
        </ol>
    </nav>

    <div class="card border-info mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Removal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Store:</strong> <?php echo htmlspecialchars($removal['store_name']); ?></p>
                    <p><strong>Removal Date:</strong> <?php echo date('F j, Y g:i A', strtotime($removal['removal_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Removed By:</strong> <?php echo htmlspecialchars($removal['manager_first_name'] . ' ' . $removal['manager_last_name']); ?></p>
                    <?php if ($removal['reason']): ?>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($removal['reason']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Equipment You Previously Managed</h5>
                <span class="badge bg-secondary"><?php echo count($equipment); ?> items</span>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Read-Only Access:</strong> This is a historical view of the equipment you managed. You can no longer modify or manage these items.
            </div>

            <?php if (empty($equipment)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Equipment Found</h5>
                    <p class="text-muted mb-0">No equipment records found for this store.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Serial Number</th>
                                <th>Brand/Model</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Assigned Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($item['category_name']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($item['serial_number']); ?></code></td>
                                    <td>
                                        <?php if ($item['brand'] || $item['model']): ?>
                                            <?php echo htmlspecialchars($item['brand']); ?>
                                            <?php if ($item['model']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($item['model']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo $item['quantity']; ?></span></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($item['status']) {
                                            case 'available': $statusClass = 'bg-success'; break;
                                            case 'in_use': $statusClass = 'bg-primary'; break;
                                            case 'maintenance': $statusClass = 'bg-warning'; break;
                                            case 'retired': $statusClass = 'bg-danger'; break;
                                            default: $statusClass = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($item['assigned_date'])); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light">
            <a href="supervisor_notifications.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Notifications
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
