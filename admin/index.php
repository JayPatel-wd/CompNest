<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get dashboard statistics
$stats = [];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetchColumn();

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT SUM(total_price) FROM orders");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Monthly revenue (current month)
$stmt = $pdo->query("
    SELECT SUM(total_price) FROM orders 
    WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(order_date) = YEAR(CURRENT_DATE())
");
$stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;

// Recent orders
$stmt = $pdo->query("
    SELECT o.id, o.total_price, o.order_date, o.status, u.name as user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $pdo->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
$low_stock = $stmt->fetchAll();

// Sales by category (top 5)
$stmt = $pdo->query("
    SELECT p.category, SUM(oi.quantity * oi.price) as total_sales, COUNT(oi.id) as items_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.category
    ORDER BY total_sales DESC
    LIMIT 5
");
$category_sales = $stmt->fetchAll();

// Recent user registrations
$stmt = $pdo->query("
    SELECT name, email, created_at 
    FROM users 
    WHERE is_admin = 0
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();
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
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .low-stock {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .dashboard-widget {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../images/logo.png" alt="CompNest - Admin" style="max-height: 50px;">
                CompNest - Admin
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></small>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_products']; ?></h4>
                                        <p class="mb-0">Total Products</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo formatPrice($stats['total_revenue']); ?></h4>
                                        <p class="mb-0">Total Revenue</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_users']; ?></h4>
                                        <p class="mb-0">Total Users</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-person" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_orders']; ?></h4>
                                        <p class="mb-0">Total Orders</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-bag" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1">This Month's Revenue</h5>
                                        <h3 class="text-success"><?php echo formatPrice($stats['monthly_revenue']); ?></h3>
                                    </div>
                                    <div class="text-end">
                                        <i class="bi bi-graph-up text-success" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-6 dashboard-widget">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <p class="text-muted">No orders yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Order</th>
                                                    <th>Customer</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $order['status'] == 'delivered' ? 'success' :
                                                                ($order['status'] == 'cancelled' ? 'danger' :
                                                                ($order['status'] == 'shipped' ? 'info' :
                                                                ($order['status'] == 'processing' ? 'warning' : 'secondary')));
                                                        ?> bg-opacity-10 text-<?php 
                                                            echo $order['status'] == 'delivered' ? 'success' :
                                                                ($order['status'] == 'cancelled' ? 'danger' :
                                                                ($order['status'] == 'shipped' ? 'info' :
                                                                ($order['status'] == 'processing' ? 'warning' : 'secondary')));
                                                        ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="col-md-6 dashboard-widget">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Low Stock Alert
                                </h5>
                                <a href="products.php" class="btn btn-sm btn-outline-warning">Manage</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($low_stock)): ?>
                                    <p class="text-success mb-0">
                                        <i class="bi bi-check-circle"></i> All products are well stocked!
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($low_stock as $product): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 low-stock rounded">
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo ucwords(str_replace('_', ' ', $product['category'])); ?></small>
                                        </div>
                                        <span class="badge bg-danger"><?php echo $product['stock']; ?> left</span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Sales by Category -->
                    <div class="col-md-6 dashboard-widget">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Categories by Sales</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($category_sales)): ?>
                                    <p class="text-muted">No sales data available.</p>
                                <?php else: ?>
                                    <?php foreach ($category_sales as $category): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <strong><?php echo ucwords(str_replace('_', ' ', $category['category'])); ?></strong><br>
                                            <small class="text-muted"><?php echo $category['items_sold']; ?> items sold</small>
                                        </div>
                                        <span class="h6 mb-0 text-success"><?php echo formatPrice($category['total_sales']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent User Registrations -->
                    <div class="col-md-6 dashboard-widget">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">New Customers</h5>
                                <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_users)): ?>
                                    <p class="text-muted">No new registrations.</p>
                                <?php else: ?>
                                    <?php foreach ($recent_users as $user): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j', strtotime($user['created_at'])); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="products.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-plus-square"></i> Add Product
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="orders.php?status=pending" class="btn btn-outline-warning w-100">
                                            <i class="bi bi-clock"></i> Pending Orders
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="users.php" class="btn btn-outline-info w-100">
                                            <i class="bi bi-person-plus"></i> Manage Users
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="products.php?search=stock" class="btn btn-outline-danger w-100">
                                            <i class="bi bi-exclamation-triangle"></i> Check Stock
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh stats every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>