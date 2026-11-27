<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['admin', 'employee', 'courier']);

$conn = db();
$order_id = intval($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$new_status = trim($_POST['status'] ?? $_GET['status'] ?? '');

$allowed_statuses = ['Pending', 'Dikonfirmasi', 'Dikemas', 'Siap Dikirim', 'Dikirim', 'Sampai Tujuan', 'Selesai'];

if ($order_id > 0 && in_array($new_status, $allowed_statuses, true)) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
}

$redirect = $_SESSION['role'] === 'admin' ? 'order-management.php' : ($_SESSION['role'] . '_dashboard.php');
header("Location: " . $redirect);
exit();
