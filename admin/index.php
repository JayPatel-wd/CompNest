<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT SUM(total_price) FROM orders");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("
    SELECT o.id, o.total_price, o.order_date, u.name as user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();
$stmt = $pdo->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
$low_stock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Computer Store</title>
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
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="products.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-box"></i> Manage Products
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cart"></i> View Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Manage Users
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2>Dashboard</h2>
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_products']; ?></h4>
                                        <p>Total Products</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-cart" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo formatPrice($stats['total_revenue']); ?></h4>
                                        <p>Total Revenue</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                 <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_users']; ?></h4>
                                        <p>Total Users</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-person" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_orders']; ?></h4>
                                        <p>Total Orders</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <p>No orders yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Total</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>