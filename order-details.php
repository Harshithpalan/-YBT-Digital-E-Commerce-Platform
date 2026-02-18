<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
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

// Get user ID
$userId = $_SESSION['user_id'];

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .order-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            margin-bottom: 0.5rem;
        }
        
        .detail-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--text-dark);
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
        
        .product-item {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(99, 102, 241, 0.1);
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .product-category {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .product-quantity {
            background: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            border: 1px solid #ddd;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .total-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-top: 2rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 1.3rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: var(--text-dark);
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
            color: var(--text-dark);
        }
        
        .btn-cancel {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
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
            .product-item {
                flex-direction: column;
                text-align: center;
            }
            
            .product-image {
                margin: 0 auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="order-container">
        <div class="page-header">
            <h1>Order Details</h1>
            <p>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
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

        <!-- Order Information -->
        <div class="detail-section">
            <h3 class="section-title">Order Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Order Date</span>
                    <span class="detail-value"><?php echo date('F j, Y, g:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value"><?php echo ucfirst($order['payment_method'] ?? 'Online'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Payment Status</span>
                    <span class="detail-value"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="detail-section">
            <h3 class="section-title">Shipping Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['user_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['user_email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['user_phone'] ?? 'Not provided'); ?></span>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <span class="detail-label">Shipping Address</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'Not provided')); ?></span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="detail-section">
            <h3 class="section-title">Order Items</h3>
            <?php if (empty($orderItems)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-box fa-3x mb-3"></i>
                    <p>No items found in this order.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orderItems as $item): ?>
                    <div class="product-item">
                        <?php if ($item['product_image']): ?>
                            <img src="<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image d-flex align-items-center justify-content-center bg-secondary">
                                <i class="fas fa-image fa-2x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="product-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Unknown Category'); ?></div>
                            <div class="product-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                        </div>
                        
                        <div class="product-quantity">
                            Qty: <?php echo $item['quantity']; ?>
                        </div>
                        
                        <div class="product-price" style="align-self: center;">
                            <strong>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Order Total -->
        <div class="total-section">
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
                <span>Total Amount:</span>
                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Orders
            </a>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
            </a>
            <?php if ($order['status'] === 'pending'): ?>
                <a href="orders.php?cancel=<?php echo $order['id']; ?>" 
                   class="btn btn-cancel" 
                   onclick="return confirm('Are you sure you want to cancel this order?')">
                    <i class="fas fa-times me-2"></i> Cancel Order
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
