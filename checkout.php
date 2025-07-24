<?php
require_once 'config.php';
require_once 'paypal_config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=checkout.php');
}

$error = '';
$success = false;

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.stock, p.image_url
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

// Get user information and billing details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch();

// Handle billing information save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_billing'])) {
    $billing_name = sanitize($_POST['billing_name']);
    $billing_address = sanitize($_POST['billing_address']);
    $billing_city = sanitize($_POST['billing_city']);
    $billing_postal = sanitize($_POST['billing_postal']);
    $billing_phone = sanitize($_POST['billing_phone']);
    
    // Update user billing information
    $stmt = $pdo->prepare("
        UPDATE users SET 
        billing_name = ?, billing_address = ?, billing_city = ?, 
        billing_postal = ?, billing_phone = ?
        WHERE id = ?
    ");
    $stmt->execute([$billing_name, $billing_address, $billing_city, $billing_postal, $billing_phone, $_SESSION['user_id']]);
    
    // Refresh user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch();
    
    $success_msg = 'Billing information saved successfully!';
}

// Handle PayPal success callback
if (isset($_GET['paypal_success']) && isset($_GET['payment_id'])) {
    $payment_id = sanitize($_GET['payment_id']);
    
    // Verify payment with PayPal
    $payment_verification = verifyPayPalPayment($payment_id);
    
    if ($payment_verification && isset($payment_verification['status']) && $payment_verification['status'] === 'COMPLETED') {
        try {
            $pdo->beginTransaction();
            
            // Re-fetch cart items to ensure they're still available
            $stmt = $pdo->prepare("
                SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.stock
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $current_cart_items = $stmt->fetchAll();
            
            // Check stock availability
            $stock_error = false;
            foreach ($current_cart_items as $item) {
                if ($item['stock'] < $item['quantity']) {
                    $stock_error = true;
                    break;
                }
            }
            
            if ($stock_error) {
                $error = 'Some items are no longer available in the requested quantity';
                $pdo->rollBack();
            } else {
                // Create order with billing information
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_price, payment_method, payment_id, status, 
                                      billing_name, billing_email, billing_address, billing_city, billing_postal, billing_phone) 
                    VALUES (?, ?, 'paypal', ?, 'processing', ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $total, 
                    $payment_id, 
                    $user_info['billing_name'] ?: $user_info['name'], 
                    $user_info['email'],
                    $user_info['billing_address'] ?: '',
                    $user_info['billing_city'] ?: '',
                    $user_info['billing_postal'] ?: '',
                    $user_info['billing_phone'] ?: ''
                ]);
                $order_id = $pdo->lastInsertId();
                
                // Add order items and update stock
                foreach ($current_cart_items as $item) {
                    // Add to order_items
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                    
                    // Update stock
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Log PayPal transaction
                logPayPalTransaction($order_id, $payment_id, 'completed', $total, $payment_verification);
                
                // Clear cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $pdo->commit();
                $success = true;
                $_SESSION['last_order_id'] = $order_id;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Order processing failed: " . $e->getMessage());
            $error = 'Order processing failed. Please contact support with payment ID: ' . $payment_id;
        }
    } else {
        $error = 'Payment verification failed. Please contact support.';
    }
}

// Handle PayPal cancellation
if (isset($_GET['paypal_cancel'])) {
    $error = 'Payment was cancelled. You can try again or contact support if you need help.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - CompNest</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
    
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .billing-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-light mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5>Processing your payment...</h5>
            <p>Please don't close this window</p>
        </div>
    </div>

    <div class="container mt-4 checkout-container">
        <?php if ($success): ?>
            <!-- Success Page -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                </div>
                <h1 class="text-success mb-3">Payment Successful!</h1>
                <p class="lead mb-4">Thank you for your purchase from CompNest!</p>
                
                <div class="card mx-auto" style="max-width: 500px;">
                    <div class="card-body">
                        <h5 class="card-title">Order Confirmation</h5>
                        <div class="row text-start">
                            <div class="col-6">
                                <strong>Order Number:</strong><br>
                                <strong>Payment Method:</strong><br>
                                <strong>Total Amount:</strong><br>
                                <strong>Status:</strong>
                            </div>
                            <div class="col-6">
                                #<?php echo $_SESSION['last_order_id']; ?><br>
                                PayPal<br>
                                <?php echo formatPrice($total); ?><br>
                                <span class="badge bg-warning">Processing</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4 mx-auto" style="max-width: 600px;">
                    <i class="bi bi-info-circle"></i> 
                    <strong>What happens next?</strong><br>
                    You will receive an email confirmation shortly. Your order will be processed within 1-2 business days.
                </div>
                
                <div class="mt-4">
                    <a href="orders.php" class="btn btn-primary me-2">
                        <i class="bi bi-bag"></i> View Order Details
                    </a>
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Checkout Page -->
            <div class="row">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                            <li class="breadcrumb-item active">Checkout</li>
                        </ol>
                    </nav>
                    
                    <h2 class="mb-4">
                        <i class="bi bi-lock-fill text-success"></i> Secure Checkout
                    </h2>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Billing Information -->
                <div class="col-lg-7">
                    <!-- Order Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul"></i> Order Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'images/placeholder.png'); ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="max-height: 60px; object-fit: cover;">
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">
                                            Quantity: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($subtotal); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php if ($shipping > 0): ?>
                                            <?php echo formatPrice($shipping); ?>
                                        <?php else: ?>
                                            <span class="text-success">FREE</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong class="h5">Total:</strong>
                                    <strong class="h5 text-primary"><?php echo formatPrice($total); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-person-lines-fill"></i> Billing Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="billing_name" name="billing_name" 
                                               value="<?php echo htmlspecialchars($user_info['billing_name'] ?: $user_info['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="billing_phone" name="billing_phone" 
                                               value="<?php echo htmlspecialchars($user_info['billing_phone'] ?: ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="billing_address" class="form-label">Address *</label>
                                    <input type="text" class="form-control" id="billing_address" name="billing_address" 
                                           value="<?php echo htmlspecialchars($user_info['billing_address'] ?: ''); ?>" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="billing_city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="billing_city" name="billing_city" 
                                               value="<?php echo htmlspecialchars($user_info['billing_city'] ?: ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="billing_postal" class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" id="billing_postal" name="billing_postal" 
                                               value="<?php echo htmlspecialchars($user_info['billing_postal'] ?: ''); ?>" required>
                                    </div>
                                </div>
                                <button type="submit" name="save_billing" class="btn btn-outline-primary">
                                    <i class="bi bi-save"></i> Save Billing Information
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-paypal"></i> PayPal Payment
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-200px.png" 
                                     alt="PayPal" class="img-fluid" style="max-height: 50px;">
                                <p class="text-muted mt-3 mb-0">
                                    <strong>Pay securely with your PayPal account</strong>
                                </p>
                                <small class="text-muted">
                                    You will be redirected to PayPal to complete your payment
                                </small>
                            </div>

                            <!-- PayPal Button Container -->
                            <div id="paypal-button-container"></div>

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>PayPal Only Payment</strong><br>
                                This store only accepts PayPal payments for secure transactions.
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check"></i> 
                                    SSL Encrypted | 
                                    <i class="bi bi-arrow-counterclockwise"></i> 
                                    Money Back Guarantee
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Order Info -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-truck"></i> Shipping Information
                            </h6>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-1">
                                    <i class="bi bi-check text-success"></i> 
                                    Free shipping on orders over $100
                                </li>
                                <li class="mb-1">
                                    <i class="bi bi-check text-success"></i> 
                                    2-5 business days delivery
                                </li>
                                <li class="mb-1">
                                    <i class="bi bi-check text-success"></i> 
                                    Order tracking included
                                </li>
                                <li class="mb-0">
                                    <i class="bi bi-check text-success"></i> 
                                    30-day return policy
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="cart.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Cart
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>

    <?php if (!$success): ?>
    <!-- PayPal SDK with specific funding options -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_sandbox_client_id; ?>&currency=CAD&disable-funding=credit,card&enable-funding=paypal"></script>
    
    <script>
        // PayPal Button Configuration - PayPal Only
        paypal.Buttons({
            style: {
                layout: 'vertical',
                color: 'blue',
                shape: 'rect',
                label: 'paypal',
                height: 55
            },
            
            funding: {
                allowed: [paypal.FUNDING.PAYPAL],
                disallowed: [paypal.FUNDING.CREDIT, paypal.FUNDING.CARD]
            },
            
            // Create order
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo number_format($total, 2, '.', ''); ?>',
                            currency_code: 'CAD'
                        },
                        description: 'CompNest Computer Store Order',
                        custom_id: '<?php echo $_SESSION['user_id'] . '_' . time(); ?>'
                    }],
                    application_context: {
                        brand_name: 'CompNest Computer Store',
                        locale: 'en-CA',
                        landing_page: 'LOGIN',
                        shipping_preference: 'NO_SHIPPING',
                        user_action: 'PAY_NOW'
                    }
                });
            },
            
            // Handle successful payment
            onApprove: function(data, actions) {
                // Show loading overlay
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                return actions.order.capture().then(function(details) {
                    // Redirect to success page
                    window.location.href = 'checkout.php?paypal_success=1&payment_id=' + data.orderID;
                }).catch(function(error) {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    console.error('Payment capture failed:', error);
                    alert('Payment processing failed. Please try again.');
                });
            },
            
            // Handle errors
            onError: function(err) {
                console.error('PayPal Error:', err);
                alert('Payment failed. Please try again or contact support.');
                document.getElementById('paypal-button-container').innerHTML = 
                    '<div class="alert alert-danger">Payment failed. <button class="btn btn-primary btn-sm" onclick="location.reload()">Try Again</button></div>';
            },
            
            // Handle cancellation
            onCancel: function(data) {
                console.log('Payment cancelled by user');
            }
            
        }).render('#paypal-button-container').catch(function(err) {
            console.error('PayPal Buttons failed to render:', err);
            document.getElementById('paypal-button-container').innerHTML = 
                '<div class="alert alert-warning">PayPal failed to load. Please refresh the page.</div>';
        });

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
    <?php endif; ?>
</body>
</html>