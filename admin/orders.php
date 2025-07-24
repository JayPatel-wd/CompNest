<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    
    if ($order_id && in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $message = 'Order status updated successfully!';
        }
    }
}

// Get filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM orders o $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Get orders
$sql = "
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
 <nav class="navbar custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../images/logo.png" alt="CompNest - Admin" style="max-height: 50px;">
                CompNest -Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-house"></i> Back to Store
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="products.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-box"></i> Manage Products
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-cart"></i> View Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Manage Users
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2>Manage Orders</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $status_filter == $status ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No orders found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </td>
                                            <td><?php echo $order['item_count']; ?> items</td>
                                            <td><?php echo formatPrice($order['total_price']); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'delivered' ? 'success' :
                                                        ($order['status'] == 'cancelled' ? 'danger' :
                                                        ($order['status'] == 'shipped' ? 'info' :
                                                        ($order['status'] == 'processing' ? 'warning' : 'secondary')));
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-order-details" 
                                                        data-order-id="<?php echo $order['id']; ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success update-status" 
                                                        data-order-id="<?php echo $order['id']; ?>"
                                                        data-current-status="<?php echo $order['status']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status_select" class="form-select" required>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>"><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // View order details
        document.querySelectorAll('.view-order-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                
                document.getElementById('orderDetailsContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                
                fetch(`../get_order_details.php?id=${orderId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('orderDetailsContent').innerHTML = html;
                    })
                    .catch(error => {
                        document.getElementById('orderDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
                    });
            });
        });

        // Update status
        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const currentStatus = this.dataset.currentStatus;
                
                document.getElementById('status_order_id').value = orderId;
                document.getElementById('status_select').value = currentStatus;
                
                new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
            });
        });
    </script>
</body>
</html>