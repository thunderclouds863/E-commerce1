<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    if ($product_id > 0) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = ['quantity' => 0];
        }
        $_SESSION['cart'][$product_id]['quantity']++;
    }
}

// Handle remove from cart
if (isset($_GET['remove_from_cart'])) {
    $product_id = intval($_GET['remove_from_cart']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Handle quantity change
if (isset($_POST['update_quantity']) || isset($_POST['quantity'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if (isset($_SESSION['cart'][$product_id])) {
                $quantity = intval($quantity);
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                }
            }
        }
    }
}

// Build cart display items
$cart_display = [];
$subtotal = 0;
$total_units = 0;

foreach ($_SESSION['cart'] as $product_id => $item) {
    if ($product_id <= 0) {
        unset($_SESSION['cart'][$product_id]);
        continue;
    }
    if (is_array($item) && isset($item['quantity'])) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $product = $res->fetch_assoc();
            $qty = intval($item['quantity']);
            $line_total = $product['price'] * $qty;
            $subtotal += $line_total;
            $total_units += $qty;

            $imgSrc = '';
            if (!empty($product['image']) && is_string($product['image']) && file_exists($product['image'])) {
                $imgSrc = $product['image'];
            } elseif (file_exists('uploads/produk' . $product['id'] . '.png')) {
                $imgSrc = 'uploads/produk' . $product['id'] . '.png';
            }

            $cart_display[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $qty,
                'line_total' => $line_total,
                'image' => $imgSrc,
            ];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart — AS Berkah E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
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
                <a href="customer_dashboard.php">Catalog</a>
                <a href="shopping-cart.php" class="active">
                    Cart
                    <?php if ($total_units > 0): ?>
                        <span class="nav-badge"><?php echo $total_units; ?></span>
                    <?php endif; ?>
                </a>
                <a href="order-history.php">Orders</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Your Bag</div>
                <h1 class="page-title">Shopping Cart</h1>
                <p class="page-subtitle">Review your selected items and adjust quantities before checkout.</p>
            </div>
            <a href="customer_dashboard.php" class="btn btn-secondary">Continue Shopping</a>
        </div>

        <?php if (empty($cart_display)): ?>
            <div class="surface-card empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <h3>Your cart is empty</h3>
                <p>Add products from the catalog to get started with your order.</p>
                <a href="customer_dashboard.php" class="btn btn-primary">Browse Catalog</a>
            </div>
        <?php else: ?>
            <div class="split-layout">
                <div class="cart-list">
                    <?php foreach ($cart_display as $item): ?>
                        <div class="cart-row">
                            <?php if ($item['image'] !== ''): ?>
                                <img class="cart-row-img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class="cart-row-img" style="display:flex;align-items:center;justify-content:center;color:var(--ink-muted);font-size:12px;">No img</div>
                            <?php endif; ?>

                            <div class="cart-row-info">
                                <div class="cart-row-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-row-unit tabular-nums">
                                    Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> each
                                    &middot;
                                    <strong>Rp <?php echo number_format($item['line_total'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>

                            <div class="cart-row-controls">
                                <form action="shopping-cart.php" method="POST" style="display:inline-flex;align-items:center;gap:8px;margin:0;">
                                    <input type="hidden" name="update_quantity" value="1">
                                    <input class="form-control qty-input" type="number" name="quantity[<?php echo $item['id']; ?>]"
                                           value="<?php echo $item['quantity']; ?>" min="1" max="99"
                                           aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>"
                                           onchange="this.form.submit()">
                                </form>
                                <a href="shopping-cart.php?remove_from_cart=<?php echo $item['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Remove this item from your cart?');">Remove</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <aside class="summary-card">
                    <h2 class="summary-title">Order Summary</h2>
                    <div class="summary-line">
                        <span>Items (<?php echo $total_units; ?>)</span>
                        <span class="tabular-nums">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="summary-total-amount">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-lg btn-block" style="margin-top:20px;">Proceed to Checkout</a>
                </aside>
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

<?php
$conn->close();
?>
