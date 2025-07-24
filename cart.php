<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=cart.php');
}

$message = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            $quantity = (int)$quantity;
            $cart_id = (int)$cart_id;
            
            if ($quantity > 0) {
                // Check stock availability
                $stmt = $pdo->prepare("
                    SELECT p.stock 
                    FROM cart c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.id = ? AND c.user_id = ?
                ");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
                $stock = $stmt->fetchColumn();
                
                if ($stock && $quantity <= $stock) {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
                } else {
                    $message = 'Some items exceed available stock!';
                }
            } else {
                // Remove item if quantity is 0
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
            }
        }
        $message = $message ?: 'Cart updated successfully!';
    }
    
    if (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        $message = 'Item removed from cart!';
    }
    
    if (isset($_POST['clear_cart'])) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $message = 'Cart cleared!';
    }
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image_url, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Computer Store</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart text-muted mb-3" style="font-size: 3rem;"></i>
                <h4>Your cart is empty</h4>
                <p>Add some products to get started!</p>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="row align-items-center border-bottom py-3">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             class="img-fluid" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">
                                            Stock: <?php echo $item['stock']; ?> available
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantity:</label>
                                        <select name="quantities[<?php echo $item['id']; ?>]" 
                                                class="form-select form-select-sm">
                                            <?php for ($i = 1; $i <= min(10, $item['stock']); $i++): ?>
                                                <option value="<?php echo $i; ?>" 
                                                        <?php echo $i == $item['quantity'] ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <strong><?php echo formatPrice($item['price']); ?></strong>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="submit" name="remove_item" 
                                                value="<?php echo $item['id']; ?>" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Remove this item?')">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="update_cart" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat"></i> Update Cart
                            </button>
                            <button type="submit" name="clear_cart" class="btn btn-outline-danger"
                                    onclick="return confirm('Clear entire cart?')">
                                <i class="bi bi-trash"></i> Clear Cart
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($total); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php 
                                        $shipping = $total >= 100 ? 0 : 9.99;
                                        echo $shipping > 0 ? formatPrice($shipping) : 'FREE';
                                        ?>
                                    </span>
                                </div>
                                <?php if ($total < 100): ?>
                                <small class="text-muted">
                                    Spend <?php echo formatPrice(100 - $total); ?> more for free shipping!
                                </small>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong><?php echo formatPrice($total + $shipping); ?></strong>
                                </div>
                                <a href="checkout.php" class="btn btn-success w-100 btn-lg">
                                    <i class="bi bi-credit-card"></i> Proceed to Checkout
                                </a>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <h6><i class="bi bi-shield-lock text-success"></i> Secure Checkout</h6>
                                <small class="text-muted">
                                    Your payment information is processed securely. 
                                    We do not store credit card details.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>
</body>
</html>