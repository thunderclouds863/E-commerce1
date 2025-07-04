<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_GET['add_to_cart'])) {
    $product_id = $_GET['add_to_cart'];

    // Check stock before adding
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    if ($stock > 0) {
        // Add to cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity']++;
        } else {
            $_SESSION['cart'][$product_id] = ['quantity' => 1];
        }

        // Decrease stock in the database
        $stmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Product added to cart successfully.');</script>";
    } else {
        echo "<script>alert('Sorry, this product is out of stock.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Peminjaman Alat</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fdf6ec;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #ff7043;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .logo {
            display: flex;
            align-items: center;
        }

        header .logo img {
            height: 50px;
            margin-right: 10px;
        }

        header .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 600;
        }

        header .navbar a:hover {
            text-decoration: underline;
        }

        .container {
            width: 90%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 60%;
            padding: 10px;
            border-radius: 25px;
            border: 1px solid #ccc;
            outline: none;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background-color: #ff7043;
            color: white;
        }

        table th, table td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .action-button {
            background-color: #ff7043;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .action-button:hover {
            background-color: #ff5722;
        }

        footer {
            background-color: #ff7043;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Logo">
            <h1 style="font-size: 20px; font-weight: 600;">Sistem Informasi Peminjaman Alat</h1>
        </div>
        <nav class="navbar">
            <a href="home.php"><i class="fas fa-home"></i> Home</a>
            <a href="#"><i class="fas fa-box"></i> Items</a>
            <a href="shopping-cart.php"><i class="fas fa-shopping-cart"></i> Peminjaman</a>
            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2><i class="fas fa-list"></i> Available Products</h2>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search products...">
        </div>
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> No</th>
                    <th><i class="fas fa-tools"></i> Nama Alat</th>
                    <th><i class="fas fa-tags"></i> Jenis Alat</th>
                    <th><i class="fas fa-money-bill-wave"></i> Harga</th>
                    <th><i class="fas fa-cart-plus"></i> Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?>/Minggu</td>
                    <td>
                        <?php if ($row['stock'] > 0) { ?>
                            <a href="?add_to_cart=<?php echo $row['id']; ?>" class="action-button"><i class="fas fa-cart-plus"></i> Add to Cart</a>
                        <?php } else { ?>
                            <button class="action-button" disabled>Out of Stock</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2024 <i class="fas fa-cogs"></i> Sistem Informasi Peminjaman Alat</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>
