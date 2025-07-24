<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['toggle_admin'])) {
        $user_id = (int)$_POST['user_id'];
        $is_admin = (int)$_POST['is_admin'];
        
        if ($user_id && $user_id != $_SESSION['user_id']) { // Can't change own admin status
            $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            if ($stmt->execute([$is_admin, $user_id])) {
                $message = 'User privileges updated successfully!';
            } else {
                $error = 'Failed to update user privileges.';
            }
        } else {
            $error = 'Cannot modify your own admin privileges.';
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        if ($user_id && $user_id != $_SESSION['user_id']) { // Can't delete own account
            try {
                $pdo->beginTransaction();
                
                // Delete user's cart items
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Note: We keep orders for record keeping, just delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                $message = 'User deleted successfully!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to delete user.';
            }
        } else {
            $error = 'Cannot delete your own account.';
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$admin_filter = isset($_GET['admin_filter']) ? $_GET['admin_filter'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($admin_filter !== '') {
    $where_conditions[] = "is_admin = ?";
    $params[] = $admin_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users with order statistics
$sql = "
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(o.total_price), 0) as total_spent,
           MAX(o.order_date) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cart"></i> View Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person"></i> Manage Users
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2>Manage Users</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="admin_filter" class="form-select">
                                    <option value="">All Users</option>
                                    <option value="1" <?php echo $admin_filter === '1' ? 'selected' : ''; ?>>Admins Only</option>
                                    <option value="0" <?php echo $admin_filter === '0' ? 'selected' : ''; ?>>Regular Users</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="users.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Last Order</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($user['is_admin']): ?>
                                                    <span class="badge bg-danger">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $user['total_orders']; ?></td>
                                            <td><?php echo formatPrice($user['total_spent']); ?></td>
                                            <td>
                                                <?php if ($user['last_order_date']): ?>
                                                    <?php echo date('M j, Y', strtotime($user['last_order_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                                data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="is_admin" value="<?php echo $user['is_admin'] ? 0 : 1; ?>">
                                                                    <button type="submit" name="toggle_admin" class="dropdown-item">
                                                                        <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" class="d-inline" 
                                                                      onsubmit="return confirm('Delete this user? This action cannot be undone.')">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" name="delete_user" class="dropdown-item text-danger">
                                                                        Delete User
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&admin_filter=<?php echo urlencode($admin_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">User Statistics</h5>
                                <p>Total Users: <strong><?php echo $total_users; ?></strong></p>
                                <?php
                                $admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
                                $regular_count = $total_users - $admin_count;
                                ?>
                                <p>Admins: <strong><?php echo $admin_count; ?></strong></p>
                                <p>Regular Users: <strong><?php echo $regular_count; ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Registrations</h5>
                                <?php
                                $recent_users = $pdo->query("
                                    SELECT name, created_at 
                                    FROM users 
                                    ORDER BY created_at DESC 
                                    LIMIT 5
                                ")->fetchAll();
                                ?>
                                <?php foreach ($recent_users as $recent): ?>
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($recent['name']); ?></span>
                                        <small class="text-muted"><?php echo date('M j', strtotime($recent['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>