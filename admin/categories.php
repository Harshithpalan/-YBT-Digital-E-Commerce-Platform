<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            $icon = $_POST['icon'] ?? 'fas fa-folder';
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
            
            if (!empty($name)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $slug, $icon]);
                    $_SESSION['success'] = 'Category added successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error adding category: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Category name is required';
            }
            header('Location: categories.php');
            exit();
            break;
            
        case 'edit':
            $id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $icon = $_POST['icon'] ?? 'fas fa-folder';
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
            
            if (!empty($name) && !empty($id)) {
                try {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $icon, $id]);
                    $_SESSION['success'] = 'Category updated successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating category: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Category name and ID are required';
            }
            header('Location: categories.php');
            exit();
            break;
            
        case 'delete':
            $id = $_GET['id'] ?? '';
            if (!empty($id)) {
                try {
                    // Check if category has products
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                    $stmt->execute([$id]);
                    $productCount = $stmt->fetchColumn();
                    
                    if ($productCount > 0) {
                        $_SESSION['error'] = 'Cannot delete category. It contains ' . $productCount . ' products.';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$id]);
                        $_SESSION['success'] = 'Category deleted successfully!';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error deleting category: ' . $e->getMessage();
                }
            }
            header('Location: categories.php');
            exit();
            break;
    }
}

// Get all categories
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $_SESSION['error'] = 'Error fetching categories: ' . $e->getMessage();
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching category: ' . $e->getMessage();
    }
}

// Font Awesome icons for categories
$iconOptions = [
    'fas fa-laptop-code' => 'Development',
    'fas fa-palette' => 'Design',
    'fas fa-mobile-alt' => 'Mobile',
    'fas fa-camera' => 'Photography',
    'fas fa-video' => 'Video',
    'fas fa-music' => 'Music',
    'fas fa-book' => 'Books',
    'fas fa-graduation-cap' => 'Education',
    'fas fa-heartbeat' => 'Health',
    'fas fa-gamepad' => 'Gaming',
    'fas fa-shopping-bag' => 'Shopping',
    'fas fa-utensils' => 'Food',
    'fas fa-plane' => 'Travel',
    'fas fa-home' => 'Real Estate',
    'fas fa-car' => 'Automotive',
    'fas fa-dumbbell' => 'Fitness',
    'fas fa-briefcase' => 'Business',
    'fas fa-chart-line' => 'Marketing',
    'fas fa-cog' => 'Technology',
    'fas fa-folder' => 'General'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - YBT Digital Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        
        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 0.75rem 1.5rem !important;
            transition: all 0.3s ease;
            border-radius: 0;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .category-card {
            background-color: var(--admin-card) !important;
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .category-name {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .category-slug {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .product-count {
            background: rgba(99, 102, 241, 0.2);
            color: #6366f1;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            color: #fff;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .icon-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .icon-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .icon-option.selected {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-title, .form-label {
            color: #fff;
        }
        
        .btn-close {
            filter: invert(1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="text-center mb-4">
                <h4 class="text-white">YBT Digital</h4>
                <small class="text-muted">Admin Panel</small>
            </div>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="nav-link active">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="coupons.php" class="nav-link">
                    <i class="fas fa-ticket-alt"></i> Coupons
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="faqs.php" class="nav-link">
                    <i class="fas fa-question-circle"></i> FAQs
                </a>
                <a href="testimonials.php" class="nav-link">
                    <i class="fas fa-quote-left"></i> Testimonials
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-white mb-2">Categories Management</h2>
                        <p class="text-muted mb-0">Manage product categories and icons</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus me-2"></i> Add Category
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h4>No Categories Found</h4>
                    <p>Start by adding your first category.</p>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-icon">
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                            </div>
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                            <div class="category-slug"><?php echo htmlspecialchars($category['slug']); ?></div>
                            <div class="category-stats">
                                <span class="product-count"><?php echo $category['product_count']; ?> products</span>
                            </div>
                            <div class="category-actions">
                                <button class="btn-icon btn-edit" onclick="editCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="categoryForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Icon</label>
                            <div class="icon-grid">
                                <?php foreach ($iconOptions as $iconClass => $label): ?>
                                    <div class="icon-option" data-icon="<?php echo $iconClass; ?>">
                                        <i class="<?php echo $iconClass; ?>"></i>
                                        <span><?php echo $label; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="icon" id="selectedIcon" value="fas fa-folder">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Icon selection
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedIcon').value = this.dataset.icon;
            });
        });

        // Set default icon selection
        document.querySelector('[data-icon="fas fa-folder"]').classList.add('selected');

        // Edit category
        function editCategory(id) {
            // Load category data via AJAX or redirect
            window.location.href = '?edit=' + id;
        }

        // Delete category
        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category?')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }

        // Handle edit mode
        <?php if ($editCategory): ?>
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = '<?php echo $editCategory['id']; ?>';
            document.getElementById('categoryName').value = '<?php echo htmlspecialchars($editCategory['name']); ?>';
            
            // Select current icon
            document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
            const currentIcon = document.querySelector('[data-icon="<?php echo htmlspecialchars($editCategory['icon']); ?>"]');
            if (currentIcon) {
                currentIcon.classList.add('selected');
                document.getElementById('selectedIcon').value = '<?php echo htmlspecialchars($editCategory['icon']); ?>';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        <?php endif; ?>
    </script>
</body>
</html>
