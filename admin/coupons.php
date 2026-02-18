<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle coupon operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discountType = $_POST['discount_type'] ?? 'flat';
            $discountValue = $_POST['discount_value'] ?? 0;
            $expiryDate = $_POST['expiry_date'] ?? '';
            $usageLimit = $_POST['usage_limit'] ?? 100;
            $minimumAmount = $_POST['minimum_amount'] ?? 0;
            $description = $_POST['description'] ?? '';
            
            if (!empty($code) && $discountValue > 0) {
                try {
                    // Check if coupon code already exists
                    $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
                    $stmt->execute([$code]);
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = 'Coupon code already exists!';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO coupons (code, discount_type, discount_value, expiry_date, usage_limit, minimum_amount, description) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$code, $discountType, $discountValue, $expiryDate, $usageLimit, $minimumAmount, $description]);
                        $_SESSION['success'] = 'Coupon created successfully!';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating coupon: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Coupon code and discount value are required!';
            }
            header('Location: coupons.php');
            exit();
            break;
            
        case 'edit':
            $id = $_POST['id'] ?? '';
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discountType = $_POST['discount_type'] ?? 'flat';
            $discountValue = $_POST['discount_value'] ?? 0;
            $expiryDate = $_POST['expiry_date'] ?? '';
            $usageLimit = $_POST['usage_limit'] ?? 100;
            $minimumAmount = $_POST['minimum_amount'] ?? 0;
            $description = $_POST['description'] ?? '';
            
            if (!empty($id) && !empty($code) && $discountValue > 0) {
                try {
                    // Check if coupon code already exists (excluding current)
                    $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
                    $stmt->execute([$code, $id]);
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = 'Coupon code already exists!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE coupons SET 
                            code = ?, discount_type = ?, discount_value = ?, expiry_date = ?, 
                            usage_limit = ?, minimum_amount = ?, description = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$code, $discountType, $discountValue, $expiryDate, $usageLimit, $minimumAmount, $description, $id]);
                        $_SESSION['success'] = 'Coupon updated successfully!';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating coupon: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'All fields are required!';
            }
            header('Location: coupons.php');
            exit();
            break;
            
        case 'delete':
            $id = $_GET['id'] ?? '';
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = 'Coupon deleted successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error deleting coupon: ' . $e->getMessage();
                }
            }
            header('Location: coupons.php');
            exit();
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("UPDATE coupons SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['success'] = 'Coupon status updated!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating coupon status: ' . $e->getMessage();
                }
            }
            header('Location: coupons.php');
            exit();
            break;
    }
}

// Get all coupons with usage statistics
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               CASE 
                   WHEN c.expiry_date < CURDATE() THEN 'expired'
                   WHEN c.used_count >= c.usage_limit THEN 'exhausted'
                   ELSE c.status
               END as current_status
        FROM coupons c 
        ORDER BY c.created_at DESC
    ");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $coupons = [];
    $_SESSION['error'] = 'Error fetching coupons: ' . $e->getMessage();
}

// Get coupon statistics
try {
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM coupons WHERE status = 'active' AND expiry_date >= CURDATE() AND used_count < usage_limit")->fetchColumn(),
        'expired' => $pdo->query("SELECT COUNT(*) FROM coupons WHERE expiry_date < CURDATE()")->fetchColumn(),
        'exhausted' => $pdo->query("SELECT COUNT(*) FROM coupons WHERE used_count >= usage_limit")->fetchColumn(),
        'total_used' => $pdo->query("SELECT SUM(used_count) FROM coupons")->fetchColumn() ?: 0
    ];
} catch (PDOException $e) {
    $stats = [
        'total' => 0,
        'active' => 0,
        'expired' => 0,
        'exhausted' => 0,
        'total_used' => 0
    ];
}

// Get coupon for editing
$editCoupon = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching coupon: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons Management - YBT Digital Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .coupons-table {
            background-color: var(--admin-card) !important;
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table {
            color: #fff;
        }
        
        .table thead th {
            color: rgba(255, 255, 255, 0.9);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .coupon-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1rem;
            background: rgba(99, 102, 241, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .discount-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .discount-flat {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .discount-percentage {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .status-expired {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .status-exhausted {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .usage-progress {
            width: 100px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .usage-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: width 0.3s ease;
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
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            margin: 0 2px;
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
        
        .btn-toggle {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .btn-toggle:hover {
            background: rgba(107, 114, 128, 0.3);
            color: #6b7280;
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
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="coupons.php" class="nav-link active">
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
                        <h2 class="text-white mb-2">Coupons Management</h2>
                        <p class="text-muted mb-0">Create and manage discount coupons</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
                        <i class="fas fa-plus me-2"></i> Add Coupon
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

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Coupons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['expired']; ?></div>
                    <div class="stat-label">Expired</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_used']; ?></div>
                    <div class="stat-label">Total Uses</div>
                </div>
            </div>

            <!-- Coupons Table -->
            <div class="admin-card overflow-hidden p-4">
                <?php if (empty($coupons)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ticket-alt"></i>
                        <h4>No Coupons Found</h4>
                        <p>Start by creating your first coupon.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Usage</th>
                                    <th>Min. Amount</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td>
                                            <div class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                            <?php if (!empty($coupon['description'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($coupon['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="discount-badge discount-<?php echo $coupon['discount_type']; ?>">
                                                <?php echo $coupon['discount_type'] === 'flat' ? '$' : ''; ?><?php echo $coupon['discount_value']; ?><?php echo $coupon['discount_type'] === 'percentage' ? '%' : ''; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?></span>
                                                <div class="usage-progress">
                                                    <div class="usage-progress-bar" style="width: <?php echo min(($coupon['used_count'] / $coupon['usage_limit']) * 100, 100); ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $coupon['minimum_amount'] > 0 ? '$' . number_format($coupon['minimum_amount'], 2) : 'No minimum'; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($coupon['expiry_date'])): ?>
                                                <?php 
                                                $expiry = new DateTime($coupon['expiry_date']);
                                                $today = new DateTime();
                                                echo $expiry < $today ? '<span class="text-danger">' . $expiry->format('M j, Y') . '</span>' : $expiry->format('M j, Y');
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $coupon['current_status']; ?>">
                                                <?php echo ucfirst($coupon['current_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <button class="btn-icon btn-edit" onclick="editCoupon(<?php echo $coupon['id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" onclick="toggleStatus(<?php echo $coupon['id']; ?>, '<?php echo $coupon['status']; ?>')" title="Toggle Status">
                                                    <i class="fas fa-<?php echo $coupon['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" onclick="deleteCoupon(<?php echo $coupon['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Coupon Modal -->
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="couponForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="couponId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="couponCode" class="form-label">Coupon Code *</label>
                                <input type="text" class="form-control" id="couponCode" name="code" required 
                                       placeholder="e.g., SAVE20" style="text-transform: uppercase;">
                                <div class="form-text">Enter a unique coupon code</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discountType" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discountType" name="discount_type" required>
                                    <option value="flat">Flat Amount ($)</option>
                                    <option value="percentage">Percentage (%)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="discountValue" class="form-label">Discount Value *</label>
                                <input type="number" class="form-control" id="discountValue" name="discount_value" 
                                       step="0.01" min="0" required placeholder="0.00">
                                <div class="form-text">Amount or percentage to discount</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="minimumAmount" class="form-label">Minimum Order Amount</label>
                                <input type="number" class="form-control" id="minimumAmount" name="minimum_amount" 
                                       step="0.01" min="0" placeholder="0.00">
                                <div class="form-text">Minimum amount required to use coupon</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="usageLimit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usageLimit" name="usage_limit" 
                                       min="1" value="100">
                                <div class="form-text">Maximum times this coupon can be used</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiryDate" name="expiry_date">
                                <div class="form-text">Leave empty for no expiry</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Optional description for this coupon"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Coupon
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit coupon
        function editCoupon(id) {
            window.location.href = '?edit=' + id;
        }

        // Toggle coupon status
        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Delete coupon
        function deleteCoupon(id) {
            if (confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }

        // Handle edit mode
        <?php if ($editCoupon): ?>
            document.getElementById('modalTitle').textContent = 'Edit Coupon';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('couponId').value = '<?php echo $editCoupon['id']; ?>';
            document.getElementById('couponCode').value = '<?php echo htmlspecialchars($editCoupon['code']); ?>';
            document.getElementById('discountType').value = '<?php echo $editCoupon['discount_type']; ?>';
            document.getElementById('discountValue').value = '<?php echo $editCoupon['discount_value']; ?>';
            document.getElementById('minimumAmount').value = '<?php echo $editCoupon['minimum_amount']; ?>';
            document.getElementById('usageLimit').value = '<?php echo $editCoupon['usage_limit']; ?>';
            document.getElementById('expiryDate').value = '<?php echo $editCoupon['expiry_date']; ?>';
            document.getElementById('description').value = '<?php echo htmlspecialchars($editCoupon['description']); ?>';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('couponModal'));
            modal.show();
        <?php endif; ?>

        // Auto-format coupon code to uppercase
        document.getElementById('couponCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>
