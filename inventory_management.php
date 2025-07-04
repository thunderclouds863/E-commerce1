<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
  header("Location: index.php");
  exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Function for uploading images
function uploadImage($image)
{
    $targetDir = "uploads/";
    $imageFileType = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png"];

    // Check if the file type is allowed
    if (!in_array($imageFileType, $allowedTypes)) {
        return "error_type";
    }

    // Check file size (max 2MB)
    if ($image["size"] > 2 * 1024 * 1024) {
        return "error_size";
    }

    // Generate a unique file name (produk1, produk2, ...)
    $i = 1;
    $targetFile = $targetDir . "produk" . $i . "." . $imageFileType;

    // Check if a file with the generated name already exists, if so, increment the number
    while (file_exists($targetFile)) {
        $i++;
        $targetFile = $targetDir . "produk" . $i . "." . $imageFileType;
    }

    // Move the uploaded file to the target directory
    if (move_uploaded_file($image["tmp_name"], $targetFile)) {
        return $targetFile;
    }

    return "error_upload";
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_product') {
      $name = $_POST['name'];
      $description = $_POST['description'];
      $price = $_POST['price'];
      $stock = $_POST['stock'];

      $image = null;
      if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadImage($_FILES['image']);
      }

      $sql = "INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssdss", $name, $description, $price, $stock, $image);
      $stmt->execute();
    }

    if ($action === 'edit_product') {
      $id = $_POST['id'];
      $name = $_POST['name'];
      $description = $_POST['description'];
      $price = $_POST['price'];
      $stock = $_POST['stock'];
      $existingImage = $_POST['existing_image'];

      $image = $existingImage;
      if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadImage($_FILES['image']);
      }

      $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssdssi", $name, $description, $price, $stock, $image, $id);
      $stmt->execute();
    }
    if ($action === 'delete_product') {
      $id = $_POST['id'];
      $sql = "DELETE FROM products WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $id);
      $stmt->execute();
    }
  }
}

// Fetch products
$products = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fef5e7;
      margin: 0;
      padding: 0;
    }

    .container {
      padding: 20px;
    }

    h1,
    h2 {
      text-align: center;
      color: #ff8a65;
    }

    button {
      background-color: #4caf50;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #388e3c;
    }

    nav {
      background-color: #ff7043;
      color: white;
      padding: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    nav ul {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
    }

    nav ul li {
      margin: 0 10px;
    }

    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav ul li a:hover {
      background-color: #ff8a65;
    }

    .container {
      padding: 20px;
    }

    h1,
    h2 {
      text-align: center;
      color: #ff8a65;
    }

    button {
      background-color: #4caf50;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #388e3c;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    table th,
    table td {
      padding: 12px;
      border: 1px solid #ffccbc;
      text-align: center;
    }

    table th {
      background-color: #ffe0b2;
      color: #d84315;
    }

    /* Popup Styles */
    .popup {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .popup.active {
      display: flex;
    }

    .popup-content {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      width: 400px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .popup-content h3 {
      margin-bottom: 20px;
    }

    .popup-content label {
      display: block;
      text-align: left;
      margin: 10px 0;
    }

    .popup-content input,
    .popup-content textarea {
      width: 100%;
      padding: 8px;
      margin: 5px 0 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .popup-content button {
      margin-top: 10px;
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

    nav {
      background-color: #ff7043;
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    nav ul {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
    }

    nav ul li {
      margin: 0 15px;
    }
    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav div a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    nav ul li a:hover {
      background-color: #ff8a65;
    }

    nav div a:hover {
      background-color: #ff8a65;
    }
  </style>
</head>

<body>
  <nav>
    <div><a href="admin_dashboard.php">Admin Dashboard</a></div>
    <ul>
    <li><a href="admin_dashboard.php">Home</a></li>
      <li><a href="manajemen_user.php">User Management</a></li>
      <li><a href="inventory_management.php">Inventory</a></li>
      <li><a href="order-management.php">Orders</a></li>
      <li><a href="report.php">Report</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <main>
    <div class="container">
      <h2>Inventory Management</h2>

      <button onclick="togglePopup('add')">Add Product</button>

      <!-- Add Product Popup -->
      <div class="popup" id="popup-add">
        <div class="popup-content">
          <h3>Add Product</h3>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            <label>Name: <input type="text" name="name" required></label>
            <label>Description: <textarea name="description" required></textarea></label>
            <label>Price: <input type="number" name="price" step="0.01" required></label>
            <label>Stock: <input type="number" name="stock" required></label>
            <label>Image: <input type="file" name="image" accept="image/*"></label>
            <button type="submit">Save Product</button>
            <button type="button" onclick="togglePopup('add')" style="background-color: red;">Close</button>
          </form>
        </div>
      </div>

      <!-- Edit Product Popup -->
      <div class="popup" id="popup-edit">
        <div class="popup-content">
          <h3>Edit Product</h3>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="id" id="edit-id">
            <label>Name: <input type="text" name="name" id="edit-name" required></label>
            <label>Description: <textarea name="description" id="edit-description" required></textarea></label>
            <label>Price: <input type="number" name="price" id="edit-price" step="0.01" required></label>
            <label>Stock: <input type="number" name="stock" id="edit-stock" required></label>
            <input type="hidden" name="existing_image" id="edit-existing-image">
            <label>Image: <input type="file" name="image" accept="image/*"></label>
            <button type="submit">Save Changes</button>
            <button type="button" onclick="togglePopup('edit')" style="background-color: red;">Close</button>
          </form>
        </div>
      </div>

      <!-- Product List -->
      <h3>Product List</h3>
      <table>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Description</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Image</th>
          <th>Action</th>
        </tr>
        <?php while ($product = $products->fetch_assoc()): ?>
          <tr>
            <td><?= $product['id'] ?></td>
            <td><?= $product['name'] ?></td>
            <td><?= $product['description'] ?></td>
            <td><?= $product['price'] ?></td>
            <td><?= $product['stock'] ?></td>
            <td>
              <?php if ($product['image']): ?>
                <img src="<?= $product['image'] ?>" alt="Product Image" style="max-width: 100px;">
              <?php endif; ?>
            </td>
            <td>
              <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">Edit</button>
              <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <button type="submit" style="background-color: red;">Delete</button>
              </form>
            </td>

          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </main>
  <footer>
    <p>&copy; 2024 AS Berkah. All Rights Reserved.</p>
  </footer>

  <script>
    function togglePopup(type) {
      const popupAdd = document.getElementById('popup-add');
      const popupEdit = document.getElementById('popup-edit');
      if (type === 'add') popupAdd.classList.toggle('active');
      if (type === 'edit') popupEdit.classList.toggle('active');
    }

    function editProduct(product) {
      document.getElementById('edit-id').value = product.id;
      document.getElementById('edit-name').value = product.name;
      document.getElementById('edit-description').value = product.description;
      document.getElementById('edit-price').value = product.price;
      document.getElementById('edit-stock').value = product.stock;
      document.getElementById('edit-existing-image').value = product.image;
      togglePopup('edit');
    }
  </script>
</body>

</html>