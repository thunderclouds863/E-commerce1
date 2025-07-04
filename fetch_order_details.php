<?php
if (isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ecommerce_db";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $response = [];

    // Fetch customer and order details
    $sql = "SELECT
                o.address AS shipping_address,
                o.payment_method,
                o.total_amount,
                u.username AS customer_name,
                u.created_at AS customer_created_at
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response = $row;
    }

    // Fetch order items
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

    // Send JSON response
    echo json_encode($response);

    // Cleanup
    $stmt->close();
    $conn->close();
}
?>
