<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

$auth = new AdminAuth($pdo);
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit;
}

// Handle block/unblock
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['action'] === 'block' ? 'blocked' : 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: users.php");
    exit;
}

// Stats for user page
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$inactive_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
$new_users_30d = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

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
                <a href="index.php" class="list-group-item"><i class="fas fa-th-large me-3"></i>Dashboard</a>
                <a href="products.php" class="list-group-item"><i class="fas fa-box me-3"></i>Products</a>
                <a href="categories.php" class="list-group-item"><i class="fas fa-tags me-3"></i>Categories</a>
                <a href="orders.php" class="list-group-item"><i class="fas fa-shopping-cart me-3"></i>Orders</a>
                <a href="users.php" class="list-group-item active"><i class="fas fa-users me-3"></i>Users</a>
                <a href="coupons.php" class="list-group-item"><i class="fas fa-ticket-alt me-3"></i>Coupons</a>
                <a href="reports.php" class="list-group-item"><i class="fas fa-chart-bar me-3"></i>Reports</a>
                <a href="settings.php" class="list-group-item"><i class="fas fa-cog me-3"></i>Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-white mb-0">Users Management</h2>
                <div class="d-flex gap-3">
                    <button class="btn btn-action shadow-sm" style="background: var(--admin-card); color: white;">
                        <i class="fas fa-download"></i> EXPORT
                    </button>
                </div>
            </div>

            <!-- User Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="admin-card stat-card primary p-4">
                        <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Total Users</h6>
                        <h3 class="fw-bold mb-0"><?php echo $total_users; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card success p-4">
                        <div class="stat-icon text-success"><i class="fas fa-user-check"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Active Users</h6>
                        <h3 class="fw-bold mb-0"><?php echo $active_users; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card warning p-4">
                        <div class="stat-icon text-warning"><i class="fas fa-user-times"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Inactive Users</h6>
                        <h3 class="fw-bold mb-0"><?php echo $inactive_users; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="admin-card stat-card info p-4">
                        <div class="stat-icon text-info"><i class="fas fa-user-plus"></i></div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">New Users (30D)</h6>
                        <h3 class="fw-bold mb-0"><?php echo $new_users_30d; ?></h3>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Users List</h5>
                    <span class="badge rounded-pill bg-info px-3 py-2"><?php echo count($users); ?> TOTAL USERS</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th class="px-4">ID</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th class="text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td class="px-4"><?php echo $u['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>N/A</td>
                                    <td><span class="badge bg-secondary rounded-pill">0 ORDERS</span></td>
                                    <td class="fw-bold">₹0.00</td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $u['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo strtoupper($u['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td class="text-end px-4">
                                        <div class="btn-group shadow-0">
                                            <button class="btn btn-sm btn-link text-info"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-link text-primary"><i class="fas fa-edit"></i></button>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <a href="users.php?action=block&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-link text-warning" onclick="return confirm('Block this user?')"><i class="fas fa-ban"></i></a>
                                            <?php else: ?>
                                                <a href="users.php?action=unblock&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-link text-success"><i class="fas fa-check-circle"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
