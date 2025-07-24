<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=checkout.php');
}

$error = '';
$success = false;


$stmt = $pdo->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect('cart.php');
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal >= 100 ? 0 : 9.99;
$total = $subtotal + $shipping;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $billing_name = sanitize($_POST['billing_name']);
    $billing_email = sanitize($_POST['billing_email']);
    $billing_address = sanitize($_POST['billing_address']);
    $billing_city = sanitize($_POST['billing_city']);
    $billing_postal = sanitize($_POST['billing_postal']);
    $payment_method = sanitize($_POST['payment_method']);
    
    if (empty($billing_name) || empty($billing_email) || empty($billing_address) || 
        empty($billing_city) || empty($billing_postal) || empty($payment_method)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $pdo->beginTransaction();
            $stock_error = false;
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $current_stock = $stmt->fetchColumn();
                
                if ($current_stock < $item['quantity']) {
                    $stock_error = true;
                    break;
                }
            }
            
            if ($stock_error) {
                $error = 'Some items are no longer available in the requested quantity';
                $pdo->rollBack();
            } else {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $total]);
                $order_id = $pdo->lastInsertId();

                foreach ($cart_items as $item) {
                    // Add to order_items
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $pdo->commit();
                $success = true;
                $_SESSION['last_order_id'] = $order_id;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order processing failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Computer Store</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <?php if ($success): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your purchase. Your order #<?php echo $_SESSION['last_order_id']; ?> has been confirmed.</p>
                <div class="mt-4">
                    <a href="orders.php" class="btn btn-primary">View Order History</a>
                    <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <h2><i class="bi bi-credit-card"></i> Checkout</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Billing Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Billing Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="billing_name" name="billing_name" 
                                               value="<?php echo isset($_POST['billing_name']) ? htmlspecialchars($_POST['billing_name']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="billing_email" name="billing_email" 
                                               value="<?php echo isset($_POST['billing_email']) ? htmlspecialchars($_POST['billing_email']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="billing_address" class="form-label">Address *</label>
                                    <input type="text" class="form-control" id="billing_address" name="billing_address" 
                                           value="<?php echo isset($_POST['billing_address']) ? htmlspecialchars($_POST['billing_address']) : ''; ?>" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="billing_city" name="billing_city" 
                                               value="<?php echo isset($_POST['billing_city']) ? htmlspecialchars($_POST['billing_city']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_postal" class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" id="billing_postal" name="billing_postal" 
                                               value="<?php echo isset($_POST['billing_postal']) ? htmlspecialchars($_POST['billing_postal']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Payment Method</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                    <label class="form-check-label" for="credit_card">
                                        <i class="bi bi-credit-card"></i> Credit Card
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <i class="bi bi-paypal"></i> PayPal
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="bi bi-bank"></i> Bank Transfer
                                    </label>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    This is a demo store. No actual payment will be processed.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Order Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['name']); ?> <?php echo $item['quantity']; ?></span>
                                    <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                                </div>
                                <?php endforeach; ?>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span><?php echo $shipping > 0 ? formatPrice($shipping) : 'FREE'; ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong><?php echo formatPrice($total); ?></strong>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 btn-lg">
                                    <i class="bi bi-lock"></i> Place Order
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-shield-lock"></i> 
                                        Your information is secure and encrypted
                                    </small>
                                </div>
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