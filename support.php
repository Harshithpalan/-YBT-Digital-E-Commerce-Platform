<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=support.php');
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $category = $_POST['category'] ?? 'general';
    
    if (!empty($subject) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets (user_id, subject, message, priority, category, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'open', NOW())
            ");
            $stmt->execute([$userId, $subject, $message, $priority, $category]);
            $success = 'Support ticket submitted successfully! We will respond within 24 hours.';
        } catch (PDOException $e) {
            $error = 'Error submitting ticket: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get user's tickets
try {
    $stmt = $pdo->prepare("
        SELECT st.*, COUNT(sr.id) as reply_count,
               CASE 
                   WHEN st.status = 'open' AND TIMESTAMPDIFF(HOUR, st.created_at, NOW()) < 24 THEN 'urgent'
                   WHEN st.status = 'open' AND TIMESTAMPDIFF(HOUR, st.created_at, NOW()) < 72 THEN 'normal'
                   ELSE st.status
               END as urgency
        FROM support_tickets st 
        LEFT JOIN support_replies sr ON st.id = sr.ticket_id 
        WHERE st.user_id = ? 
        GROUP BY st.id 
        ORDER BY st.created_at DESC
    ");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
    $error = 'Error fetching tickets: ' . $e->getMessage();
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = [];
}

// Get support statistics
try {
    $stats = [
        'total_tickets' => $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE user_id = $userId")->fetchColumn() : 0,
        'open_tickets' => $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status = 'open'")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE user_id = $userId AND status = 'open'")->fetchColumn() : 0,
        'resolved_tickets' => $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status = 'closed'")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE user_id = $userId AND status = 'closed'")->fetchColumn() : 0
    ];
} catch (PDOException $e) {
    $stats = ['total_tickets' => 0, 'open_tickets' => 0, 'resolved_tickets' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --support-hero: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .support-hero-section {
            background: var(--support-hero);
            border-radius: 30px;
            padding: 4rem 3rem;
            text-align: center;
            color: white;
            margin-bottom: 4rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .support-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .support-card {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .support-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .support-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(99, 102, 241, 0.25);
        }
        
        .support-icon-large {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 2rem;
        }
        
        .support-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .support-description {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .contact-methods {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: var(--card-bg);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .contact-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .contact-item:last-child {
            margin-bottom: 0;
        }
        
        .contact-item i {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .contact-info {
            flex: 1;
        }
        
        .contact-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .contact-value {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .ticket-form-section {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .form-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .tickets-section {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }
        
        .ticket-item {
            background: var(--bg-light);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .ticket-item:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .ticket-subject {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .ticket-meta-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }
        
        .priority-high {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .priority-medium {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .priority-low {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .status-open {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .status-closed {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }
        
        .status-replied {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .category-badge {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .ticket-content {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .ticket-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .ticket-meta-info {
            display: flex;
            gap: 1.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .ticket-meta-info i {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 15px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 15px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
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
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 2rem;
            opacity: 0.3;
        }
        
        .empty-state h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .support-container {
                padding: 1rem;
            }
            
            .hero-section {
                padding: 2rem 1.5rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .support-options {
                grid-template-columns: 1fr;
            }
            
            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .ticket-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="support-container">
        <!-- Hero Section -->
        <div class="support-hero-section">
            <h1 class="hero-title">Customer Support Center</h1>
            <p class="hero-subtitle">We're here to help you 24/7. Get instant support from our dedicated team.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_tickets']; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['open_tickets']; ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['resolved_tickets']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>

        <!-- Support Options -->
        <div class="support-options">
            <div class="support-card">
                <div class="support-icon-large">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="support-title">Phone Support</h3>
                <p class="support-description">
                    Speak directly with our support team for immediate assistance with complex issues.
                </p>
                <div class="contact-methods">
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div class="contact-info">
                            <div class="contact-label">Main Line</div>
                            <div class="contact-value">+91 80 1234 5678</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-volume"></i>
                        <div class="contact-info">
                            <div class="contact-label">Toll-Free</div>
                            <div class="contact-value">1-800-YBT-SUPPORT</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="support-card">
                <div class="support-icon-large">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="support-title">Live Chat</h3>
                <p class="support-description">
                    Get instant help through our live chat service. Average response time under 2 minutes.
                </p>
                <div class="contact-methods">
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div class="contact-info">
                            <div class="contact-label">Available Hours</div>
                            <div class="contact-value">24/7 Support</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-bolt"></i>
                        <div class="contact-info">
                            <div class="contact-label">Avg Response</div>
                            <div class="contact-value">Under 2 minutes</div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3">
                    <i class="fas fa-comment-dots me-2"></i> Start Live Chat
                </button>
            </div>

            <div class="support-card">
                <div class="support-icon-large">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 class="support-title">Email Support</h3>
                <p class="support-description">
                    Send us detailed questions and receive comprehensive responses within 24 hours.
                </p>
                <div class="contact-methods">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-info">
                            <div class="contact-label">General Support</div>
                            <div class="contact-value">support@ybtdigital.com</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-rocket"></i>
                        <div class="contact-info">
                            <div class="contact-label">Priority Support</div>
                            <div class="contact-value">priority@ybtdigital.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Ticket Form -->
        <div class="ticket-form-section">
            <h2 class="section-title">Submit a Support Ticket</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject" required 
                               placeholder="Brief description of your issue">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="general">General</option>
                            <option value="technical">Technical</option>
                            <option value="billing">Billing</option>
                            <option value="account">Account</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="6" required 
                              placeholder="Please describe your issue in detail. Include any error messages, steps to reproduce, and what you've already tried."></textarea>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" name="submit_ticket" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i> Submit Ticket
                    </button>
                </div>
            </form>
        </div>

        <!-- Your Tickets -->
        <div class="tickets-section">
            <h2 class="section-title">Your Support Tickets</h2>
            
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>No Support Tickets</h4>
                    <p>You haven't submitted any support tickets yet. Create your first ticket above to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach($tickets as $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <div>
                                <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div class="ticket-meta-badges">
                                    <span class="badge category-badge"><?php echo ucfirst($ticket['category']); ?></span>
                                    <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?> Priority
                                    </span>
                                    <span class="badge status-<?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="ticket-content">
                            <?php echo nl2br(htmlspecialchars(substr($ticket['message'], 0, 200))); ?>
                            <?php if (strlen($ticket['message']) > 200): ?>...<?php endif; ?>
                        </div>
                        <div class="ticket-footer">
                            <div class="ticket-meta-info">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></span>
                                <span><i class="fas fa-reply"></i> <?php echo $ticket['reply_count']; ?> replies</span>
                            </div>
                            <a href="ticket-details.php?id=<?php echo $ticket['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-resize textarea
        const messageTextarea = document.getElementById('message');
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Simulate live chat (for demo purposes)
        document.querySelector('.btn-primary').addEventListener('click', function(e) {
            if (e.target.textContent.includes('Start Live Chat')) {
                e.preventDefault();
                alert('Live chat feature would open here. This is a demo - in production, this would connect to a live chat service.');
            }
        });
    </script>
</body>
</html>
