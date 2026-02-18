<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get order ID
$orderId = $_GET['id'] ?? '';
if (empty($orderId) || !is_numeric($orderId)) {
    $_SESSION['error'] = 'Invalid order ID';
    header('Location: orders.php');
    exit();
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = 'Order not found';
        header('Location: orders.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching order: ' . $e->getMessage();
    header('Location: orders.php');
    exit();
}

// Get order items
try {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image as product_image, c.name as category_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orderItems = [];
    $_SESSION['error'] = 'Error fetching order items: ' . $e->getMessage();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        
        $_SESSION['success'] = 'Order status updated successfully!';
        header("Location: order-details.php?id=$orderId");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating order status: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - YBT Digital Admin</title>
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
        
        .detail-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: #fff;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
        }
        
        .status-pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        
        .status-processing {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .status-completed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .product-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
        }
        
        .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            color: #fff;
        }
        
        .form-select option {
            background: #1a1a2e;
            color: #fff;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
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
        
        .total-section {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            padding: 1rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .total-row.final {
            font-weight: bold;
            color: #fff;
            font-size: 1.2rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
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
                        <h2 class="text-white mb-2">Order Details</h2>
                        <p class="text-muted mb-0">View and manage order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Orders
                        </a>
                    </div>
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

            <div class="row">
                <!-- Order Information -->
                <div class="col-md-6">
                    <div class="detail-card">
                        <h5 class="text-white mb-3">Order Information</h5>
                        <div class="mb-3">
                            <div class="detail-label">Order ID</div>
                            <div class="detail-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('F j, Y, g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="col-md-6">
                    <div class="detail-card">
                        <h5 class="text-white mb-3">Customer Information</h5>
                        <div class="mb-3">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['user_phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Shipping Address</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="detail-card">
                <h5 class="text-white mb-3">Update Order Status</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <select class="form-select" name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Items -->
            <div class="detail-card">
                <h5 class="text-white mb-3">Order Items</h5>
                <?php if (empty($orderItems)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-box fa-3x mb-3"></i>
                        <p>No items found in this order.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="product-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php if ($item['product_image']): ?>
                                        <img src="../<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                    <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center bg-secondary">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-white mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Unknown Category'); ?></small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="text-white">Qty: <?php echo $item['quantity']; ?></div>
                                    <small class="text-muted">$<?php echo number_format($item['price'], 2); ?> each</small>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="text-white fw-bold">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Total Summary -->
                    <div class="total-section mt-4">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($order['subtotal'] ?? 0, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>$<?php echo number_format($order['shipping_cost'] ?? 0, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($order['tax'] ?? 0, 2); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="total-row">
                                <span>Discount:</span>
                                <span>-$<?php echo number_format($order['discount_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="total-row final">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
