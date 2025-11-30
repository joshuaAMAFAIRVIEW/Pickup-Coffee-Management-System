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
          <a href="add_item.php" class="list-group-item list-group-item-action ps-4">Add Item</a>
          <a href="categories.php" class="list-group-item list-group-item-action ps-4">Categories</a>
        </div>
        <a href="#" class="list-group-item list-group-item-action">Reports</a>
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
