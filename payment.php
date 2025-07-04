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

if (!isset($_SESSION['order_id']) || !isset($_SESSION['total_amount'])) {
    echo "<p>No pending payment found. Please proceed to checkout.</p>";
    exit;
}

$order_id = $_SESSION['order_id'];
$total_amount = $_SESSION['total_amount'];

// Generate unique payment code
$random_code = rand(1000000000, 9999999999999); // Generate 3-digit random number
$payment_amount = $total_amount + $random_code;

// Save payment code to database
$sql = "UPDATE orders SET payment_code = ?, status = 'awaiting payment' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $random_code, $order_id);
$stmt->execute();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding-bottom: 100px;
        }

        header, footer {
            background-color: #ff8a65;
            color: white;
            text-align: center;
            padding: 10px;
        }

        .payment-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .payment-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .payment-details {
            text-align: center;
            margin-bottom: 20px;
        }

        .payment-details p {
            margin: 10px 0;
        }

        .btn-pay {
            background-color: #ff7043;
            color: white;
            padding: 10px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
        }

        .btn-pay:hover {
            background-color: #ff5722;
        }
    </style>
</head>

<body>
    <header>
        <h1>Payment</h1>
    </header>

    <div class="payment-container">
        <h2>Complete Your Payment</h2>
        <div class="payment-details">
            <p>Order ID: <?php echo $order_id; ?></p>
            <p>Total Amount: Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></p>
            <p>Unique Payment Code: <strong><?php echo $random_code; ?></strong></p>
            <p><strong>Total to Pay: Rp <?php echo number_format($payment_amount, 0, ',', '.'); ?></strong></p>
        </div>
        <a href="confirm-payment.php?order_id=<?php echo $order_id; ?>" class="btn-pay">I Have Paid</a>
    </div>

    <footer>
        <p>&copy; 2024 AS Berkah E-Commerce. All Rights Reserved.</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>
