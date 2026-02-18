<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to continue']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    switch ($action) {
        case 'add':
            if ($productId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
                exit;
            }
            
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo json_encode(['status' => 'error', 'message' => 'Product not found or unavailable']);
                exit;
            }
            
            // Check if product is in stock
            if ($product['stock'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Product is out of stock']);
                exit;
            }
            
            // Check if item already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update quantity if not exceeding stock
                $newQuantity = $existingItem['quantity'] + 1;
                if ($newQuantity > $product['stock']) {
                    echo json_encode(['status' => 'error', 'message' => 'Cannot add more items than available stock']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$newQuantity, $userId, $productId]);
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
                $stmt->execute([$userId, $productId]);
            }
            
            // Get cart count
            $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $cartCount = $stmt->fetchColumn();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Product added to cart',
                'cart_count' => $cartCount ?: 0
            ]);
            break;
            
        case 'update':
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            if ($productId <= 0 || $quantity <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
                exit;
            }
            
            // Check product stock
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $quantity > $product['stock']) {
                echo json_encode(['status' => 'error', 'message' => 'Quantity exceeds available stock']);
                exit;
            }
            
            // Update cart item
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
            }
            break;
            
        case 'remove':
            if ($productId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                // Get updated cart count
                $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                $cartCount = $stmt->fetchColumn();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Item removed from cart',
                    'cart_count' => $cartCount ?: 0
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
            }
            break;
            
        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode(['status' => 'success', 'message' => 'Cart cleared']);
            break;
            
        case 'get':
            $stmt = $pdo->prepare("
                SELECT c.*, p.name, p.price, p.image, p.stock, p.status as product_status
                FROM cart c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $subtotal = 0;
            foreach ($cartItems as $item) {
                if ($item['product_status'] === 'active' && $item['stock'] >= $item['quantity']) {
                    $subtotal += $item['price'] * $item['quantity'];
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'items' => $cartItems,
                'subtotal' => $subtotal,
                'total_items' => array_sum(array_column($cartItems, 'quantity'))
            ]);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
