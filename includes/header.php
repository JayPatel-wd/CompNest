<nav class="navbar navbar-expand-lg custom-navbar">
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
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">All Products</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        Categories
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="products.php?category=laptops">Laptops</a></li>
                        <li><a class="dropdown-item" href="products.php?category=desktops">Desktops</a></li>
                        <li><a class="dropdown-item" href="products.php?category=graphic_cards">Graphics Cards</a></li>
                        <li><a class="dropdown-item" href="products.php?category=memories">Memory</a></li>
                        <li><a class="dropdown-item" href="products.php?category=accessories">Accessories</a></li>
                        <li><a class="dropdown-item" href="products.php?category=storage">Storage</a></li>
                    </ul>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart"></i> Cart
                            <?php
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $cart_count = $stmt->fetchColumn() ?: 0;
                                echo "<span class='badge bg-danger'>$cart_count</span>";
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="orders.php">Order History</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>