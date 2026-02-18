<?php
require_once 'config/config.php';
require_once 'classes/Product.php';

$id = $_GET['id'] ?? 0;
$productObj = new Product($pdo);
$product = $productObj->getProductById($id);

if (!$product) {
    header("Location: index.php");
    exit;
}

include 'includes/header.php';
?>
<!-- Premium Product Header / Breadcrumbs -->
<section class="hero-section py-4 mb-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="animate__animated animate__fadeInDown">
            <ol class="breadcrumb glass p-2 px-4 rounded-pill d-inline-flex mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php" class="text-white-50 text-decoration-none">Products</a></li>
                <li class="breadcrumb-item active text-white fw-bold" aria-current="page"><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></li>
            </ol>
        </nav>
    </div>
</section>

<div class="container pb-5">
    <div class="row g-5">
        <!-- Product Image Section -->
        <div class="col-lg-7 animate__animated animate__fadeInLeft">
            <div class="product-detail-img-wrapper p-3 bg-white rounded-5 shadow-sm border border-light overflow-hidden position-relative">
                <div class="category-badge top-4 start-4"><?php echo htmlspecialchars($product['category_name'] ?: 'Asset'); ?></div>
                <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/1200x800'; ?>" class="img-fluid rounded-4 w-100" alt="<?php echo htmlspecialchars($product['title']); ?>" style="object-fit: cover;" />
            </div>
            
            <!-- Trust Features -->
            <div class="row g-3 mt-4">
                <div class="col-md-4">
                    <div class="glass p-3 rounded-4 text-center border-0">
                        <i class="fas fa-shield-alt text-primary mb-2 fa-lg"></i>
                        <h6 class="fw-bold mb-1 small">Secure Payment</h6>
                        <p class="text-muted smaller mb-0">100% encrypted transactions</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass p-3 rounded-4 text-center border-0">
                        <i class="fas fa-bolt text-warning mb-2 fa-lg"></i>
                        <h6 class="fw-bold mb-1 small">Instant Access</h6>
                        <p class="text-muted smaller mb-0">Download immediately</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass p-3 rounded-4 text-center border-0">
                        <i class="fas fa-headset text-success mb-2 fa-lg"></i>
                        <h6 class="fw-bold mb-1 small">Expert Support</h6>
                        <p class="text-muted smaller mb-0">24/7 dedicated help</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Info Section -->
        <div class="col-lg-5 animate__animated animate__fadeInRight">
            <div class="product-info-card">
                <h1 class="display-6 fw-bold mb-3 text-dark"><?php echo htmlspecialchars($product['title'] ?: $product['name'] ?: 'Untitled Product'); ?></h1>
                
                <div class="d-flex align-items-center gap-4 mb-4">
                    <div class="price-badge bg-primary bg-opacity-10 px-4 py-2 rounded-pill">
                        <span class="h2 fw-bold text-primary mb-0">₹<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <?php if(isset($product['old_price']) && $product['old_price']): ?>
                        <span class="text-muted text-decoration-line-through h4 mb-0">₹<?php echo number_format($product['old_price'], 2); ?></span>
                    <?php endif; ?>
                </div>

                <div class="product-stats-grid d-flex flex-wrap gap-3 mb-4">
                    <div class="stat-pill border rounded-pill px-3 py-2 d-flex align-items-center bg-light">
                        <i class="fas fa-star text-warning me-2"></i>
                        <span class="fw-bold me-1"><?php echo number_format($product['avg_rating'] ?? 5, 1); ?></span>
                        <span class="text-muted small">(<?php echo $product['review_count'] ?? 12; ?> reviews)</span>
                    </div>
                    <div class="stat-pill border rounded-pill px-3 py-2 d-flex align-items-center bg-light">
                        <i class="fas fa-download text-primary me-2"></i>
                        <span class="text-muted small"><strong><?php echo number_format($product['downloads'] ?? 0); ?></strong> downloads</span>
                    </div>
                </div>

                <div class="description-box mb-5">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Product Description</h5>
                    <p class="text-muted lh-lg"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="action-buttons d-grid gap-3">
                    <button class="btn btn-modern btn-primary btn-lg py-3 shadow-lg" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-cart-plus me-2"></i> ADD TO SHOPPING CART
                    </button>
                    <button class="btn btn-outline-secondary btn-modern py-3">
                        <i class="far fa-heart me-2"></i> ADD TO WISHLIST
                    </button>
                </div>

                <!-- Product Meta -->
                <div class="mt-5 pt-4 border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Last Updated:</span>
                        <span class="fw-bold small"><?php echo date('M d, Y', strtotime($product['updated_at'] ?? 'now')); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Compatibility:</span>
                        <span class="fw-bold small">Latest Browsers & Frameworks</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">File Format:</span>
                        <span class="fw-bold small">ZIP, Source Code included</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
