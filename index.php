<?php
require_once 'config/config.php';
require_once 'classes/Product.php';
$productObj = new Product($pdo);
$featuredProducts = $productObj->getFeaturedProducts(6);

include 'includes/header.php';
?>

<!-- Modern Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 hero-content">
                <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3 animate__animated animate__fadeInDown">
                    <i class="fas fa-sparkles me-2"></i>THE FUTURE OF DIGITAL ASSETS
                </div>
                <h1 class="animate__animated animate__fadeInUp">Crafting Your <span class="text-primary">Digital Success</span> Story</h1>
                <p class="animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    Discover a hand-curated collection of elite digital assets. From high-performance web templates to stunning graphics, we provide the tools that turn visions into reality.
                </p>
                <div class="d-flex flex-wrap gap-3 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <a href="products.php" class="btn btn-modern btn-primary px-4 py-3 shadow-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Start Browsing
                    </a>
                    <a href="support.php" class="btn btn-modern glass px-4 py-3 text-white">
                        <i class="fas fa-headset me-2"></i>Get Support
                    </a>
                </div>
                
                <div class="mt-5 d-flex align-items-center gap-4 animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
                    <div class="d-flex -space-x-2">
                        <img class="rounded-pill border border-dark border-2" width="40" src="https://i.pravatar.cc/100?u=1" alt="">
                        <img class="rounded-pill border border-dark border-2 ms-n2" width="40" src="https://i.pravatar.cc/100?u=2" alt="">
                        <img class="rounded-pill border border-dark border-2 ms-n2" width="40" src="https://i.pravatar.cc/100?u=3" alt="">
                    </div>
                    <div class="text-muted small">
                        <span class="text-white fw-bold">1,200+</span> happy customers joined us this week
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="position-relative">
                    <div class="cat-card glass floating p-4 text-center">
                        <div class="cat-icon text-primary mb-3"><i class="fas fa-code fa-2x"></i></div>
                        <h5 class="text-white mb-0">Web Templates</h5>
                    </div>
                    <div class="cat-card glass floating-delayed p-4 text-center position-absolute" style="top: 180px; right: -20px; width: 200px;">
                        <div class="cat-icon text-info mb-3"><i class="fas fa-mobile-alt fa-2x"></i></div>
                        <h5 class="text-white mb-0">Mobile UI</h5>
                    </div>
                    <div class="cat-card glass floating p-4 text-center position-absolute" style="top: 250px; left: -40px; width: 180px;">
                        <div class="cat-icon text-warning mb-3"><i class="fas fa-paint-brush fa-2x"></i></div>
                        <h5 class="text-white mb-0">Graphics</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-4" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bolt fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">Instant Access</h4>
                    <p class="text-muted">Download your purchases immediately after checkout. No waiting periods.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-4" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">Secure Gateway</h4>
                    <p class="text-muted">All transactions are encrypted and processed through industry-leading providers.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="feature-icon bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-4" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-sync fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">Lifetime Updates</h4>
                    <p class="text-muted">Get access to all future updates for your purchased products at no extra cost.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<div class="container my-5 py-5" id="featured">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h6 class="text-primary fw-bold text-uppercase mb-2">Our Collection</h6>
            <h2 class="fw-bold h1">Featured Products</h2>
        </div>
        <a href="products.php" class="btn btn-link text-primary fw-bold p-0">
            View All Collection <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>

    <div class="row g-4">
        <?php foreach($featuredProducts as $product): ?>
        <div class="col-lg-4 col-md-6">
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
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($product['title'] ?: $product['name'] ?: 'Untitled Product'); ?>
                        </a>
                    </h5>
                    <p class="card-text text-muted small mb-4"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                    
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
</div>

<?php include 'includes/footer.php'; ?>
