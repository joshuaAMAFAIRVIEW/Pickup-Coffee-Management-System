<?php
require_once __DIR__ . '/helpers.php';
require_role(['area_manager']);
require_once __DIR__ . '/config.php';

$user = $_SESSION['user'];

// Get all requests made by this area manager
$stmt = $pdo->prepare('
    SELECT 
        sar.*,
        s.store_name,
        s.store_code,
        u.employee_number,
        u.first_name as supervisor_first_name,
        u.last_name as supervisor_last_name,
        u.email as supervisor_email
    FROM supervisor_assignment_requests sar
    INNER JOIN stores s ON sar.store_id = s.store_id
    INNER JOIN users u ON sar.supervisor_user_id = u.id
    WHERE sar.area_manager_id = ?
    ORDER BY 
        CASE sar.status
            WHEN "pending" THEN 1
            WHEN "accepted" THEN 2
            WHEN "declined" THEN 3
        END,
        sar.requested_at DESC
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fas fa-inbox"></i> Assignment Requests</h1>
        <p class="text-muted mb-0">Track the status of your supervisor assignment requests</p>
    </div>
</div>

<?php if (empty($requests)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Requests Yet</h5>
            <p class="text-muted mb-3">You haven't sent any assignment requests.</p>
            <a href="manage_team.php" class="btn btn-primary">
                <i class="fas fa-user-check me-2"></i>Request Supervisor Assignment
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
        $accepted = count(array_filter($requests, fn($r) => $r['status'] === 'accepted'));
        $declined = count(array_filter($requests, fn($r) => $r['status'] === 'declined'));
        ?>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Pending</h6>
                    <h2 class="text-warning"><?php echo $pending; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Accepted</h6>
                    <h2 class="text-success"><?php echo $accepted; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Declined</h6>
                    <h2 class="text-danger"><?php echo $declined; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests List -->
    <div class="row g-3">
        <?php foreach ($requests as $request): ?>
            <?php
            $isPending = $request['status'] === 'pending';
            $isAccepted = $request['status'] === 'accepted';
            $statusBadge = $isPending ? 'bg-warning text-dark' : ($isAccepted ? 'bg-success' : 'bg-danger');
            $statusIcon = $isPending ? 'fa-clock' : ($isAccepted ? 'fa-check-circle' : 'fa-times-circle');
            $borderClass = $isPending ? 'border-warning' : ($isAccepted ? 'border-success' : 'border-danger');
            ?>
            <div class="col-md-6">
                <div class="card <?php echo $borderClass; ?> h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars(trim($request['supervisor_first_name'] . ' ' . $request['supervisor_last_name'])); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($request['employee_number']); ?></small>
                        </div>
                        <span class="badge <?php echo $statusBadge; ?>">
                            <i class="fas <?php echo $statusIcon; ?> me-1"></i><?php echo ucfirst($request['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Requested Store:</small><br>
                            <strong><?php echo htmlspecialchars($request['store_name']); ?></strong>
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($request['store_code']); ?></span>
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
                        
                        <?php if ($request['supervisor_email']): ?>
                            <div class="mb-2">
                                <small class="text-muted">Supervisor Email:</small><br>
                                <a href="mailto:<?php echo htmlspecialchars($request['supervisor_email']); ?>">
                                    <?php echo htmlspecialchars($request['supervisor_email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>Sent: <?php echo date('M d, Y h:i A', strtotime($request['requested_at'])); ?>
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
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-hourglass-half me-2"></i>Waiting for supervisor response
                            </div>
                        </div>
                    <?php elseif ($isAccepted): ?>
                        <div class="card-footer bg-light">
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle me-2"></i>Assignment accepted! Supervisor is now assigned to this store.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-footer bg-light">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-times-circle me-2"></i>Assignment declined by supervisor
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>
</body>
</html>
