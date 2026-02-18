<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

$auth = new AdminAuth($pdo);
if ($auth->isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Login - YBT Digital</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.0.0/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: var(--primary-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .login-icon {
            font-size: 3rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }
        .form-control {
            background: #f1f5f9 !important;
            border: none !important;
            padding: 0.8rem 1.2rem !important;
            border-radius: 10px !important;
        }
        .credentials-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="login-card text-center animate__animated animate__zoomIn">
    <div class="login-icon">
        <i class="fas fa-shield-alt"></i>
    </div>
    <h2 class="fw-bold mb-1">Admin Login</h2>
    <p class="text-muted mb-4">Access YBT Digital Admin Panel</p>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-outline mb-4" data-mdb-input-init>
            <input type="email" name="email" id="email" class="form-control" required />
            <label class="form-label" for="email">Email Address</label>
        </div>

        <div class="form-outline mb-4" data-mdb-input-init>
            <input type="password" name="password" id="password" class="form-control" required />
            <label class="form-label" for="password">Password</label>
        </div>

        <div class="d-flex justify-content-start mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="rememberMe" />
                <label class="form-check-label small" for="rememberMe">Remember Me</label>
            </div>
        </div>

        <button type="submit" class="btn btn-modern btn-primary w-100 mb-4" style="background: var(--primary-gradient); border: none;">
            <i class="fas fa-sign-in-alt me-2"></i> LOGIN
        </button>

        <a href="../index.php" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Website
        </a>
    </form>

    <div class="credentials-box">
        <p class="fw-bold mb-2">Default Admin Credentials</p>
        <p class="mb-1 text-muted">Email: <span class="text-dark">admin@ybtdigital.com</span></p>
        <p class="mb-0 text-muted">Password: <span class="text-dark">password</span></p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
</html>
