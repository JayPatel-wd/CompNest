<?php
require_once 'config.php';

if (!isLoggedIn() || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$order_id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT o.*, oi.quantity, oi.price, p.name, p.image_url
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_items = $stmt->fetchAll();

if (empty($order_items)) {
    exit('<div class="alert alert-danger">Order not found.</div>');
}

$order = $order_items[0]; 
?>

<div class="order-details">
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Order #<?php echo $order['id']; ?></strong><br>
            <small class="text-muted">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></small>
        </div>
        <div class="col-md-6 text-end">
            <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?> fs-6">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </div>
    </div>

    <h6>Order Items</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 class="me-2" style="width: 40px; height: 40px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                    </td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatPrice($item['price']); ?></td>
                    <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th><?php echo formatPrice($order['total_price']); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>