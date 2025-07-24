<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
$featured_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Store - Home</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Best Computer Store Online</h1>
                    <p class="hero-subtitle">Find the perfect computer hardware for your needs</p>
                    <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <section class="py-5 categories-section">
        <div class="container">
            <h2 class="text-center mb-5" style="color: #01344d;">Shop by Category</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="category-card">
                       <i class="bi bi-laptop"></i>
                        <h4>Laptops</h4>
                        <p>Gaming and business laptops</p>
                        <a href="products.php?category=laptops" class="btn btn-outline-light">Browse</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="category-card">
                        <i class="bi bi-display"></i>
                        <h4>Desktops</h4>
                        <p>Custom and pre-built PCs</p>
                        <a href="products.php?category=desktops" class="btn btn-outline-light">Browse</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="category-card">
                        <i class="bi bi-cpu"></i>
                        <h4>Components</h4>
                        <p>Graphics cards, RAM, and more</p>
                        <a href="products.php?category=graphic_cards" class="btn btn-outline-light">Browse</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5" style="color: #01344d;">Featured Products</h2>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price"><?php echo formatPrice($product['price']); ?></span>
                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>
</body>
</html>