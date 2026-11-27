<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart (POST preferred with CSRF; GET kept for backward compatibility)
$add_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (verify_csrf()) {
        $add_id = intval($_POST['add_to_cart']);
    } else {
        set_flash('error', 'Invalid request token. Please try again.');
        header("Location: customer_dashboard.php");
        exit();
    }
} elseif (isset($_GET['add_to_cart'])) {
    $add_id = intval($_GET['add_to_cart']);
}

if ($add_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $add_id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prod) {
        set_flash('error', 'Product not found.');
    } else {
        $current_qty = isset($_SESSION['cart'][$add_id]['quantity']) ? intval($_SESSION['cart'][$add_id]['quantity']) : 0;
        if ($current_qty + 1 > intval($prod['stock'])) {
            set_flash('error', 'Cannot add more "' . $prod['name'] . '" — only ' . intval($prod['stock']) . ' in stock.');
        } else {
            $_SESSION['cart'][$add_id] = ['quantity' => $current_qty + 1];
            set_flash('success', '"' . $prod['name'] . '" added to your cart.');
        }
    }
    header("Location: customer_dashboard.php");
    exit();
}

$result = $conn->query("SELECT * FROM products ORDER BY id ASC");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$flash = get_flash();
$cart_count = cart_total_units();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storefront — AS Berkah E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php if ($flash): ?>
        <div class="toast-banner toast-banner--<?php echo e($flash['type']); ?>" data-toast role="status">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <header class="site-header">
        <div class="header-inner">
            <a href="customer_dashboard.php" class="brand-link">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </span>
                <span class="brand-text">
                    <span class="brand-name">AS Berkah</span>
                    <span class="brand-sub">Storefront</span>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <nav class="nav-links" data-nav-links aria-label="Customer navigation">
                <a href="customer_dashboard.php" class="active">Catalog</a>
                <a href="shopping-cart.php">
                    Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="nav-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="order-history.php">Orders</a>
                <a href="account.php">Account</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Collection</div>
                <h1 class="page-title">Available Products</h1>
                <p class="page-subtitle">Browse curated everyday apparel and accessories ready to ship.</p>
            </div>
            <?php if ($cart_count > 0): ?>
                <a href="shopping-cart.php" class="btn btn-primary">
                    View Cart (<?php echo $cart_count; ?>)
                </a>
            <?php endif; ?>
        </div>

        <div class="catalog-toolbar">
            <div class="search-input-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" class="form-control" data-product-search placeholder="Search by product name or details..." aria-label="Search products">
            </div>

            <div class="toolbar-controls">
                <span class="toolbar-count" data-product-count><?php echo count($products); ?> products</span>
                <select class="form-select" data-product-sort aria-label="Sort products" style="width:auto;min-width:170px;">
                    <option value="default">Sort: Featured</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="name-asc">Name: A–Z</option>
                </select>
            </div>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid" data-product-grid>
                <?php foreach ($products as $idx => $row):
                    $imgSrc = '';
                    if (!empty($row['image']) && is_string($row['image']) && file_exists($row['image'])) {
                        $imgSrc = $row['image'];
                    } elseif (file_exists('uploads/produk' . $row['id'] . '.png')) {
                        $imgSrc = 'uploads/produk' . $row['id'] . '.png';
                    }
                    $stock = intval($row['stock']);
                    $inStock = $stock > 0;
                    $lowStock = $inStock && $stock <= 10;
                ?>
                    <article class="product-card"
                             data-product-card
                             data-index="<?php echo $idx; ?>"
                             data-name="<?php echo e($row['name']); ?>"
                             data-desc="<?php echo e($row['description'] ?? ''); ?>"
                             data-price="<?php echo floatval($row['price']); ?>">
                        <div class="product-media">
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?php echo e($imgSrc); ?>" alt="<?php echo e($row['name']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="product-media-placeholder">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <span>No image</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!$inStock): ?>
                                <span class="badge-pill badge-danger product-stock-tag">Out of Stock</span>
                            <?php elseif ($lowStock): ?>
                                <span class="badge-pill badge-warning product-stock-tag"><?php echo $stock; ?> left</span>
                            <?php else: ?>
                                <span class="badge-pill badge-success product-stock-tag">In Stock &middot; <?php echo $stock; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h2 class="product-name"><?php echo e($row['name']); ?></h2>
                            <p class="product-desc"><?php echo e($row['description'] ?: 'Quality everyday product from AS Berkah.'); ?></p>

                            <div class="product-footer">
                                <div class="product-price"><?php echo format_idr($row['price']); ?></div>
                                <?php if ($inStock): ?>
                                    <form method="POST" style="margin:0;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="add_to_cart" value="<?php echo intval($row['id']); ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm" disabled>Unavailable</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="surface-card empty-state" data-search-empty style="display:none;margin-top:20px;">
                <h3>No matching products</h3>
                <p>Try a different keyword or clear your search filter.</p>
            </div>
        <?php else: ?>
            <div class="surface-card empty-state">
                <h3>Catalog is currently empty</h3>
                <p>Check back soon for new arrivals from AS Berkah.</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
            <span>Customer Storefront</span>
        </div>
    </footer>

    <script src="assets/js/ui.js"></script>
</body>

</html>
