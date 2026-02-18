<?php
require_once 'config/config.php';

// Get current category
$currentCategory = $_GET['category'] ?? '';

// Get all categories with product counts
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get products for current category
$products = [];
$categoryInfo = null;

if (!empty($currentCategory)) {
    try {
        // Get category info
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$currentCategory]);
        $categoryInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($categoryInfo) {
            // Get products in this category
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name, c.icon as category_icon
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$categoryInfo['id']]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $products = [];
    }
} else {
    // Get all products if no category selected
    try {
        $stmt = $pdo->query("
            SELECT p.*, c.name as category_name, c.icon as category_icon
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 12
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $products = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .categories-container {
            max-width: 1400px;
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
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1);
        }
        
        .category-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .category-count {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .products-section {
            margin-top: 3rem;
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .section-subtitle {
            color: var(--text-muted);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        }
        
        .product-details {
            padding: 1.5rem;
        }
        
        .product-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .product-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-view {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        .btn-cart {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-cart:hover {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-color);
        }
        
        .breadcrumb {
            background: transparent;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item {
            color: var(--text-muted);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        .breadcrumb-item a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
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
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .categories-container {
                padding: 1rem;
            }
            
            .categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="categories-container">
        <div class="page-header">
            <h1>Product Categories</h1>
            <p>Browse our digital products by category</p>
        </div>

        <?php if ($categoryInfo): ?>
            <!-- Breadcrumb for category view -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($categoryInfo['name']); ?></li>
                </ol>
            </nav>

            <!-- Category Header -->
            <div class="text-center mb-4">
                <div class="category-icon d-inline-flex" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="<?php echo htmlspecialchars($categoryInfo['icon']); ?>"></i>
                </div>
                <h2 class="mt-3"><?php echo htmlspecialchars($categoryInfo['name']); ?></h2>
                <p class="text-muted"><?php echo count($products); ?> products available</p>
            </div>

            <a href="categories.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Categories
            </a>
        <?php endif; ?>

        <!-- Categories Grid (only show when not viewing a specific category) -->
        <?php if (!$categoryInfo && !empty($categories)): ?>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="categories.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                        </div>
                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-count"><?php echo $category['product_count']; ?> products</div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Products Section -->
        <?php if (!empty($products)): ?>
            <div class="products-section">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">
                            <?php echo $categoryInfo ? htmlspecialchars($categoryInfo['name']) : 'Featured Products'; ?>
                        </h2>
                        <p class="section-subtitle">
                            <?php echo $categoryInfo ? 'Products in this category' : 'Latest products from all categories'; ?>
                        </p>
                    </div>
                </div>

                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? $product['title']); ?>" class="product-image">
                            <?php else: ?>
                                <div class="product-image d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-details">
                                <?php if ($product['category_name']): ?>
                                    <div class="product-category">
                                        <i class="<?php echo htmlspecialchars($product['category_icon']); ?>"></i>
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name'] ?? $product['title']); ?></h3>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($categoryInfo): ?>
            <!-- Empty state for category with no products -->
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>This category doesn't have any products yet.</p>
                <a href="categories.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Browse Other Categories
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
