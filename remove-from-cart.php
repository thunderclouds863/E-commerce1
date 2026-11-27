<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        set_flash('success', 'Item removed from your cart.');
    }
}

header("Location: shopping-cart.php");
exit();
