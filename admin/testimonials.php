<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle testimonial operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $testimonial = trim($_POST['testimonial'] ?? '');
            $rating = (int)($_POST['rating'] ?? 5);
            $company = trim($_POST['company'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            if (!empty($name) && !empty($testimonial)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO testimonials (name, email, testimonial, rating, company, position, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $email, $testimonial, $rating, $company, $position, $status]);
                    $_SESSION['success'] = 'Testimonial created successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating testimonial: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Name and testimonial are required!';
            }
            header('Location: testimonials.php');
            exit();
            break;
            
        case 'edit':
            $id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $testimonial = trim($_POST['testimonial'] ?? '');
            $rating = (int)($_POST['rating'] ?? 5);
            $company = trim($_POST['company'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            if (!empty($id) && !empty($name) && !empty($testimonial)) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE testimonials SET 
                        name = ?, email = ?, testimonial = ?, rating = ?, company = ?, position = ?, status = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $testimonial, $rating, $company, $position, $status, $id]);
                    $_SESSION['success'] = 'Testimonial updated successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating testimonial: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'All required fields are needed!';
            }
            header('Location: testimonials.php');
            exit();
            break;
            
        case 'delete':
            $id = $_GET['id'] ?? '';
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = 'Testimonial deleted successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error deleting testimonial: ' . $e->getMessage();
                }
            }
            header('Location: testimonials.php');
            exit();
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("UPDATE testimonials SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['success'] = 'Testimonial status updated!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating testimonial status: ' . $e->getMessage();
                }
            }
            header('Location: testimonials.php');
            exit();
            break;
    }
}

// Get all testimonials
try {
    $stmt = $pdo->query("
        SELECT * FROM testimonials 
        ORDER BY created_at DESC
    ");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
    $_SESSION['error'] = 'Error fetching testimonials: ' . $e->getMessage();
}

// Get testimonial for editing
$editTestimonial = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching testimonial: ' . $e->getMessage();
    }
}

// Get testimonial statistics
try {
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'inactive'")->fetchColumn(),
        'avg_rating' => $pdo->query("SELECT AVG(rating) FROM testimonials")->fetchColumn() ?: 0,
        'five_star' => $pdo->query("SELECT COUNT(*) FROM testimonials WHERE rating = 5")->fetchColumn(),
        'four_star' => $pdo->query("SELECT COUNT(*) FROM testimonials WHERE rating = 4")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'avg_rating' => 0, 'five_star' => 0, 'four_star' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - YBT Digital Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .testimonials-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .testimonial-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6366f1;
            transition: all 0.3s ease;
        }
        
        .testimonial-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .testimonial-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: #fff;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .testimonial-position {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .testimonial-content {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            line-height: 1.6;
            font-style: italic;
        }
        
        .testimonial-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .rating {
            display: flex;
            gap: 0.25rem;
        }
        
        .rating i {
            color: #f59e0b;
            font-size: 1rem;
        }
        
        .rating .empty {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            margin: 0 2px;
        }
        
        .btn-edit {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .btn-toggle {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .btn-toggle:hover {
            background: rgba(107, 114, 128, 0.3);
            color: #6b7280;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            color: #fff;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .rating-input {
            display: flex;
            gap: 0.5rem;
            font-size: 1.5rem;
        }
        
        .rating-input i {
            cursor: pointer;
            color: rgba(255, 255, 255, 0.3);
            transition: color 0.3s ease;
        }
        
        .rating-input i:hover,
        .rating-input i.active {
            color: #f59e0b;
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
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-title, .form-label {
            color: #fff;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                <a href="testimonials.php" class="nav-link active">
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
                        <h2 class="text-white mb-2">Testimonials Management</h2>
                        <p class="text-muted mb-0">Manage customer testimonials and reviews</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testimonialModal">
                        <i class="fas fa-plus me-2"></i> Add Testimonial
                    </button>
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

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Testimonials</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['five_star']; ?></div>
                    <div class="stat-label">5 Star Reviews</div>
                </div>
            </div>

            <!-- Testimonials List -->
            <div class="testimonials-container">
                <?php if (empty($testimonials)): ?>
                    <div class="empty-state">
                        <i class="fas fa-quote-left"></i>
                        <h4>No Testimonials Found</h4>
                        <p>Start by adding your first customer testimonial.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="testimonial-item">
                            <div class="testimonial-header">
                                <div>
                                    <div class="testimonial-author"><?php echo htmlspecialchars($testimonial['name']); ?></div>
                                    <?php if (!empty($testimonial['position']) || !empty($testimonial['company'])): ?>
                                        <div class="testimonial-position">
                                            <?php echo htmlspecialchars($testimonial['position'] ?? ''); ?>
                                            <?php if (!empty($testimonial['position']) && !empty($testimonial['company'])): ?>, <?php endif; ?>
                                            <?php echo htmlspecialchars($testimonial['company'] ?? ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? '' : 'empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="testimonial-content">"<?php echo nl2br(htmlspecialchars($testimonial['testimonial'])); ?>"</div>
                            <div class="testimonial-meta">
                                <span class="status-badge status-<?php echo $testimonial['status']; ?>">
                                    <?php echo ucfirst($testimonial['status']); ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?>
                                </small>
                                <?php if (!empty($testimonial['email'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($testimonial['email']); ?>
                                    </small>
                                <?php endif; ?>
                                <div class="ms-auto">
                                    <button class="btn-icon btn-edit" onclick="editTestimonial(<?php echo $testimonial['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-toggle" onclick="toggleStatus(<?php echo $testimonial['id']; ?>, '<?php echo $testimonial['status']; ?>')" title="Toggle Status">
                                        <i class="fas fa-<?php echo $testimonial['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Testimonial Modal -->
    <div class="modal fade" id="testimonialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Testimonial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="testimonialForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="testimonialId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       placeholder="Customer name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="customer@example.com">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       placeholder="Job title">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company" name="company" 
                                       placeholder="Company name">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="testimonial" class="form-label">Testimonial *</label>
                            <textarea class="form-control" id="testimonial" name="testimonial" rows="4" required 
                                      placeholder="Customer testimonial text"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rating *</label>
                                <div class="rating-input" id="ratingInput">
                                    <i class="fas fa-star" data-rating="1"></i>
                                    <i class="fas fa-star" data-rating="2"></i>
                                    <i class="fas fa-star" data-rating="3"></i>
                                    <i class="fas fa-star" data-rating="4"></i>
                                    <i class="fas fa-star" data-rating="5"></i>
                                </div>
                                <input type="hidden" name="rating" id="rating" value="5" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Testimonial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rating input functionality
        const ratingStars = document.querySelectorAll('#ratingInput i');
        const ratingInput = document.getElementById('rating');
        
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                updateRatingDisplay(rating);
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                updateRatingDisplay(rating);
            });
        });
        
        document.getElementById('ratingInput').addEventListener('mouseleave', function() {
            updateRatingDisplay(parseInt(ratingInput.value));
        });
        
        function updateRatingDisplay(rating) {
            ratingStars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Edit Testimonial
        function editTestimonial(id) {
            window.location.href = '?edit=' + id;
        }

        // Toggle Testimonial status
        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Delete Testimonial
        function deleteTestimonial(id) {
            if (confirm('Are you sure you want to delete this testimonial? This action cannot be undone.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }

        // Handle edit mode
        <?php if ($editTestimonial): ?>
            document.getElementById('modalTitle').textContent = 'Edit Testimonial';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('testimonialId').value = '<?php echo $editTestimonial['id']; ?>';
            document.getElementById('name').value = '<?php echo htmlspecialchars($editTestimonial['name']); ?>';
            document.getElementById('email').value = '<?php echo htmlspecialchars($editTestimonial['email']); ?>';
            document.getElementById('position').value = '<?php echo htmlspecialchars($editTestimonial['position']); ?>';
            document.getElementById('company').value = '<?php echo htmlspecialchars($editTestimonial['company']); ?>';
            document.getElementById('testimonial').value = '<?php echo htmlspecialchars($editTestimonial['testimonial']); ?>';
            document.getElementById('rating').value = '<?php echo $editTestimonial['rating']; ?>';
            document.getElementById('status').value = '<?php echo $editTestimonial['status']; ?>';
            
            // Set rating display
            updateRatingDisplay(<?php echo $editTestimonial['rating']; ?>);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('testimonialModal'));
            modal.show();
        <?php endif; ?>
        
        // Initialize rating display
        updateRatingDisplay(5);
    </script>
</body>
</html>