<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

$auth = new AdminAuth($pdo);
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit;
}

$productObj = new Product($pdo);
$categoryObj = new Category($pdo);
$categories = $categoryObj->getAllCategories();

// Handle Form Submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $status = $_POST['status'];
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Validate file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_types)) {
                $error = "Invalid file type. Only JPG, PNG, GIF, and WebP files are allowed.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = "File size too large. Maximum size is 5MB.";
            } else {
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target = '../uploads/' . $filename;
                
                // Ensure uploads directory exists
                if (!is_dir('../uploads')) {
                    mkdir('../uploads', 0755, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $image = 'uploads/' . $filename;
                } else {
                    $error = "Failed to upload file. Please check directory permissions.";
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle upload errors
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "File size too large.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "File was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = "Missing temporary folder.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = "File upload stopped by extension.";
                    break;
                default:
                    $error = "Unknown upload error.";
                    break;
            }
        }

        if (!empty($title) && !empty($price) && !empty($category_id)) {
            $stmt = $pdo->prepare("INSERT INTO products (title, description, price, category_id, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $price, $category_id, $image, $status])) {
                $success = "Product added successfully!";
            } else {
                $error = "Failed to add product.";
            }
        } elseif (empty($category_id)) {
            $error = "Please select a category for the product.";
        }
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php");
    exit;
}

// Stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$active_products = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$inactive_products = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'inactive'")->fetchColumn();

$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();

include '../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<body class="admin-body">

<div class="container-fluid">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 p-0 admin-sidebar d-none d-lg-block">
            <div class="p-4 mb-2">
                <h4 class="fw-bold text-white mb-0">YBT Digital Admin</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item"><i class="fas fa-th-large me-3"></i>Dashboard</a>
                <a href="products.php" class="list-group-item active"><i class="fas fa-box me-3"></i>Products</a>
                <a href="categories.php" class="list-group-item"><i class="fas fa-tags me-3"></i>Categories</a>
                <a href="orders.php" class="list-group-item"><i class="fas fa-shopping-cart me-3"></i>Orders</a>
                <a href="users.php" class="list-group-item"><i class="fas fa-users me-3"></i>Users</a>
                <a href="coupons.php" class="list-group-item"><i class="fas fa-ticket-alt me-3"></i>Coupons</a>
                <a href="reports.php" class="list-group-item"><i class="fas fa-chart-bar me-3"></i>Reports</a>
                <a href="settings.php" class="list-group-item"><i class="fas fa-cog me-3"></i>Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-white mb-0">Products Management</h2>
                <div class="d-flex gap-3">
                    <a href="add-product.php" class="btn btn-action text-white shadow-sm" style="background: var(--admin-primary);">
                        <i class="fas fa-plus"></i> ADD PRODUCT
                    </a>
                </div>
            </div>

            <!-- Product Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="admin-card stat-card primary p-4">
                        <div class="stat-icon text-primary"><i class="fas fa-box"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Products</h6>
                        <h3 class="fw-bold mb-0"><?php echo $total_products; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-card stat-card success p-4">
                        <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Active Products</h6>
                        <h3 class="fw-bold mb-0"><?php echo $active_products; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-card stat-card warning p-4">
                        <div class="stat-icon text-warning"><i class="fas fa-pause-circle"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Inactive Products</h6>
                        <h3 class="fw-bold mb-0"><?php echo $inactive_products; ?></h3>
                    </div>
                </div>
            </div>

            
            <div class="admin-card overflow-hidden">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Products List</h5>
                    <span class="badge rounded-pill bg-info px-3 py-2"><?php echo count($products); ?> TOTAL PRODUCTS</span>
                </div>
                <div class="p-0">
                    <?php if(empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box fa-4x text-muted opacity-25 mb-3"></i>
                            <h4 class="text-muted">No products found</h4>
                            <p class="text-muted mb-4">Start by adding your first product.</p>
                            <button class="btn btn-modern btn-primary px-5" onclick="window.location.href='add-product.php'">
                                <i class="fas fa-plus me-2"></i> ADD FIRST PRODUCT
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table admin-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="px-4">Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th class="text-end px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $p): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center">
                                                <img src="../<?php echo $p['image'] ?: 'assets/img/product-placeholder.svg'; ?>" width="40" height="40" class="rounded me-3 object-fit-cover">
                                                <span class="fw-bold"><?php echo htmlspecialchars($p['title']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($p['category_name'] ?: 'Uncategorized'); ?></span></td>
                                        <td class="fw-bold text-primary">₹<?php echo number_format($p['price'], 2); ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?php echo $p['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo strtoupper($p['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="btn-group shadow-0">
                                                <button class="btn btn-sm btn-link text-primary"><i class="fas fa-edit"></i></button>
                                                <a href="products.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('Delete product?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content admin-card">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold text-white">Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-outline mb-4" data-mdb-input-init>
                                <input type="text" name="title" id="pTitle" class="form-control" required />
                                <label class="form-label" for="pTitle">Product Title</label>
                            </div>
                            <div class="form-outline mb-4" data-mdb-input-init>
                                <textarea name="description" id="pDesc" class="form-control" rows="4"></textarea>
                                <label class="form-label" for="pDesc">Description</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline mb-4" data-mdb-input-init>
                                <input type="number" step="0.01" name="price" id="pPrice" class="form-control" required />
                                <label class="form-label" for="pPrice">Price (₹)</label>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small">Category</label>
                                <select name="category_id" class="form-select bg-dark border-0 text-white" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small">Status</label>
                                <select name="status" class="form-select bg-dark border-0 text-white">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Product Image</label>
                        <input type="file" name="image" class="form-control bg-dark border-0 text-white" accept="image/*" />
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="submit" name="add_product" class="btn btn-modern btn-primary px-5" style="background: var(--admin-gradient); border: none;">SAVE PRODUCT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
