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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        if ($auth->register($name, $email, $password)) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Registration failed. Email may already exist.";
        }
    }
}

include 'includes/header.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-0 border rounded-4">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4 text-center">Register</h3>
                    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="form-outline mb-4" data-mdb-input-init>
                            <input type="text" name="name" id="name" class="form-control" required />
                            <label class="form-label" for="name">Full Name</label>
                        </div>
                        <div class="form-outline mb-4" data-mdb-input-init>
                            <input type="email" name="email" id="email" class="form-control" required />
                            <label class="form-label" for="email">Email Address</label>
                        </div>
                        <div class="form-outline mb-4" data-mdb-input-init>
                            <input type="password" name="password" id="password" class="form-control" required />
                            <label class="form-label" for="password">Password</label>
                        </div>
                        <div class="form-outline mb-4" data-mdb-input-init>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required />
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-rounded">Register</button>
                    </form>
                    <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
