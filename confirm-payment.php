<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('customer');

$conn = db();
$user_id = intval($_SESSION['user_id']);

$order_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token. Please try again.');
        header("Location: order-history.php");
        exit();
    }
    $order_id = intval($_POST['order_id'] ?? 0);
} elseif (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
}

if ($order_id > 0) {
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ? AND user_id = ? AND status IN ('awaiting payment', 'Pending')");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $updated = $stmt->affected_rows > 0;
    $stmt->close();

    unset($_SESSION['order_id'], $_SESSION['total_amount']);

    if ($updated) {
        set_flash('success', 'Thank you! Payment for Order #' . $order_id . ' has been confirmed.');
    } else {
        set_flash('error', 'Order could not be confirmed or is already paid.');
    }
} else {
    set_flash('error', 'Invalid order ID.');
}

header("Location: order-history.php");
exit();
