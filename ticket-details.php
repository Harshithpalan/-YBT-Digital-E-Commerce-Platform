<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=ticket-details.php');
    exit();
}

$userId = $_SESSION['user_id'];
$ticketId = $_GET['id'] ?? '';
$success = '';
$error = '';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO support_replies (ticket_id, user_id, message, is_admin, created_at) 
                VALUES (?, ?, ?, FALSE, NOW())
            ");
            $stmt->execute([$ticketId, $message, $userId]);
            
            // Update ticket status to replied
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'replied' WHERE id = ?");
            $stmt->execute([$ticketId]);
            
            $success = 'Reply submitted successfully!';
        } catch (PDOException $e) {
            $error = 'Error submitting reply: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter a message.';
    }
}

// Get ticket details
try {
    $stmt = $pdo->prepare("
        SELECT st.*, u.name as user_name, u.email as user_email 
        FROM support_tickets st 
        LEFT JOIN users u ON st.user_id = u.id 
        WHERE st.id = ? AND (st.user_id = ? OR ? = 1)
    ");
    $stmt->execute([$ticketId, $userId, $userId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header('Location: support.php');
        exit();
    }
} catch (PDOException $e) {
    $error = 'Error fetching ticket: ' . $e->getMessage();
    $ticket = null;
}

// Get ticket replies
try {
    $stmt = $pdo->prepare("
        SELECT sr.*, u.name as user_name, u.email as user_email 
        FROM support_replies sr 
        LEFT JOIN users u ON sr.user_id = u.id 
        WHERE sr.ticket_id = ? 
        ORDER BY sr.created_at ASC
    ");
    $stmt->execute([$ticketId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $replies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .ticket-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .ticket-header-box {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        
        .ticket-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .ticket-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }
        
        .priority-high {
            background: rgba(239, 68, 68, 0.3);
            color: #fff;
        }
        
        .priority-medium {
            background: rgba(245, 158, 11, 0.3);
            color: #fff;
        }
        
        .priority-low {
            background: rgba(34, 197, 94, 0.3);
            color: #fff;
        }
        
        .status-open {
            background: rgba(34, 197, 94, 0.3);
            color: #fff;
        }
        
        .status-closed {
            background: rgba(107, 114, 128, 0.3);
            color: #fff;
        }
        
        .status-replied {
            background: rgba(59, 130, 246, 0.3);
            color: #fff;
        }
        
        .conversation-box {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .message {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .message.admin {
            border-left-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }
        
        .reply-form-box {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="ticket-container">
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

        <?php if ($ticket): ?>
            <!-- Ticket Header -->
            <div class="ticket-header-box">
                <div class="ticket-title"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                <div class="ticket-meta">
                    <span class="badge priority-<?php echo $ticket['priority']; ?>">
                        <?php echo ucfirst($ticket['priority']); ?> Priority
                    </span>
                    <span class="badge status-<?php echo $ticket['status']; ?>">
                        <?php echo ucfirst($ticket['status']); ?>
                    </span>
                    <span class="badge">
                        <?php echo ucfirst($ticket['category']); ?>
                    </span>
                </div>
                <div class="mt-2">
                    <small>Created: <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></small>
                </div>
            </div>

            <!-- Conversation -->
            <div class="conversation-box">
                <h3 class="mb-4">Conversation</h3>
                
                <!-- Original Message -->
                <div class="message">
                    <div class="message-header">
                        <div class="message-author">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($ticket['user_name']); ?>
                        </div>
                        <div class="message-time">
                            <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                        </div>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                    </div>
                </div>

                <!-- Replies -->
                <?php foreach ($replies as $reply): ?>
                    <div class="message <?php echo $reply['is_admin'] ? 'admin' : ''; ?>">
                        <div class="message-header">
                            <div class="message-author">
                                <i class="fas fa-<?php echo $reply['is_admin'] ? 'headset' : 'user'; ?> me-2"></i>
                                <?php echo $reply['is_admin'] ? 'Support Team' : htmlspecialchars($reply['user_name']); ?>
                                <?php if ($reply['is_admin']): ?>
                                    <span class="badge bg-success ms-2">Staff</span>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Reply Form -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="reply-form-box">
                    <h3 class="mb-4">Add Reply</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required 
                                      placeholder="Type your reply here..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="submit_reply" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Send Reply
                            </button>
                            <a href="support.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Support
                            </a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">This ticket has been closed.</p>
                    <a href="support.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Support
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center">
                <h3>Ticket not found</h3>
                <p class="text-muted">The ticket you're looking for doesn't exist or you don't have permission to view it.</p>
                <a href="support.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Support
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
