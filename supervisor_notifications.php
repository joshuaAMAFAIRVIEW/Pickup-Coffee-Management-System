<?php
require_once __DIR__ . '/helpers.php';
require_role(['store_supervisor']);
require_once __DIR__ . '/config.php';

$user = $_SESSION['user'];

// Get removal notifications (unread first, then recent read ones)
$removalStmt = $pdo->prepare('
    SELECT 
        srn.*,
        u.first_name as manager_first_name,
        u.last_name as manager_last_name
    FROM supervisor_removal_notifications srn
    INNER JOIN users u ON srn.removed_by_user_id = u.id
    WHERE srn.supervisor_user_id = ?
    ORDER BY srn.is_read ASC, srn.removal_date DESC
    LIMIT 10
');
$removalStmt->execute([$user['id']]);
$removals = $removalStmt->fetchAll();

// Get pending and recent requests
$stmt = $pdo->prepare('
    SELECT 
        sar.*,
        s.store_name,
        s.store_code,
        s.address as store_address,
        a.area_name,
        u.first_name as manager_first_name,
        u.last_name as manager_last_name,
        u.email as manager_email
    FROM supervisor_assignment_requests sar
    INNER JOIN stores s ON sar.store_id = s.store_id
    LEFT JOIN areas a ON s.area_id = a.area_id
    INNER JOIN users u ON sar.area_manager_id = u.id
    WHERE sar.supervisor_user_id = ?
    ORDER BY sar.requested_at DESC
');
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<?php include __DIR__ . '/dashboard_nav_wrapper_start.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fas fa-inbox"></i> Notifications & Requests</h1>
        <p class="text-muted mb-0">Review notifications and assignment requests</p>
    </div>
</div>

<!-- Removal Notifications Section -->
<?php if (!empty($removals)): ?>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Store Assignment Removals</h5>
    </div>
    <div class="card-body">
        <?php foreach ($removals as $removal): ?>
            <div class="alert alert-<?php echo $removal['is_read'] ? 'secondary' : 'danger'; ?> mb-2" onclick="markRemovalAsRead(<?php echo $removal['id']; ?>)" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <?php if (!$removal['is_read']): ?>
                                <span class="badge bg-danger me-2">NEW</span>
                            <?php endif; ?>
                            You have been removed from: <strong><?php echo htmlspecialchars($removal['store_name']); ?></strong>
                        </h6>
                        <p class="mb-2">
                            <small>
                                <i class="fas fa-user me-1"></i>Removed by: <?php echo htmlspecialchars($removal['manager_first_name'] . ' ' . $removal['manager_last_name']); ?><br>
                                <i class="fas fa-clock me-1"></i>Date: <?php echo date('F j, Y g:i A', strtotime($removal['removal_date'])); ?>
                            </small>
                        </p>
                        <?php if ($removal['reason']): ?>
                            <div class="bg-light p-2 rounded">
                                <small><strong>Reason:</strong> <?php echo htmlspecialchars($removal['reason']); ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="my_previous_equipment.php?removal_id=<?php echo $removal['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View Previous Equipment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Assignment Requests Section -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Store Assignment Requests</h5>
    </div>
    <div class="card-body">
<?php if (empty($requests)): ?>
    <div class="text-center py-4">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No Requests</h5>
        <p class="text-muted mb-0">You have no assignment requests at this time.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($requests as $request): ?>
            <?php
            $isPending = $request['status'] === 'pending';
            $isAccepted = $request['status'] === 'accepted';
            $statusBadge = $isPending ? 'bg-warning text-dark' : ($isAccepted ? 'bg-success' : 'bg-danger');
            $statusIcon = $isPending ? 'fa-clock' : ($isAccepted ? 'fa-check-circle' : 'fa-times-circle');
            ?>
            <div class="col-md-6">
                <div class="card <?php echo $isPending ? 'border-warning' : ''; ?> h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?php echo htmlspecialchars($request['store_name']); ?></strong>
                        <span class="badge <?php echo $statusBadge; ?>">
                            <i class="fas <?php echo $statusIcon; ?> me-1"></i><?php echo ucfirst($request['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Store Code:</small><br>
                            <strong><?php echo htmlspecialchars($request['store_code']); ?></strong>
                        </div>
                        
                        <?php if ($request['area_name']): ?>
                            <div class="mb-2">
                                <small class="text-muted">Area:</small><br>
                                <?php echo htmlspecialchars($request['area_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($request['store_address']): ?>
                            <div class="mb-2">
                                <small class="text-muted">Address:</small><br>
                                <?php echo htmlspecialchars($request['store_address']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <small class="text-muted">Requested by:</small><br>
                            <?php echo htmlspecialchars(trim($request['manager_first_name'] . ' ' . $request['manager_last_name'])); ?>
                            <?php if ($request['manager_email']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($request['manager_email']); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Reason:</small><br>
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($request['reason']); ?></span>
                        </div>
                        
                        <?php if ($request['notes']): ?>
                            <div class="mb-2">
                                <small class="text-muted">Notes:</small><br>
                                <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>Requested: <?php echo date('M d, Y h:i A', strtotime($request['requested_at'])); ?>
                            </small>
                            <?php if ($request['responded_at']): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-reply me-1"></i>Responded: <?php echo date('M d, Y h:i A', strtotime($request['responded_at'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($isPending): ?>
                        <div class="card-footer bg-light">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" onclick="respondToRequest(<?php echo $request['id']; ?>, 'accepted')">
                                    <i class="fas fa-check me-2"></i>Accept Assignment
                                </button>
                                <button class="btn btn-outline-danger" onclick="respondToRequest(<?php echo $request['id']; ?>, 'declined')">
                                    <i class="fas fa-times me-2"></i>Decline
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

<script>
async function respondToRequest(requestId, action) {
    const confirmMsg = action === 'accepted' 
        ? 'Are you sure you want to ACCEPT this store assignment?' 
        : 'Are you sure you want to DECLINE this assignment request?';
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);
        
        const response = await fetch('respond_to_assignment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error processing response');
        console.error('Error:', error);
    }
}

async function markRemovalAsRead(removalId) {
    try {
        const formData = new FormData();
        formData.append('removal_id', removalId);
        
        await fetch('mark_removal_read.php', {
            method: 'POST',
            body: formData
        });
        
        // Reload to update UI
        window.location.reload();
    } catch (error) {
        console.error('Error marking as read:', error);
    }
}
</script>
</body>
</html>
