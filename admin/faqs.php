<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle FAQ operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            $category = $_POST['category'] ?? 'general';
            $status = $_POST['status'] ?? 'active';
            $order = (int)($_POST['order'] ?? 0);
            
            if (!empty($question) && !empty($answer)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO faqs (question, answer, category, status, sort_order, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$question, $answer, $category, $status, $order]);
                    $_SESSION['success'] = 'FAQ created successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating FAQ: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'Question and answer are required!';
            }
            header('Location: faqs.php');
            exit();
            break;
            
        case 'edit':
            $id = $_POST['id'] ?? '';
            $question = trim($_POST['question'] ?? '');
            $answer = trim($_POST['answer'] ?? '');
            $category = $_POST['category'] ?? 'general';
            $status = $_POST['status'] ?? 'active';
            $order = (int)($_POST['order'] ?? 0);
            
            if (!empty($id) && !empty($question) && !empty($answer)) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE faqs SET 
                        question = ?, answer = ?, category = ?, status = ?, sort_order = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$question, $answer, $category, $status, $order, $id]);
                    $_SESSION['success'] = 'FAQ updated successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating FAQ: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'All fields are required!';
            }
            header('Location: faqs.php');
            exit();
            break;
            
        case 'delete':
            $id = $_GET['id'] ?? '';
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = 'FAQ deleted successfully!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error deleting FAQ: ' . $e->getMessage();
                }
            }
            header('Location: faqs.php');
            exit();
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            if (!empty($id)) {
                try {
                    $stmt = $pdo->prepare("UPDATE faqs SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['success'] = 'FAQ status updated!';
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating FAQ status: ' . $e->getMessage();
                }
            }
            header('Location: faqs.php');
            exit();
            break;
    }
}

// Get all FAQs
try {
    $stmt = $pdo->query("
        SELECT * FROM faqs 
        ORDER BY sort_order ASC, created_at DESC
    ");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faqs = [];
    $_SESSION['error'] = 'Error fetching FAQs: ' . $e->getMessage();
}

// Get FAQ for editing
$editFaq = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editFaq = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching FAQ: ' . $e->getMessage();
    }
}

// Get FAQ statistics
try {
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM faqs WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM faqs WHERE status = 'inactive'")->fetchColumn(),
        'general' => $pdo->query("SELECT COUNT(*) FROM faqs WHERE category = 'general'")->fetchColumn(),
        'billing' => $pdo->query("SELECT COUNT(*) FROM faqs WHERE category = 'billing'")->fetchColumn(),
        'technical' => $pdo->query("SELECT COUNT(*) FROM faqs WHERE category = 'technical'")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'general' => 0, 'billing' => 0, 'technical' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs Management - YBT Digital Admin</title>
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
        
        .faqs-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6366f1;
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .faq-question {
            font-weight: 600;
            color: #fff;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .faq-answer {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .faq-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .category-general {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .category-billing {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .category-technical {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
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
                <a href="faqs.php" class="nav-link active">
                    <i class="fas fa-question-circle"></i> FAQs
                </a>
                <a href="testimonials.php" class="nav-link">
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
                        <h2 class="text-white mb-2">FAQs Management</h2>
                        <p class="text-muted mb-0">Manage frequently asked questions</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#faqModal">
                        <i class="fas fa-plus me-2"></i> Add FAQ
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
                    <div class="stat-label">Total FAQs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['general']; ?></div>
                    <div class="stat-label">General</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['billing']; ?></div>
                    <div class="stat-label">Billing</div>
                </div>
            </div>

            <!-- FAQs List -->
            <div class="faqs-container">
                <?php if (empty($faqs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-question-circle"></i>
                        <h4>No FAQs Found</h4>
                        <p>Start by creating your first FAQ.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></div>
                            <div class="faq-answer"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>
                            <div class="faq-meta">
                                <span class="category-badge category-<?php echo $faq['category']; ?>">
                                    <?php echo ucfirst($faq['category']); ?>
                                </span>
                                <span class="status-badge status-<?php echo $faq['status']; ?>">
                                    <?php echo ucfirst($faq['status']); ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-sort-numeric-up me-1"></i>
                                    Order: <?php echo $faq['sort_order']; ?>
                                </small>
                                <div class="ms-auto">
                                    <button class="btn-icon btn-edit" onclick="editFaq(<?php echo $faq['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-toggle" onclick="toggleStatus(<?php echo $faq['id']; ?>, '<?php echo $faq['status']; ?>')" title="Toggle Status">
                                        <i class="fas fa-<?php echo $faq['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="deleteFaq(<?php echo $faq['id']; ?>)" title="Delete">
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

    <!-- FAQ Modal -->
    <div class="modal fade" id="faqModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="faqForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="faqId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question *</label>
                            <input type="text" class="form-control" id="question" name="question" required 
                                   placeholder="Enter the FAQ question">
                        </div>
                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer *</label>
                            <textarea class="form-control" id="answer" name="answer" rows="4" required 
                                      placeholder="Enter the detailed answer"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="general">General</option>
                                    <option value="billing">Billing</option>
                                    <option value="technical">Technical</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="order" name="order" value="0" min="0">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit FAQ
        function editFaq(id) {
            window.location.href = '?edit=' + id;
        }

        // Toggle FAQ status
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

        // Delete FAQ
        function deleteFaq(id) {
            if (confirm('Are you sure you want to delete this FAQ? This action cannot be undone.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }

        // Handle edit mode
        <?php if ($editFaq): ?>
            document.getElementById('modalTitle').textContent = 'Edit FAQ';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('faqId').value = '<?php echo $editFaq['id']; ?>';
            document.getElementById('question').value = '<?php echo htmlspecialchars($editFaq['question']); ?>';
            document.getElementById('answer').value = '<?php echo htmlspecialchars($editFaq['answer']); ?>';
            document.getElementById('category').value = '<?php echo $editFaq['category']; ?>';
            document.getElementById('order').value = '<?php echo $editFaq['sort_order']; ?>';
            document.getElementById('status').value = '<?php echo $editFaq['status']; ?>';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('faqModal'));
            modal.show();
        <?php endif; ?>
    </script>
</body>
</html>