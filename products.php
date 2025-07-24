<?php
require_once 'config.php';

// Get filter parameters
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($category) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM products $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT * FROM products $where_clause ORDER BY name LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = $categories_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Computer Store</title>
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Filters</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Categories -->
                        <h6>Categories</h6>
                        <ul class="list-unstyled">
                            <li><a href="products.php" class="text-decoration-none 
                                <?php echo !$category ? 'fw-bold' : ''; ?>" style="color: #01344d;">All Products</a></li>
                            <?php foreach ($categories as $cat): ?>
                            <li><a href="products.php?category=<?php echo urlencode($cat['category']); ?>" 
                                   class="text-decoration-none <?php echo $category == $cat['category'] ? 'fw-bold' : ''; ?>" style="color: #01344d;">
                                <?php echo ucwords(str_replace('_', ' ', $cat['category'])); ?>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <?php 
                        if ($category) {
                            echo ucwords(str_replace('_', ' ', $category));
                        } elseif ($search) {
                            echo "Search Results for: " . htmlspecialchars($search);
                        } else {
                            echo "All Products";
                        }
                        ?>
                    </h2>
                    <span class="text-muted"><?php echo $total_products; ?> products found</span>
                </div>

                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
                        <h4>No products found</h4>
                        <p>Try adjusting your search or filter criteria.</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text flex-grow-1">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="price h5 mb-0"><?php echo formatPrice($product['price']); ?></span>
                                        <div>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">View</a>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-primary btn-sm add-to-cart" 
                                                        data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2">
                                        <?php echo $product['stock'] > 0 ? "In Stock ({$product['stock']})" : "Out of Stock"; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Product pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/script.js"></script>
</body>
</html>