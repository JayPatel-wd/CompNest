<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=orders.php');
}
$stmt = $pdo->prepare("
    SELECT o.id, o.total_price, o.order_date, o.status,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Computer Store</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-clock-history"></i> Order History</h2>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-fill text-muted mb-3"></i>
                <h4>No orders yet</h4>
                <p>When you place your first order, it will appear here.</p>
                <a href="products.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($orders as $order): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Order #<?php echo $order['id']; ?></span>
                            <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Order Date</small>
                                    <p><?php echo date('M j, Y', strtotime($order['order_date'])); ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Amount</small>
                                    <p><strong><?php echo formatPrice($order['total_price']); ?></strong></p>
                                </div>
                            </div>
                            <p><small class="text-muted"><?php echo $order['item_count']; ?> item(s)</small></p>
                            <button class="btn btn-primary btn-sm view-order-details" 
                                    data-order-id="<?php echo $order['id']; ?>">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center">
                        <i class="bi bi-spinner bi-spin" style="font-size: 2rem;"></i>
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewButtons = document.querySelectorAll('.view-order-details');
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                document.getElementById('orderDetailsContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                modal.show();
                fetch(`get_order_details.php?id=${orderId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('orderDetailsContent').innerHTML = html;
                    })
                    .catch(error => {
                        document.getElementById('orderDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
                    });
            });
        });
    });
    </script>
</body>
</html>