<?php
require_once 'config/config.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

$productObj = new Product($pdo);
$categoryObj = new Category($pdo);

$categories = $categoryObj->getAllCategories();

// Get filters from URL
$cat_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Fetch products based on filters (This would normally be a more complex query in the Product class)
$products = $productObj->getAllProducts('active');

include 'includes/header.php';
?>

<!-- Premium Category Header -->
<section class="hero-section py-5 mb-5">
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeInDown">
            <ol class="breadcrumb glass p-3 px-4 rounded-pill d-inline-flex mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white fw-bold" aria-current="page">Products</li>
            </ol>
        </nav>
        
        <div class="row align-items-center">
            <div class="col-lg-8 animate__animated animate__fadeInLeft">
                <h1 class="text-white mb-2"><?php echo $cat_filter ? $categoryObj->getCategory($cat_filter)['name'] : 'All Collection'; ?></h1>
                <p class="text-white-50 mb-0">Discover our curated selection of high-quality digital assets and tools.</p>
            </div>
            <div class="col-lg-4 text-lg-end animate__animated animate__fadeInRight">
                <div class="glass p-3 rounded-4 d-inline-block">
                    <span class="text-white h4 fw-bold mb-0 d-block"><?php echo count($products); ?></span>
                    <span class="text-white-50 small text-uppercase">Items Available</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container pb-5">

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="product-card p-4 sticky-top border-0 mb-4" style="top: 100px;">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="fas fa-sliders-h text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Browse Filters</h5>
                </div>

                <form action="products.php" method="GET">
                    <!-- Categories -->
                    <div class="mb-4">
                        <label class="fw-bold mb-3 d-block small text-muted text-uppercase">Categories</label>
                        <div class="filter-options">
                            <div class="form-check mb-2 p-0">
                                <input class="btn-check" type="radio" name="category" id="allCat" value="" <?php echo $cat_filter == '' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-light text-dark border-0 w-100 text-start py-2 px-3 rounded-3 d-flex justify-content-between align-items-center" for="allCat">
                                    <span><i class="fas fa-th-large me-2"></i> All Categories</span>
                                    <i class="fas fa-chevron-right small opacity-50"></i>
                                </label>
                            </div>
                            <?php foreach($categories as $cat): ?>
                            <div class="form-check mb-2 p-0">
                                <input class="btn-check" type="radio" name="category" id="cat<?php echo $cat['id']; ?>" value="<?php echo $cat['id']; ?>" <?php echo $cat_filter == $cat['id'] ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-light text-dark border-0 w-100 text-start py-2 px-3 rounded-3 d-flex justify-content-between align-items-center" for="cat<?php echo $cat['id']; ?>">
                                    <span><i class="<?php echo $cat['icon'] ?? 'fas fa-folder'; ?> me-2"></i> <?php echo $cat['name']; ?></span>
                                    <i class="fas fa-chevron-right small opacity-50"></i>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="fw-bold mb-3 d-block small text-muted text-uppercase">Price Range (₹)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="pt-2">
                        <button type="submit" class="btn btn-modern btn-primary w-100 mb-3 shadow-sm">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                        <a href="products.php" class="btn btn-outline-danger w-100 btn-modern py-2">
                            <i class="fas fa-undo me-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
                <div class="d-none d-md-block">
                    <p class="text-muted mb-0">Showing <strong><?php echo count($products); ?></strong> premium products in this collection</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted text-nowrap">Sort by:</span>
                    <select class="form-select border-0 shadow-sm rounded-3 py-2 px-3 bg-white" style="min-width: 180px;">
                        <option value="newest">Latest Assets</option>
                        <option value="oldest">Oldest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <!-- No Products Found -->
            <div class="text-center py-5 my-5">
                <div class="mb-4">
                    <i class="fas fa-search fa-4x text-muted opacity-25"></i>
                </div>
                <h3 class="fw-bold">No products found</h3>
                <p class="text-muted mb-4">Try adjusting your filters or search terms.</p>
                <a href="products.php" class="btn btn-modern btn-primary px-5" style="background: var(--primary-gradient); border: none;">
                    VIEW ALL PRODUCTS
                </a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach($products as $product): ?>
                <div class="col-md-4">
                    <div class="product-card h-100">
                        <div class="product-img-wrapper">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></span>
                            <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/600x400'; ?>" class="product-img" alt="<?php echo htmlspecialchars($product['title']); ?>" />
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php echo renderStars($product['avg_rating']); ?>
                                <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                            </div>
                            <h5 class="card-title fw-bold mb-3">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($product['title'] ?: $product['name'] ?: 'Untitled Product'); ?>
                                </a>
                            </h5>
                            <p class="text-muted small mb-4"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <div class="price-container mb-0">
                                    <span class="current-price">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if($product['old_price']): ?>
                                        <span class="old-price">₹<?php echo number_format($product['old_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-cart-small" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>

                            <div class="card-stats">
                                <div class="stat-item">
                                    <i class="fas fa-download"></i> <?php echo number_format($product['downloads']); ?> downloads
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-eye"></i> <?php echo number_format($product['views']); ?> views
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
