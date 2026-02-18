<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Check if user is logged in
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($name)) {
        try {
            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $userId]);
            
            // Handle password change if provided
            if (!empty($currentPassword) && !empty($newPassword)) {
                if ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($currentPassword, $user['password'])) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $userId]);
                        $success .= ' Password updated successfully!';
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                }
            }
            
            if (empty($error)) {
                $success = 'Profile updated successfully!' . $success;
            }
        } catch (PDOException $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    } else {
        $error = 'Name is required.';
    }
}

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = [];
    $error = 'Error fetching user data.';
}

// Get user statistics
try {
    $stats = [
        'orders' => $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $userId")->fetchColumn() : 0,
        'downloads' => $pdo->prepare("SELECT COUNT(*) FROM downloads WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM downloads WHERE user_id = $userId")->fetchColumn() : 0,
        'support_tickets' => $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE user_id = $userId")->fetchColumn() : 0
    ];
} catch (PDOException $e) {
    $stats = ['orders' => 0, 'downloads' => 0, 'support_tickets' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - YBT Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-email {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-status {
            display: inline-block;
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            border: 1px solid rgba(34, 197, 94, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: var(--bg-light);
            border-radius: 15px;
            transition: all 0.3s ease;
            color: var(--text-dark);
        }
        
        .stat-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .form-label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            border-radius: 10px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-control:read-only {
            background-color: rgba(0, 0, 0, 0.05);
            opacity: 0.8;
        }
        
        [data-mdb-theme="dark"] .form-control:read-only {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .quick-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--bg-light);
            border-radius: 15px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .quick-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        
        .quick-link i {
            width: 40px;
            height: 40px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .quick-link:hover i {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
        
        .password-section {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-links {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="profile-status">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo ucfirst($user['status']); ?> Account
            </span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['downloads']; ?></div>
                <div class="stat-label">Downloads</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['support_tickets']; ?></div>
                <div class="stat-label">Support Tickets</div>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Edit Profile -->
            <div class="profile-card">
                <h3 class="card-title">Edit Profile</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="password-section">
                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Links -->
            <div class="profile-card">
                <h3 class="card-title">Quick Links</h3>
                <div class="quick-links">
                    <a href="orders.php" class="quick-link">
                        <i class="fas fa-shopping-bag"></i>
                        <div>
                            <div class="fw-bold">My Orders</div>
                            <small class="text-muted">View order history</small>
                        </div>
                    </a>
                    <a href="downloads.php" class="quick-link">
                        <i class="fas fa-download"></i>
                        <div>
                            <div class="fw-bold">Downloads</div>
                            <small class="text-muted">Access purchased files</small>
                        </div>
                    </a>
                    <a href="cart.php" class="quick-link">
                        <i class="fas fa-shopping-cart"></i>
                        <div>
                            <div class="fw-bold">Shopping Cart</div>
                            <small class="text-muted">Manage cart items</small>
                        </div>
                    </a>
                    <a href="support.php" class="quick-link">
                        <i class="fas fa-headset"></i>
                        <div>
                            <div class="fw-bold">Support</div>
                            <small class="text-muted">Get help</small>
                        </div>
                    </a>
                </div>
                
                <div class="mt-4">
                    <h5 class="mb-3">Account Information</h5>
                    <div class="mb-2">
                        <small class="text-muted">Member Since</small>
                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Account Type</small>
                        <div class="fw-bold">Premium Member</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Last Login</small>
                        <div class="fw-bold"><?php echo date('M j, Y g:i A', strtotime($user['last_login'] ?? 'now')); ?></div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-top">
                    <a href="logout.php" class="btn btn-outline-danger w-100">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    </script>
</body>
</html>
