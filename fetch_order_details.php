<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id']);
    exit();
}

$orderId = intval($_POST['order_id']);
$conn = db();
$response = [];

$sql = "SELECT
            o.address AS shipping_address,
            o.payment_method,
            o.total_amount,
            u.username AS customer_name,
            u.created_at AS customer_created_at
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        WHERE o.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $response = $row;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}
$stmt->close();

$sql = "SELECT
            p.name AS product_name,
            oi.quantity,
            oi.price
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$response['order_items'] = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($response);
