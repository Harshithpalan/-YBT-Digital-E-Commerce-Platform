<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

// Check if admin is logged in
$adminAuth = new AdminAuth();
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    
    switch ($section) {
        case 'general':
            $settings = [
                'site_name' => $_POST['site_name'] ?? 'YBT Digital',
                'site_description' => $_POST['site_description'] ?? '',
                'site_email' => $_POST['site_email'] ?? 'admin@ybtdigital.com',
                'site_phone' => $_POST['site_phone'] ?? '',
                'site_address' => $_POST['site_address'] ?? '',
                'currency' => $_POST['currency'] ?? 'USD',
                'timezone' => $_POST['timezone'] ?? 'UTC'
            ];
            break;
            
        case 'payment':
            $settings = [
                'payment_gateway' => $_POST['payment_gateway'] ?? 'stripe',
                'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                'paypal_client_secret' => $_POST['paypal_client_secret'] ?? '',
                'bank_details' => $_POST['bank_details'] ?? ''
            ];
            break;
            
        case 'email':
            $settings = [
                'smtp_host' => $_POST['smtp_host'] ?? '',
                'smtp_port' => $_POST['smtp_port'] ?? '587',
                'smtp_username' => $_POST['smtp_username'] ?? '',
                'smtp_password' => $_POST['smtp_password'] ?? '',
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                'email_from_name' => $_POST['email_from_name'] ?? 'YBT Digital',
                'email_from_address' => $_POST['email_from_address'] ?? 'noreply@ybtdigital.com'
            ];
            break;
            
        case 'system':
            $settings = [
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'debug_mode' => isset($_POST['debug_mode']) ? '1' : '0',
                'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
                'email_verification' => isset($_POST['email_verification']) ? '1' : '0',
                'max_file_size' => $_POST['max_file_size'] ?? '10',
                'allowed_file_types' => $_POST['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,zip'
            ];
            break;
            
        default:
            $settings = [];
    }
    
    // Save settings to database
    foreach ($settings as $key => $value) {
        try {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error saving settings: ' . $e->getMessage();
        }
    }
    
    $_SESSION['success'] = 'Settings updated successfully!';
    header('Location: settings.php');
    exit();
}

// Get current settings from database
$currentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error loading settings: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - YBT Digital Admin</title>
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
        
        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1rem;
        }
        
        .tab-button {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .tab-button:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .tab-button.active {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: #fff;
        }
        
        .settings-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #fff;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #6366f1;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
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
        
        .form-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
        
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
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
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
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
        
        .upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .upload-area i {
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 1rem;
        }
        
        .current-logo {
            max-width: 200px;
            max-height: 100px;
            border-radius: 10px;
            margin-bottom: 1rem;
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
                <a href="testimonials.php" class="nav-link">
                    <i class="fas fa-quote-left"></i> Testimonials
                </a>
                <a href="settings.php" class="nav-link active">
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
                        <h2 class="text-white mb-2">Settings</h2>
                        <p class="text-muted mb-0">Configure your website settings</p>
                    </div>
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

            <!-- General Settings -->
            <div class="settings-section">
                <h3 class="section-title">General Settings</h3>
                <form method="POST">
                    <input type="hidden" name="section" value="general">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_name" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'YBT Digital'); ?>">
                            <div class="form-text">Your website name</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_email" class="form-label">Admin Email</label>
                            <input type="email" class="form-control" id="site_email" name="site_email" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_email'] ?? 'admin@ybtdigital.com'); ?>">
                            <div class="form-text">Contact email address</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?></textarea>
                        <div class="form-text">Brief description of your website</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="site_phone" name="site_phone" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="USD" <?php echo ($currentSettings['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR" <?php echo ($currentSettings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo ($currentSettings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                <option value="INR" <?php echo ($currentSettings['currency'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="site_address" class="form-label">Address</label>
                        <textarea class="form-control" id="site_address" name="site_address" rows="2"><?php echo htmlspecialchars($currentSettings['site_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save General Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Settings -->
            <div class="settings-section">
                <h3 class="section-title">Payment Settings</h3>
                <form method="POST">
                    <input type="hidden" name="section" value="payment">
                    
                    <div class="mb-3">
                        <label for="payment_gateway" class="form-label">Primary Payment Gateway</label>
                        <select class="form-select" id="payment_gateway" name="payment_gateway">
                            <option value="stripe" <?php echo ($currentSettings['payment_gateway'] ?? 'stripe') === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                            <option value="paypal" <?php echo ($currentSettings['payment_gateway'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                            <option value="bank" <?php echo ($currentSettings['payment_gateway'] ?? '') === 'bank' ? 'selected' : ''; ?>>Bank Transfer</option>
                        </select>
                    </div>

                    <h5 class="text-white mb-3">Stripe Configuration</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stripe_public_key" class="form-label">Public Key</label>
                            <input type="text" class="form-control" id="stripe_public_key" name="stripe_public_key" 
                                   value="<?php echo htmlspecialchars($currentSettings['stripe_public_key'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stripe_secret_key" class="form-label">Secret Key</label>
                            <input type="password" class="form-control" id="stripe_secret_key" name="stripe_secret_key" 
                                   value="<?php echo htmlspecialchars($currentSettings['stripe_secret_key'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="text-white mb-3">PayPal Configuration</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paypal_client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="paypal_client_id" name="paypal_client_id" 
                                   value="<?php echo htmlspecialchars($currentSettings['paypal_client_id'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="paypal_client_secret" class="form-label">Client Secret</label>
                            <input type="password" class="form-control" id="paypal_client_secret" name="paypal_client_secret" 
                                   value="<?php echo htmlspecialchars($currentSettings['paypal_client_secret'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="text-white mb-3">Bank Transfer Details</h5>
                    <div class="mb-3">
                        <label for="bank_details" class="form-label">Bank Details</label>
                        <textarea class="form-control" id="bank_details" name="bank_details" rows="4" 
                                  placeholder="Enter your bank account details here..."><?php echo htmlspecialchars($currentSettings['bank_details'] ?? ''); ?></textarea>
                        <div class="form-text">These details will be shown to customers for bank transfers</div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="settings-section">
                <h3 class="section-title">Email Settings</h3>
                <form method="POST">
                    <input type="hidden" name="section" value="email">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                   value="<?php echo htmlspecialchars($currentSettings['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                   value="<?php echo htmlspecialchars($currentSettings['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_username" class="form-label">SMTP Username</label>
                            <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                   value="<?php echo htmlspecialchars($currentSettings['smtp_username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_password" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                   value="<?php echo htmlspecialchars($currentSettings['smtp_password'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_encryption" class="form-label">Encryption</label>
                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                <option value="tls" <?php echo ($currentSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email_from_address" class="form-label">From Email</label>
                            <input type="email" class="form-control" id="email_from_address" name="email_from_address" 
                                   value="<?php echo htmlspecialchars($currentSettings['email_from_address'] ?? 'noreply@ybtdigital.com'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email_from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                               value="<?php echo htmlspecialchars($currentSettings['email_from_name'] ?? 'YBT Digital'); ?>">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Email Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Settings -->
            <div class="settings-section">
                <h3 class="section-title">System Settings</h3>
                <form method="POST">
                    <input type="hidden" name="section" value="system">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                       <?php echo ($currentSettings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-white" for="maintenance_mode">
                                    Maintenance Mode
                                </label>
                            </div>
                            <div class="form-text">Disable the website for maintenance</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" 
                                       <?php echo ($currentSettings['debug_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-white" for="debug_mode">
                                    Debug Mode
                                </label>
                            </div>
                            <div class="form-text">Enable error reporting and debugging</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" 
                                       <?php echo ($currentSettings['allow_registration'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-white" for="allow_registration">
                                    Allow User Registration
                                </label>
                            </div>
                            <div class="form-text">Enable new user registration</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_verification" name="email_verification" 
                                       <?php echo ($currentSettings['email_verification'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label text-white" for="email_verification">
                                    Email Verification Required
                                </label>
                            </div>
                            <div class="form-text">Require email verification for new accounts</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="max_file_size" class="form-label">Max File Size (MB)</label>
                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                   value="<?php echo htmlspecialchars($currentSettings['max_file_size'] ?? '10'); ?>" min="1" max="100">
                            <div class="form-text">Maximum file upload size</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                   value="<?php echo htmlspecialchars($currentSettings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,zip'); ?>">
                            <div class="form-text">Comma-separated list of allowed extensions</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save System Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>