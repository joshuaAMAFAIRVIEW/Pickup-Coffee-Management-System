<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin','manager']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid category ID');
}

// Fetch category
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die('Category not found');
}

// Fetch modifiers
$stmt = $pdo->prepare('SELECT * FROM category_modifiers WHERE category_id = ? ORDER BY position');
$stmt->execute([$id]);
$modifiers = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $new_modifiers = $_POST['modifiers'] ?? [];
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        try {
            // Update category name
            $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            
            // Delete existing modifiers
            $stmt = $pdo->prepare('DELETE FROM category_modifiers WHERE category_id = ?');
            $stmt->execute([$id]);
            
            // Insert new modifiers
            $position = 0;
            foreach ($new_modifiers as $mod) {
                $label = trim($mod);
                if (empty($label)) continue;
                
                $key_name = strtoupper(str_replace([' ', '-', '/'], '_', $label));
                
                $stmt = $pdo->prepare('INSERT INTO category_modifiers (category_id, label, key_name, type, required, position) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$id, $label, $key_name, 'text', 0, $position++]);
            }
            
            $_SESSION['success_message'] = 'Category updated successfully!';
            header('Location: categories.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error updating category: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'dashboard_nav_wrapper_start.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Category</h1>
            <p class="text-muted mb-0">Update category details and modifiers</p>
        </div>
        <a href="categories.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Categories
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Category Name</label>
                    <input name="name" required class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" placeholder="e.g. Laptop, Tablet, Monitor">
                    <small class="text-muted">Enter a descriptive name for this equipment category</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Available Modifiers</label>
                    <p class="small text-muted mb-2">Select the fields you want to track for this category</p>
                    <div id="availableModifiersContainer">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Custom Modifier</label>
                    <p class="small text-muted mb-2">Add a new modifier if it doesn't exist above (e.g. Quantity, Warranty Expiry)</p>
                    <div class="input-group">
                        <input id="customModInput" class="form-control" placeholder="e.g. QUANTITY, WARRANTY">
                        <button class="btn btn-outline-primary" type="button" id="addCustomModBtn">+ Add</button>
                    </div>
                    <div id="customMods" class="mt-3"></div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
                        <i class="fas fa-trash"></i> Delete Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the category <strong><?php echo htmlspecialchars($category['name']); ?></strong>?</p>
                <p class="text-danger mb-0"><small>This will also delete all associated modifiers and items.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="delete_category.php" class="d-inline">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'dashboard_nav_wrapper_end.php'; ?>

<script>
    const existingModLabels = <?php echo json_encode(array_column($modifiers, 'label')); ?>;

    // Load available modifiers on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAvailableModifiers();
    });

    function loadAvailableModifiers() {
        const container = document.getElementById('availableModifiersContainer');
        container.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        fetch('get_all_modifiers.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.modifiers.length > 0) {
                    let html = '<div class="d-flex flex-wrap gap-2">';
                    data.modifiers.forEach(modifier => {
                        const id = 'mod_' + modifier.id;
                        const checked = existingModLabels.includes(modifier.label) ? 'checked' : '';
                        html += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modifiers[]" value="${escapeHtml(modifier.label)}" id="${id}" ${checked}>
                                <label class="form-check-label" for="${id}">${escapeHtml(modifier.label)}</label>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No modifiers available. Add a custom modifier below.</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Error loading modifiers</div>';
            });
    }

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Custom modifier functionality
    document.getElementById('addCustomModBtn').addEventListener('click', function() {
        const input = document.getElementById('customModInput');
        const v = input.value.trim();
        if (!v) {
            alert('Please enter a modifier name');
            return;
        }
        
        const id = 'custom_' + Date.now();
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = '<input class="form-check-input" type="checkbox" name="modifiers[]" value="' + escapeHtml(v) + '" id="' + id + '" checked> <label class="form-check-label" for="' + id + '">' + escapeHtml(v) + '</label>';
        document.getElementById('customMods').appendChild(div);
        input.value = '';
    });
</script>
</body>
</html>
