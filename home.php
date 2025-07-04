<?php
session_start();

// Periksa peran pengguna
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Peminjam Alat') {
    header("Location: index.php");
    exit;
}

// Konfigurasi koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peminjaman_alat";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil barang yang sedang dipinjam oleh pengguna
$user_id = $_SESSION['user_id'];
$sql = "SELECT
            p.id AS peminjaman_id,
            i.name AS item_name,
            i.description,
            d.price AS item_price,
            d.week AS duration_weeks,
            p.created_at AS borrow_date,
            DATE_ADD(p.created_at, INTERVAL d.week WEEK) AS return_date
        FROM peminjaman p
        JOIN detail_peminjaman d ON p.id = d.peminjaman_id
        JOIN item i ON d.item_id = i.id
        WHERE p.user_id = ? AND p.status = 'Sedang Dipinjam'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Sistem Informasi Peminjaman Alat</title>
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
            font-size: 20px;
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

        .table {
            margin-top: 20px;
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
            <a href="shopping-cart.php"><i class="fas fa-shopping-cart"></i> Peminjaman</a>
            <a href="order-history.php"><i class="fas fa-history"></i> History</a>
            <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2><i class="fas fa-box"></i> Dashboard Peminjaman</h2>
        <table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th><i class="fas fa-toolbox"></i> Nama Barang</th>
            <th><i class="fas fa-info-circle"></i> Deskripsi</th>
            <th><i class="fas fa-dollar-sign"></i> Harga</th>
            <th><i class="fas fa-sort-numeric-up-alt"></i> Jumlah</th>
            <th><i class="fas fa-calendar-alt"></i> Tanggal Pinjam</th>
            <th><i class="fas fa-calendar-check"></i> Tanggal Kembali</th>
            <th><i class="fas fa-eye"></i> Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while ($row = $result->fetch_assoc()) {
        ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo date("d M Y, H:i", strtotime($row['borrow_date'])); ?></td>
                <td><?php echo date("d M Y, H:i", strtotime($row['return_date'])); ?></td>
                <td>
                    <a href="order-details.php?order_id=<?php echo $row['order_id']; ?>" class="action-button">
                        <i class="fas fa-info-circle"></i> Detail
                    </a>
                </td>
            </tr>
        <?php
        }
        ?>
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
