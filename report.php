<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
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

// Fetch completed orders
$sql = "SELECT * FROM orders WHERE status = 'Selesai'";
$result = $conn->query($sql);


$total_sales = 0;
if ($result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        $total_sales += $order['total_amount'];
    }

    $result->data_seek(0);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
        }

        header div a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            position: left;
        }

        .container {
            margin-top: 30px;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 30px;
        }

        .summary-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-card h3 {
            margin: 0;
            font-size: 24px;
        }

        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
    </style>
</head>

<body>
    <header>
        <div><a href="admin_dashboard.php">Admin Dashboard</a></div>
        <h1 class="bg-dark text-white text-center py-3">Admin Sales Report</h1>
    </header>

    <div class="container mt-4">
        <!-- Sales Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders Completed</h5>
                        <p class="card-text fs-4"><?php echo $result->num_rows; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <p class="card-text text-success fs-4">Rp
                            <?php echo number_format($total_sales, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="mt-4">
            <table id="salesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()) {
                        $total_sales += $order['total_amount'];
                        ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm view-details"
                                    data-order-id="<?php echo $order['id']; ?>">View Details</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>Customer Information</h5>
                    <p id="customerInfo"></p>
                    <h5>Order Details</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody id="orderDetails">
                            <!-- AJAX-loaded content -->
                        </tbody>
                    </table>
                    <h5>Shipping Address</h5>
                    <p id="shippingAddress"></p>
                    <h5>Payment Method</h5>
                    <p id="paymentMethod"></p>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 AS Berkah E-Commerce. All Rights Reserved.</p>
    </footer>
    <!-- Bootstrap & DataTables Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#salesTable').DataTable();

            // Handle "View Details" button click
            $('.view-details').on('click', function () {
                const orderId = $(this).data('order-id');
                $.ajax({
                    url: 'fetch_order_details.php',
                    method: 'POST',
                    data: { order_id: orderId },
                    dataType: 'json',
                    success: function (data) {
                        $('#customerInfo').text(`${data.customer_name} (${data.customer_email})`);
                        $('#shippingAddress').text(data.shipping_address);
                        $('#paymentMethod').text(data.payment_method);
                        let orderDetailsHTML = '';
                        data.order_items.forEach(item => {
                            orderDetailsHTML += `<tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>Rp ${item.price}</td>
                            </tr>`;
                        });
                        $('#orderDetails').html(orderDetailsHTML);
                        $('#detailModal').modal('show');
                    },
                    error: function () {
                        alert('Failed to fetch order details.');
                    }
                });
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>