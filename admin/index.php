<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

$auth = new AdminAuth($pdo);
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit;
}

include '../includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<body class="admin-body">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 p-0 admin-sidebar d-none d-lg-block">
            <div class="p-4 mb-2">
                <h4 class="fw-bold text-white mb-0">YBT Digital Admin</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item active"><i class="fas fa-th-large me-3"></i>Dashboard</a>
                <a href="products.php" class="list-group-item"><i class="fas fa-box me-3"></i>Products</a>
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
                <h2 class="fw-bold text-white mb-0">Dashboard</h2>
                <div class="d-flex gap-3">
                    <button class="btn btn-action shadow-sm" style="background: var(--admin-card); color: white;">
                        <i class="fas fa-sync-alt"></i> REFRESH
                    </button>
                    <a href="products.php" class="btn btn-action text-white shadow-sm" style="background: var(--admin-primary);">
                        <i class="fas fa-plus"></i> ADD PRODUCT
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="admin-card stat-card primary p-4">
                        <div class="stat-icon text-primary"><i class="fas fa-box"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Products</h6>
                        <h3 class="fw-bold mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card success p-4">
                        <div class="stat-icon text-success"><i class="fas fa-rupee-sign"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Revenue</h6>
                        <h3 class="fw-bold mb-0">₹<?php echo number_format($pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'completed'")->fetchColumn() ?: 0, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card info p-4">
                        <div class="stat-icon text-info"><i class="fas fa-shopping-cart"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Orders</h6>
                        <h3 class="fw-bold mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card warning p-4">
                        <div class="stat-icon text-warning"><i class="fas fa-users"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Users</h6>
                        <h3 class="fw-bold mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="admin-card h-100">
                        <div class="admin-card-header d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-light rounded-pill px-3">VIEW ALL</a>
                        </div>
                        <div class="card-body p-5 text-center">
                            <?php 
                            $recent = $pdo->query("SELECT o.*, u.name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
                            if(empty($recent)): ?>
                                <i class="fas fa-shopping-cart fa-3x text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">No orders yet</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table admin-table text-start">
                                        <thead><tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                                        <tbody>
                                            <?php foreach($recent as $r): ?>
                                            <tr>
                                                <td>#<?php echo $r['id']; ?></td>
                                                <td><?php echo $r['name']; ?></td>
                                                <td class="fw-bold">₹<?php echo number_format($r['total_amount'], 2); ?></td>
                                                <td><span class="badge rounded-pill bg-success"><?php echo ucfirst($r['payment_status']); ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Top Products -->
                <div class="col-lg-4">
                    <div class="admin-card h-100">
                        <div class="admin-card-header">
                            <h5 class="fw-bold mb-0">Top Products</h5>
                        </div>
                        <div class="card-body p-5 text-center">
                            <i class="fas fa-box fa-3x text-muted opacity-25 mb-3"></i>
                            <p class="text-muted">No products yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h5 class="fw-bold mb-4">Quick Actions</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="products.php" class="btn-action w-100 text-white" style="background: #6366f1;">
                        <i class="fas fa-plus"></i> ADD PRODUCT
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="categories.php" class="btn-action w-100 text-white" style="background: #10b981;">
                        <i class="fas fa-folder-plus"></i> ADD CATEGORY
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="coupons.php" class="btn-action w-100 text-white" style="background: #f59e0b;">
                        <i class="fas fa-ticket-alt"></i> CREATE COUPON
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="reports.php" class="btn-action w-100 text-white" style="background: #0ea5e9;">
                        <i class="fas fa-chart-line"></i> VIEW REPORTS
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
