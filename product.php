<?php
require_once 'config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if (!$product_id) {
    redirect('products.php');
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && $quantity <= $product['stock']) {
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $new_quantity = $existing['quantity'] + $quantity;
            if ($new_quantity <= $product['stock']) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$new_quantity, $_SESSION['user_id'], $product_id]);
                $message = 'Cart updated successfully!';
            } else {
                $message = 'Not enough stock available!';
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            $message = 'Product added to cart!';
        }
    } else {
        $message = 'Invalid quantity!';
    }
}

// Get related products
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$stmt->execute([$product['category'], $product_id]);
$related_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Computer Store</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item"><a href="products.php?category=<?php echo urlencode($product['category']); ?>">
                    <?php echo ucwords(str_replace('_', ' ', $product['category'])); ?>
                </a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     class="img-fluid product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted"><?php echo ucwords(str_replace('_', ' ', $product['category'])); ?></p>
                
                <div class="price-section mb-3">
                    <h2 class="text-primary"><?php echo formatPrice($product['price']); ?></h2>
                </div>

                <div class="stock-info mb-3">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">
                            <i class="bi bi-check"></i> In Stock (<?php echo $product['stock']; ?> available)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger">
                            <i class="bi bi-ban"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <div class="description mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                    <form method="POST" class="add-to-cart-form">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity:</label>
                                <select name="quantity" id="quantity" class="form-select">
                                    <?php for ($i = 1; $i <= min(10, $product['stock']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                       class="btn btn-primary btn-lg">Login to Purchase</a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>Out of Stock</button>
                <?php endif; ?>

                <div class="product-features mt-4">
                    <h6>Product Features:</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-truck text-primary"></i> Free shipping on orders over $100</li>
                        <li><i class="bi bi-arrow-counterclockwise text-primary"></i> 30-day return policy</li>
                        <li><i class="bi bi-shield-check text-primary"></i> 1-year warranty</li>
                        <li><i class="bi bi-headset text-primary"></i> 24/7 customer support</li>
                    </ul>
                </div>
            </div>
        </div>
</div>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>
</body>
</html>