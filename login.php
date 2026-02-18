<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';
$auth = new Auth($pdo);

if ($auth->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    if ($result === true) {
        header("Location: index.php");
        exit;
    } elseif ($result === 'blocked') {
        $error = "Your account is blocked.";
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>User Login - YBT Digital</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.0.0/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
    </style>
</head>
<body>

<div class="login-card text-center animate__animated animate__zoomIn">
    <div class="login-icon">
        <i class="fas fa-user-circle"></i>
    </div>
    <h2 class="fw-bold mb-1">User Login</h2>
    <p class="text-muted mb-4">Welcome back to YBT Digital</p>

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

        <button type="submit" class="btn btn-modern btn-primary w-100 mb-4" style="background: var(--primary-gradient); border: none;">
            <i class="fas fa-sign-in-alt me-2"></i> LOGIN
        </button>

        <p class="text-center mt-3">Don't have an account? <a href="register.php" class="text-primary fw-bold">Register</a></p>
        
        <a href="index.php" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Website
        </a>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
</html>
