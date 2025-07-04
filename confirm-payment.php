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

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    // Update order status to "paid"
    $sql = "UPDATE orders SET status = 'paid' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Clear session order data
    unset($_SESSION['order_id']);
    unset($_SESSION['total_amount']);

    echo "<script>alert('Thank you! Your payment has been confirmed.'); window.location.href = 'order-history.php';</script>";
    exit;
} else {
    echo "<p>Invalid order ID. Please try again.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment</title>
</head>

<body>
    <h1>Invalid Request</h1>
</body>

</html>

<?php
$conn->close();
?>
