<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$conn = db();

function uploadProductImage(array $image): array
{
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = strtolower(pathinfo($image["name"] ?? '', PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "webp"];
    if (!in_array($ext, $allowedTypes, true)) {
        return ['error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    if (($image["size"] ?? 0) > 2 * 1024 * 1024) {
        return ['error' => 'Image size must not exceed 2 MB.'];
    }

    if (empty($image["tmp_name"]) || @getimagesize($image["tmp_name"]) === false) {
        return ['error' => 'Uploaded file is not a valid image.'];
    }

    $i = 1;
    $targetFile = $targetDir . "produk" . $i . "." . $ext;
    while (file_exists($targetFile)) {
        $i++;
        $targetFile = $targetDir . "produk" . $i . "." . $ext;
    }

    if (move_uploaded_file($image["tmp_name"], $targetFile)) {
        return ['path' => $targetFile];
    }

    return ['error' => 'Failed to save uploaded image.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        header("Location: inventory_management.php");
        exit();
    }

    $action = $_POST['action'];

    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if ($name === '' || $price < 0 || $stock < 0) {
            set_flash('error', 'Please enter a valid product name, non-negative price, and non-negative stock.');
            header("Location: inventory_management.php");
            exit();
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadProductImage($_FILES['image']);
            if (isset($upload['error'])) {
                set_flash('error', $upload['error']);
                header("Location: inventory_management.php");
                exit();
            }
            $imagePath = $upload['path'];
        }

        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $description, $price, $stock, $imagePath);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Product "' . $name . '" added.');
        header("Location: inventory_management.php");
        exit();
    }

    if ($action === 'edit_product') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $existingImage = trim($_POST['existing_image'] ?? '');

        if ($id <= 0 || $name === '' || $price < 0 || $stock < 0) {
            set_flash('error', 'Invalid product fields.');
            header("Location: inventory_management.php");
            exit();
        }

        $imagePath = $existingImage !== '' ? $existingImage : null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadProductImage($_FILES['image']);
            if (isset($upload['error'])) {
                set_flash('error', $upload['error']);
                header("Location: inventory_management.php");
                exit();
            }
            $imagePath = $upload['path'];
        }

        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $imagePath, $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Product "' . $name . '" updated.');
        header("Location: inventory_management.php");
        exit();
    }

    if ($action === 'delete_product') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Product deleted.');
        }
        header("Location: inventory_management.php");
        exit();
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY id ASC");
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Management — AS Berkah E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <?php if ($flash): ?>
    <div class="toast-banner toast-banner--<?php echo e($flash['type']); ?>" data-toast role="status">
      <?php echo e($flash['message']); ?>
    </div>
  <?php endif; ?>

  <header class="site-header">
    <div class="header-inner">
      <a href="admin_dashboard.php" class="brand-link">
        <span class="brand-mark" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </span>
        <span class="brand-text">
          <span class="brand-name">AS Berkah</span>
          <span class="brand-sub">Admin Console</span>
        </span>
      </a>

      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>

      <nav class="nav-links" data-nav-links aria-label="Admin navigation">
        <a href="admin_dashboard.php">Overview</a>
        <a href="manajemen_user.php">Users</a>
        <a href="inventory_management.php" class="active">Inventory</a>
        <a href="order-management.php">Orders</a>
        <a href="report.php">Reports</a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="page-main">
    <div class="page-header">
      <div>
        <div class="page-eyebrow">Catalog</div>
        <h1 class="page-title">Inventory Management</h1>
        <p class="page-subtitle">Manage product listings, pricing, images, and stock availability.</p>
      </div>
      <button type="button" class="btn btn-primary" onclick="togglePopup('add')">+ Add Product</button>
    </div>

    <section class="surface-card">
      <div class="surface-card-header">
        <h2 class="surface-card-title">Product List</h2>
        <span class="badge-pill badge-neutral"><?php echo $products->num_rows; ?> items</span>
      </div>

      <?php if ($products->num_rows > 0): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Description</th>
                <th class="text-right">Price</th>
                <th class="text-center">Stock</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($product = $products->fetch_assoc()):
                $stock = intval($product['stock']);
                $stockCls = $stock === 0 ? 'badge-danger' : ($stock <= 10 ? 'badge-warning' : 'badge-success');
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                      <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                        <img class="table-thumb" src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                      <?php else: ?>
                        <div class="table-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--ink-muted);font-size:11px;">No img</div>
                      <?php endif; ?>
                      <div>
                        <strong><?php echo e($product['name']); ?></strong>
                        <div style="font-size:12px;color:var(--ink-muted);">ID #<?php echo intval($product['id']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="max-width:300px;color:var(--ink-secondary);"><?php echo e($product['description']); ?></td>
                  <td class="text-right tabular-nums"><strong><?php echo format_idr($product['price']); ?></strong></td>
                  <td class="text-center">
                    <span class="badge-pill <?php echo $stockCls; ?>"><?php echo $stock; ?></span>
                  </td>
                  <td class="text-right">
                    <div class="action-group" style="justify-content:flex-end;">
                      <button type="button" class="btn btn-secondary btn-sm" onclick='editProduct(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                      <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this product?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="id" value="<?php echo intval($product['id']); ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h3>No products yet</h3>
          <p>Add your first product to populate the storefront catalog.</p>
          <button type="button" class="btn btn-primary" onclick="togglePopup('add')">+ Add Product</button>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- Add Product Modal -->
  <div class="ui-modal" id="popup-add" role="dialog" aria-modal="true" aria-labelledby="add-modal-title">
    <div class="ui-modal-dialog">
      <div class="ui-modal-header">
        <h3 class="ui-modal-title" id="add-modal-title">Add Product</h3>
        <button type="button" class="ui-modal-close" onclick="togglePopup('add')" aria-label="Close">&times;</button>
      </div>
      <div class="ui-modal-body">
        <form method="POST" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="add_product">
          <div class="form-group">
            <label class="form-label" for="add-name">Product Name</label>
            <input class="form-control" type="text" id="add-name" name="name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-description">Description</label>
            <textarea class="form-textarea" id="add-description" name="description" required></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label class="form-label" for="add-price">Price (Rp)</label>
              <input class="form-control" type="number" id="add-price" name="price" min="0" step="0.01" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="add-stock">Stock</label>
              <input class="form-control" type="number" id="add-stock" name="stock" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-image">Product Image (JPG/PNG/WEBP, max 2MB)</label>
            <input class="form-control" type="file" id="add-image" name="image" accept="image/jpeg,image/png,image/webp">
          </div>
          <div class="ui-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="togglePopup('add')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="ui-modal" id="popup-edit" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="ui-modal-dialog">
      <div class="ui-modal-header">
        <h3 class="ui-modal-title" id="edit-modal-title">Edit Product</h3>
        <button type="button" class="ui-modal-close" onclick="togglePopup('edit')" aria-label="Close">&times;</button>
      </div>
      <div class="ui-modal-body">
        <form method="POST" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="edit_product">
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="existing_image" id="edit-existing-image">
          <div class="form-group">
            <label class="form-label" for="edit-name">Product Name</label>
            <input class="form-control" type="text" name="name" id="edit-name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-description">Description</label>
            <textarea class="form-textarea" name="description" id="edit-description" required></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label class="form-label" for="edit-price">Price (Rp)</label>
              <input class="form-control" type="number" name="price" id="edit-price" min="0" step="0.01" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="edit-stock">Stock</label>
              <input class="form-control" type="number" name="stock" id="edit-stock" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-image">Replace Image (optional)</label>
            <input class="form-control" type="file" id="edit-image" name="image" accept="image/jpeg,image/png,image/webp">
          </div>
          <div class="ui-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="togglePopup('edit')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="footer-inner">
      <span>&copy; 2024 AS Berkah E-Commerce. All rights reserved.</span>
      <span>Admin Console</span>
    </div>
  </footer>

  <script src="assets/js/ui.js"></script>
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
      document.getElementById('edit-description').value = product.description || '';
      document.getElementById('edit-price').value = product.price;
      document.getElementById('edit-stock').value = product.stock;
      document.getElementById('edit-existing-image').value = product.image || '';
      togglePopup('edit');
    }
  </script>
</body>

</html>
