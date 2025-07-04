<?php
$order_id = $_GET['order_id']; // Mendapatkan ID pesanan dari URL
$new_status = $_GET['status']; // Mendapatkan status baru

// Mengupdate status pesanan di database
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_status, $order_id);
$stmt->execute();

// Redirect ke halaman order history setelah update
header("Location: order-history.php");
exit;
?>
