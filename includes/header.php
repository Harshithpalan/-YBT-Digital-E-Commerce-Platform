<?php
// Determine if we are in the admin directory
$is_admin_path = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base_url = $is_admin_path ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>YBT Digital - Premium Digital Products</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.0.0/css/all.css">
    <!-- Google Fonts Roboto -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap">
    <!-- MDB -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    
    <!-- Theme Selection Script (Prevent Flash) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-mdb-theme', savedTheme);
        })();
    </script>
</head>
<body>

<?php if(!$is_admin_path): 
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Modern Navigation (Only for non-admin pages) -->
<nav class="navbar navbar-expand-lg sticky-top shadow-0">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_url; ?>index.php">
            <i class="fas fa-wallet me-2"></i>
            YBT Digital
        </a>
        
        <button class="navbar-toggler" type="button" data-mdb-collapse-init data-mdb-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'products.php' || $current_page == 'product.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>products.php">Products</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $current_page == 'category.php' ? 'active' : ''; ?>" href="#" id="navbarDropdown" role="button" data-mdb-dropdown-init aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu border-0 shadow" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>products.php?cat=web">Web Templates</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>products.php?cat=mobile">Mobile Apps</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>products.php?cat=graphics">Graphics</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'support.php' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>support.php">Support</a>
                </li>
            </ul>

            <div class="navbar-actions">
                <!-- Search Bar -->
                <div class="search-container d-none d-lg-block">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search products...">
                </div>

                <!-- Theme Toggle -->
                <div class="theme-toggle" id="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </div>

                <!-- Cart -->
                <a class="nav-icon-link" href="<?php echo $base_url; ?>cart.php" title="View Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge rounded-pill cart-badge bg-danger">0</span>
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a class="nav-icon-link dropdown-toggle hidden-arrow" href="#" id="userMenu" role="button" data-mdb-dropdown-init aria-expanded="false">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>profile.php">
                                    <i class="fas fa-user-edit me-2 small"></i> My Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo $base_url; ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2 small"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>login.php" class="btn btn-link px-3 text-dark text-capitalize fw-bold">Login</a>
                    <a href="<?php echo $base_url; ?>register.php" class="btn btn-modern btn-primary text-capitalize shadow-sm">SIGN UP</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<main>
