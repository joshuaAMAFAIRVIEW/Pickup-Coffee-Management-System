<?php
// Dashboard page using the shared wrapper for sidebar/navbar
require_once __DIR__ . '/config.php';
include __DIR__ . '/dashboard_nav_wrapper_start.php';
// $user is available from the wrapper

// Fetch stats
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
?>

  <div class="mb-4">
    <h1 class="h3 mb-1">Dashboard</h1>
    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['username']); ?>. Role: <?php echo htmlspecialchars($user['role']); ?></p>
  </div>

  <!-- Stats Cards -->
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

<?php include __DIR__ . '/dashboard_nav_wrapper_end.php'; ?>

<?php if (!empty($regionData)): ?>
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
