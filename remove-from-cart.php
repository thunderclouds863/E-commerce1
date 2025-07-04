<?php
session_start();

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Remove the product from the cart
    if (($key = array_search($product_id, $_SESSION['cart'])) !== false) {
        unset($_SESSION['cart'][$key]);
    }

    // Redirect back to the shopping cart page
    header("Location: shopping-cart.php");
    exit;
}
?>
