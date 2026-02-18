<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user ID
$userId = $_SESSION['user_id'];

// Handle order cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $orderId = $_GET['cancel'];
    
    try {
        // Check if order belongs to user and is in pending status
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$orderId]);
            $_SESSION['success'] = 'Order cancelled successfully!';
        } else {
            $_SESSION['error'] = 'Order not found or cannot be cancelled';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error cancelling order: ' . $e->getMessage();
    }
    
    header('Location: orders.php');
    exit();
}

// Get user's orders
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            COUNT(oi.id) as total_items,
            SUM(oi.quantity * oi.price) as total_amount
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $_SESSION['error'] = 'Error fetching orders: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }
        
        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .order-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(251, 191, 36, 0.2);
        }
        
        .status-processing {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .status-completed {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }
        
        .order-info {
            display: flex;
            gap: 2rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-view:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .btn-cancel {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .empty-state p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .btn-shop {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            .order-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="orders-container">
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track and manage your orders</p>
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

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="products.php" class="btn btn-shop">
                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div class="order-date"><?php echo date('F j, Y, g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo $order['status']; ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-info">
                            <div class="info-item">
                                <span class="info-label">Items</span>
                                <span class="info-value"><?php echo $order['total_items']; ?> items</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Amount</span>
                                <span class="info-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Payment Method</span>
                                <span class="info-value"><?php echo ucfirst($order['payment_method'] ?? 'Online'); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="orders.php?cancel=<?php echo $order['id']; ?>" 
                                   class="btn btn-cancel" 
                                   onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
