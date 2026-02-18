<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=cart.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $productId = $_GET['id'] ?? '';
    
    try {
        switch ($action) {
            case 'remove':
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
                $_SESSION['success'] = 'Item removed from cart';
                break;
                
            case 'update':
                $quantity = $_POST['quantity'] ?? 1;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $userId, $productId]);
                $_SESSION['success'] = 'Cart updated';
                break;
                
            case 'clear':
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                $_SESSION['success'] = 'Cart cleared';
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating cart: ' . $e->getMessage();
    }
    
    header('Location: cart.php');
    exit();
}

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

// Calculate totals
$subtotal = 0;
$shipping = 10; // Fixed shipping cost
$taxRate = 0.08; // 8% tax

foreach ($cartItems as $item) {
    if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

$tax = $subtotal * $taxRate;
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .cart-container {
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
        
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .cart-summary {
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
        
        .cart-item {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.1);
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .cart-item-price {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .cart-item-stock {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .cart-item-controls {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.25rem;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: var(--secondary-color);
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: none;
            font-weight: 500;
        }
        
        .item-total {
            font-weight: bold;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        
        .remove-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remove-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
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
            padding-top: 1rem;
            border-top: 2px solid #eee;
            color: var(--primary-color);
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .btn-continue {
            background: white;
            color: var(--text-dark);
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-continue:hover {
            background: #f8f9fa;
            color: var(--text-dark);
            text-decoration: none;
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        
        .empty-cart h3 {
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .empty-cart p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
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
            .cart-grid {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
        }
        
        @media (max-width: 576px) {
            .cart-container {
                padding: 1rem;
            }
            
            .cart-items, .cart-summary {
                padding: 1.5rem;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-item-image {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="cart-container">
        <div class="page-header">
            <h1>Shopping Cart</h1>
            <p>Review and manage your items</p>
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

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <!-- Cart Items -->
                <div class="cart-items">
                    <h3 class="section-title">Cart Items</h3>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <?php if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']): ?>
                            <div class="cart-item">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                <?php else: ?>
                                    <div class="cart-item-image d-flex align-items-center justify-content-center bg-secondary">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="cart-item-details">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="cart-item-stock">In stock: <?php echo $item['stock']; ?> items</div>
                                </div>
                                
                                <div class="cart-item-controls">
                                    <div class="quantity-control">
                                        <form method="POST" action="cart.php?action=update&id=<?php echo $item['product_id']; ?>" style="display: flex; align-items: center;">
                                            <button type="submit" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>" class="quantity-btn">-</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" readonly>
                                            <button type="submit" name="quantity" value="<?php echo min($item['stock'], $item['quantity'] + 1); ?>" class="quantity-btn">+</button>
                                        </form>
                                    </div>
                                    
                                    <div class="item-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                    
                                    <a href="cart.php?action=remove&id=<?php echo $item['product_id']; ?>" class="remove-btn">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="cart-item out-of-stock">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                <?php else: ?>
                                    <div class="cart-item-image d-flex align-items-center justify-content-center bg-secondary">
                                        <i class="fas fa-image fa-2x text-muted"></i>
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
                                
                                <div class="cart-item-controls">
                                    <a href="cart.php?action=remove&id=<?php echo $item['product_id']; ?>" class="remove-btn">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <div class="mt-4">
                        <a href="products.php" class="btn-continue">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3 class="section-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
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
                    
                    <a href="checkout.php" class="btn btn-checkout">
                        <i class="fas fa-lock me-2"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update quantity with AJAX
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>
