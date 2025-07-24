<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $category = sanitize($_POST['category']);
        $stock = (int)$_POST['stock'];
        $image_url = sanitize($_POST['image_url']);
        
        if ($name && $description && $price > 0 && $category && $stock >= 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $category, $stock, $image_url])) {
                $message = 'Product added successfully!';
            } else {
                $error = 'Failed to add product.';
            }
        } else {
            $error = 'Please fill all required fields correctly.';
        }
    }
    
    if (isset($_POST['update_product'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $category = sanitize($_POST['category']);
        $stock = (int)$_POST['stock'];
        $image_url = sanitize($_POST['image_url']);
        
        if ($id && $name && $description && $price > 0 && $category && $stock >= 0) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, image_url=? WHERE id=?");
            if ($stmt->execute([$name, $description, $price, $category, $stock, $image_url, $id])) {
                $message = 'Product updated successfully!';
            } else {
                $error = 'Failed to update product.';
            }
        } else {
            $error = 'Please fill all required fields correctly.';
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Product deleted successfully!';
            } else {
                $error = 'Failed to delete product.';
            }
        }
    }
}

// Get all products
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM products $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT * FROM products $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$categories = ['laptops', 'desktops', 'graphic_cards', 'memories', 'accessories', 'storage'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
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
                    <a href="products.php" class="list-group-item list-group-item-action active">
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
                    <h2>Manage Products</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-square"></i> Add New Product
                    </button>
                </div>

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
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $cat)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo ucwords(str_replace('_', ' ', $product['category'])); ?></td>
                                        <td><?php echo formatPrice($product['price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'danger' : 'success'; ?>">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-product" 
                                                    data-product='<?php echo json_encode($product); ?>'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" class="form-control" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo ucwords(str_replace('_', ' ', $cat)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image_url" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3" id="edit_description" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" id="edit_price" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" class="form-control" name="stock" min="0" id="edit_stock" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category" id="edit_category" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo ucwords(str_replace('_', ' ', $cat)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image_url" id="edit_image_url" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', function() {
                const product = JSON.parse(this.dataset.product);
                document.getElementById('edit_id').value = product.id;
                document.getElementById('edit_name').value = product.name;
                document.getElementById('edit_description').value = product.description;
                document.getElementById('edit_price').value = product.price;
                document.getElementById('edit_stock').value = product.stock;
                document.getElementById('edit_category').value = product.category;
                document.getElementById('edit_image_url').value = product.image_url;
                
                new bootstrap.Modal(document.getElementById('editProductModal')).show();
            });
        });
    </script>
</body>
</html>