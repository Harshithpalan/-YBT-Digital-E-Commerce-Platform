<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

// Get user ID
$userId = $_SESSION['user_id'];

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image, p.stock, p.status as product_status
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cartItems = [];
    $_SESSION['error'] = 'Error fetching cart items: ' . $e->getMessage();
}

// Check if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
$shipping = 10; // Fixed shipping cost
$taxRate = 0.08; // 8% tax
$discountAmount = 0;
$appliedCoupon = null;

foreach ($cartItems as $item) {
    if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// Handle coupon application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    
    if (!empty($couponCode)) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND status = 'active' 
                AND (expiry_date IS NULL OR expiry_date >= CURDATE()) 
                AND used_count < usage_limit
            ");
            $stmt->execute([$couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($coupon) {
                // Check minimum order amount
                if ($coupon['minimum_amount'] > 0 && $subtotal < $coupon['minimum_amount']) {
                    $_SESSION['error'] = 'Minimum order amount of $' . number_format($coupon['minimum_amount'], 2) . ' required for this coupon.';
                } else {
                    // Calculate discount
                    if ($coupon['discount_type'] === 'flat') {
                        $discountAmount = $coupon['discount_value'];
                    } else {
                        $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
                        // Cap discount at subtotal amount
                        $discountAmount = min($discountAmount, $subtotal);
                    }
                    
                    $appliedCoupon = $coupon;
                    $_SESSION['success'] = 'Coupon applied successfully! You saved $' . number_format($discountAmount, 2);
                }
            } else {
                $_SESSION['error'] = 'Invalid or expired coupon code.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error applying coupon.';
        }
    } else {
        $_SESSION['error'] = 'Please enter a coupon code.';
    }
}

// Remove coupon
if (isset($_GET['remove_coupon'])) {
    $appliedCoupon = null;
    $discountAmount = 0;
    $_SESSION['success'] = 'Coupon removed.';
    header('Location: checkout.php');
    exit();
}

$tax = ($subtotal - $discountAmount) * $taxRate;
$total = $subtotal + $shipping + $tax - $discountAmount;

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = [];
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'online';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($address)) {
        $_SESSION['error'] = 'Please fill in all required fields';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, subtotal, shipping_cost, tax, discount_amount, total_amount, payment_method, payment_status, status, shipping_address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())
            ");
            $stmt->execute([$userId, $subtotal, $shipping, $tax, $discountAmount, $total, $payment_method, $address]);
            $orderId = $pdo->lastInsertId();
            
            // Update coupon usage if coupon was applied
            if ($appliedCoupon) {
                $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$appliedCoupon['id']]);
            }
            
            // Add order items
            foreach ($cartItems as $item) {
                if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                    
                    // Update product stock
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success'] = 'Order placed successfully! Your order ID is #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
            header('Location: order-details.php?id=' . $orderId);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error placing order: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .checkout-container {
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
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .form-label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
        
        .cart-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(99, 102, 241, 0.1);
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .cart-item-price {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .cart-item-quantity {
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            border: 1px solid #ddd;
            font-size: 0.85rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            padding-top: 0.75rem;
            border-top: 2px solid #eee;
            color: var(--primary-color);
        }
        
        .summary-row.discount {
            color: #22c55e;
        }
        
        .coupon-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .applied-coupon {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            padding: 0.75rem;
            border-radius: 8px;
        }
        
        .coupon-code-applied {
            font-weight: bold;
            color: #22c55e;
            font-family: 'Courier New', monospace;
        }
        
        .coupon-form .input-group {
            margin-bottom: 0;
        }
        
        .coupon-form input {
            text-transform: uppercase;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-method {
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: var(--primary-color);
        }
        
        .payment-method.selected {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .payment-method i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .btn-place-order {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-place-order:hover {
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
        
        .out-of-stock {
            opacity: 0.6;
            background: rgba(239, 68, 68, 0.05);
            border-color: rgba(239, 68, 68, 0.1);
        }
        
        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
            }
        }
        
        @media (max-width: 576px) {
            .checkout-container {
                padding: 1rem;
            }
            
            .checkout-form, .order-summary {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        <div class="page-header">
            <h1>Checkout</h1>
            <p>Complete your order details</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form method="POST">
                    <h3 class="section-title">Shipping Information</h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="online">Online Payment</option>
                                <option value="cod">Cash on Delivery</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="address" class="form-label">Shipping Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required 
                                  placeholder="Enter your complete shipping address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-place-order">
                        <i class="fas fa-lock me-2"></i> Place Order
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="section-title">Order Summary</h3>
                
                <?php foreach ($cartItems as $item): ?>
                    <?php if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']): ?>
                        <div class="cart-item">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                            <?php else: ?>
                                <div class="cart-item-image d-flex align-items-center justify-content-center bg-secondary">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cart-item-details">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            
                            <div class="cart-item-quantity">
                                Qty: <?php echo $item['quantity']; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cart-item out-of-stock">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                            <?php else: ?>
                                <div class="cart-item-image d-flex align-items-center justify-content-center bg-secondary">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cart-item-details">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-price text-danger">
                                    <?php if ($item['product_status'] !== 'active'): ?>
                                        Product Unavailable
                                    <?php else: ?>
                                        Out of Stock
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="cart-item-quantity">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Coupon Section -->
                <div class="coupon-section mb-4">
                    <?php if ($appliedCoupon): ?>
                        <div class="applied-coupon">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="coupon-code-applied">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    <?php echo htmlspecialchars($appliedCoupon['code']); ?>
                                </span>
                                <a href="checkout.php?remove_coupon=1" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times"></i> Remove
                                </a>
                            </div>
                            <small class="text-success">
                                Discount applied: -$<?php echo number_format($discountAmount, 2); ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="coupon-form">
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Enter coupon code" style="text-transform: uppercase;">
                                <button type="submit" name="apply_coupon" class="btn btn-outline-primary">
                                    <i class="fas fa-tag me-1"></i> Apply
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($discountAmount > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount:</span>
                            <span class="text-success">-$<?php echo number_format($discountAmount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%):</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                
                const paymentType = this.dataset.payment;
                document.getElementById('payment_method').value = paymentType;
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            
            if (!name || !email || !address) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    </script>
</body>
</html>
