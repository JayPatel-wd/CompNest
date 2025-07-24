<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="./images/logo.png" alt="Comp Nest" class="img-fluid" style="max-height: 40px;">
            CompNest
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                        <i class="bi bi-grid"></i> All Products
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-list"></i> Categories
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="products.php?category=laptops">
                            <i class="bi bi-laptop"></i> Laptops
                        </a></li>
                        <li><a class="dropdown-item" href="products.php?category=desktops">
                            <i class="bi bi-display"></i> Desktops
                        </a></li>
                        <li><a class="dropdown-item" href="products.php?category=graphic_cards">
                            <i class="bi bi-gpu-card"></i> Graphics Cards
                        </a></li>
                        <li><a class="dropdown-item" href="products.php?category=memories">
                            <i class="bi bi-memory"></i> Memory
                        </a></li>
                        <li><a class="dropdown-item" href="products.php?category=storage">
                            <i class="bi bi-device-hdd"></i> Storage
                        </a></li>
                        <li><a class="dropdown-item" href="products.php?category=accessories">
                            <i class="bi bi-mouse"></i> Accessories
                        </a></li>
                    </ul>
                </li>
            </ul>
            
            <!-- Search Bar -->
            <div class="d-flex me-3">
                <form class="d-flex" method="GET" action="products.php" role="search">
                    <div class="input-group">
                        <input class="form-control form-control-sm" type="search" name="search" 
                               placeholder="Search products..." aria-label="Search"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               style="min-width: 200px;">
                        <button class="btn btn-outline-light btn-sm" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                            <i class="bi bi-cart3"></i> Cart
                            <?php
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $cart_count = $stmt->fetchColumn() ?: 0;
                                if ($cart_count > 0) {
                                    echo "<span class='position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger' id='cart-count'>$cart_count</span>";
                                }
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">My Account</h6></li>
                            <li><a class="dropdown-item" href="orders.php">
                                <i class="bi bi-bag"></i> Order History
                            </a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Administration</h6></li>
                                <li><a class="dropdown-item" href="admin/">
                                    <i class="bi bi-gear"></i> Admin Panel
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-2" href="register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Quick Categories Banner (Optional - shows on homepage) -->
<?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
<div class="bg-light py-2 border-bottom">
    <div class="container">
        <div class="row text-center">
            <div class="col">
                <small class="text-muted">Quick Shop:</small>
                <a href="products.php?category=laptops" class="btn btn-link btn-sm text-decoration-none">Laptops</a>
                <a href="products.php?category=desktops" class="btn btn-link btn-sm text-decoration-none">Desktops</a>
                <a href="products.php?category=graphic_cards" class="btn btn-link btn-sm text-decoration-none">GPUs</a>
                <a href="products.php?category=memories" class="btn btn-link btn-sm text-decoration-none">RAM</a>
                <a href="products.php?category=storage" class="btn btn-link btn-sm text-decoration-none">Storage</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.custom-navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.navbar-nav .nav-link.active {
    font-weight: 600;
    color: #fff !important;
}

.navbar-nav .nav-link:hover {
    color: #f8f9fa !important;
}

.dropdown-menu {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
}

.dropdown-item {
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

#cart-count {
    font-size: 0.7rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, -50%) scale(1.1); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
</style>