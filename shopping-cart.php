<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle remove from cart
if (isset($_GET['remove_from_cart'])) {
    $product_id = intval($_GET['remove_from_cart']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        set_flash('success', 'Item removed from your cart.');
    }
    header("Location: shopping-cart.php");
    exit();
}

// Handle quantity change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_quantity']) || isset($_POST['quantity']))) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid request token. Please try again.');
        header("Location: shopping-cart.php");
        exit();
    }

    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        $adjusted = false;
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $pid = intval($product_id);
            $qty = intval($quantity);
            if (!isset($_SESSION['cart'][$pid])) {
                continue;
            }
            if ($qty <= 0) {
                unset($_SESSION['cart'][$pid]);
                continue;
            }
            $stmt = $conn->prepare("SELECT name, stock FROM products WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$prod || intval($prod['stock']) <= 0) {
                unset($_SESSION['cart'][$pid]);
                $adjusted = true;
            } elseif ($qty > intval($prod['stock'])) {
                $_SESSION['cart'][$pid]['quantity'] = intval($prod['stock']);
                set_flash('error', 'Adjusted "' . $prod['name'] . '" quantity to available stock (' . intval($prod['stock']) . ').');
                $adjusted = true;
            } else {
                $_SESSION['cart'][$pid]['quantity'] = $qty;
            }
        }
        if (!$adjusted) {
            set_flash('success', 'Cart quantities updated.');
        }
    }
    header("Location: shopping-cart.php");
    exit();
}

// Build cart display items
$cart_display = [];
$subtotal = 0;
$total_units = 0;

foreach ($_SESSION['cart'] as $product_id => $item) {
    $pid = intval($product_id);
    if ($pid <= 0 || !is_array($item) || !isset($item['quantity'])) {
        unset($_SESSION['cart'][$product_id]);
        continue;
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
        $stock = intval($product['stock']);
        $qty = min(max(1, intval($item['quantity'])), max(1, $stock));
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
            'id' => $pid,
            'name' => $product['name'],
            'price' => $product['price'],
            'stock' => $stock,
            'quantity' => $qty,
            'line_total' => $line_total,
            'image' => $imgSrc,
        ];
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    $stmt->close();
}

$flash = get_flash();
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
                <a href="customer_dashboard.php">Catalog</a>
                <a href="shopping-cart.php" class="active">
                    Cart
                    <?php if ($total_units > 0): ?>
                        <span class="nav-badge"><?php echo $total_units; ?></span>
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
                                <img class="cart-row-img" src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                            <?php else: ?>
                                <div class="cart-row-img" style="display:flex;align-items:center;justify-content:center;color:var(--ink-muted);font-size:12px;">No img</div>
                            <?php endif; ?>

                            <div class="cart-row-info">
                                <div class="cart-row-title"><?php echo e($item['name']); ?></div>
                                <div class="cart-row-unit tabular-nums">
                                    <?php echo format_idr($item['price']); ?> each
                                    &middot;
                                    <strong><?php echo format_idr($item['line_total']); ?></strong>
                                    &middot;
                                    <span style="color:var(--ink-muted);"><?php echo $item['stock']; ?> available</span>
                                </div>
                            </div>

                            <div class="cart-row-controls">
                                <form action="shopping-cart.php" method="POST" style="display:inline-flex;align-items:center;gap:8px;margin:0;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="update_quantity" value="1">
                                    <input class="form-control qty-input" type="number" name="quantity[<?php echo $item['id']; ?>]"
                                           value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo max(1, $item['stock']); ?>"
                                           aria-label="Quantity for <?php echo e($item['name']); ?>"
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
                        <span class="tabular-nums"><?php echo format_idr($subtotal); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Shipping</span>
                        <span>Free standard delivery</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="summary-total-amount"><?php echo format_idr($subtotal); ?></span>
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
