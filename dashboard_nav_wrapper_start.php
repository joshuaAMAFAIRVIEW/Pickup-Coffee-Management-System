<?php
// Start wrapper: sidebar and navbar — included by pages that use the dashboard layout
require_once __DIR__ . '/auth_check.php';
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="border-end" id="sidebar-wrapper">
      <div class="sidebar-heading p-3">
        <?php
        // Show logo if uploaded (preferred filenames), otherwise show placeholder text
        $logoFiles = [
          __DIR__ . '/assets/img/PICKUP-Horizontal-White.png',
          __DIR__ . '/assets/img/logo.png',
          __DIR__ . '/assets/img/logo.svg',
          // Also accept legacy img/ folder if your file is there
          __DIR__ . '/img/PICKUP-Horizontal-White.png',
        ];
        $found = null;
        foreach ($logoFiles as $p) {
            if (file_exists($p)) { $found = $p; break; }
        }
        if ($found):
            $public = 'assets/img/' . basename($found);
        ?>
          <img src="<?php echo $public; ?>" alt="PICKUP COFFEE" class="img-fluid mb-2" style="max-height:56px;">
        <?php else: ?>
          <div class="company-logo-placeholder mb-2">PICKUP COFFEE</div>
        <?php endif; ?>
      </div>
      <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
          <a class="list-group-item list-group-item-action" data-bs-toggle="collapse" href="#inventorySub" role="button" aria-expanded="false" aria-controls="inventorySub">Inventory</a>
        <div class="collapse" id="inventorySub">
          <a href="inventory.php" class="list-group-item list-group-item-action ps-4">Items</a>
          <?php if (isset($user['role']) && !in_array($user['role'], ['area_manager', 'store_supervisor'])): ?>
            <a href="add_item.php" class="list-group-item list-group-item-action ps-4">Add Item</a>
            <a href="categories.php" class="list-group-item list-group-item-action ps-4">Categories</a>
          <?php endif; ?>
        </div>
        <a href="accountability.php" class="list-group-item list-group-item-action">Release/Return</a>
        <a href="#" class="list-group-item list-group-item-action">Reports</a>
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
          <a href="activity_logs.php" class="list-group-item list-group-item-action">Activity Logs</a>
        <?php endif; ?>
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
          <a href="stores.php" class="list-group-item list-group-item-action">Stores</a>
        <?php endif; ?>
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
          <a href="release_package.php" class="list-group-item list-group-item-action">Store Release</a>
        <?php endif; ?>
        <?php if (isset($user['role']) && $user['role'] === 'area_manager'): ?>
          <a href="area_manager_requests.php" class="list-group-item list-group-item-action">
            Requests
            <?php
            // Get responded requests count (accepted or declined)
            $req_stmt = $pdo->prepare('SELECT COUNT(*) FROM supervisor_assignment_requests WHERE area_manager_id = ? AND status != ?');
            $req_stmt->execute([$user['id'], 'pending']);
            $response_count = $req_stmt->fetchColumn();
            if ($response_count > 0):
            ?>
              <span class="badge bg-info rounded-pill float-end"><?php echo $response_count; ?></span>
            <?php endif; ?>
          </a>
          <a href="supervisor_movements.php" class="list-group-item list-group-item-action">Supervisor Movements</a>
        <?php endif; ?>
        <?php if (isset($user['role']) && $user['role'] === 'store_supervisor'): ?>
          <a href="my_store.php" class="list-group-item list-group-item-action">My Store</a>
          <a href="supervisor_notifications.php" class="list-group-item list-group-item-action">
            Requests
            <?php
            // Get pending notifications count
            $notif_stmt = $pdo->prepare('SELECT COUNT(*) FROM supervisor_assignment_requests WHERE supervisor_user_id = ? AND status = ?');
            $notif_stmt->execute([$user['id'], 'pending']);
            $pending_count = $notif_stmt->fetchColumn();
            
            // Get unread removal notifications count
            $removal_stmt = $pdo->prepare('SELECT COUNT(*) FROM supervisor_removal_notifications WHERE supervisor_user_id = ? AND is_read = 0');
            $removal_stmt->execute([$user['id']]);
            $unread_removals = $removal_stmt->fetchColumn();
            
            $total_notifications = $pending_count + $unread_removals;
            
            if ($total_notifications > 0):
            ?>
              <span class="badge bg-danger rounded-pill float-end"><?php echo $total_notifications; ?></span>
            <?php endif; ?>
          </a>
          <a href="supervisor_movements.php" class="list-group-item list-group-item-action">My Movement History</a>
        <?php endif; ?>
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
          <a href="users.php" class="list-group-item list-group-item-action">Users</a>
        <?php endif; ?>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
      </div>
      </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page content -->
    <div id="page-content-wrapper" class="w-100">
      <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
          <div class="ms-auto me-3">Signed in as <strong><?php echo htmlspecialchars($user['username']); ?></strong></div>
        </div>
      </nav>

      <div class="container-fluid mt-4">
