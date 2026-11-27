<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$conn = db();
$sql = "SELECT * FROM orders WHERE status = 'Selesai' ORDER BY created_at DESC";
$result = $conn->query($sql);

$completed_orders = [];
$total_sales = 0;
if ($result && $result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        $completed_orders[] = $order;
        $total_sales += floatval($order['total_amount']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report — AS Berkah E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
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
                <a href="inventory_management.php">Inventory</a>
                <a href="order-management.php">Orders</a>
                <a href="report.php" class="active">Reports</a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="page-header">
            <div>
                <div class="page-eyebrow">Analytics</div>
                <h1 class="page-title">Sales Report</h1>
                <p class="page-subtitle">Completed transactions and verified revenue totals.</p>
            </div>
        </div>

        <section class="kpi-grid kpi-grid--2" aria-label="Sales summary">
            <div class="kpi-card">
                <div class="kpi-top">
                    <span class="kpi-label">Completed Orders</span>
                    <span class="kpi-icon kpi-icon--info" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                </div>
                <div class="kpi-value"><?php echo count($completed_orders); ?></div>
                <div class="kpi-meta">Orders marked as Selesai</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-top">
                    <span class="kpi-label">Total Revenue</span>
                    <span class="kpi-icon kpi-icon--success" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </span>
                </div>
                <div class="kpi-value"><?php echo format_idr($total_sales); ?></div>
                <div class="kpi-meta">Gross sales from completed orders</div>
            </div>
        </section>

        <section class="surface-card">
            <div class="surface-card-header">
                <h2 class="surface-card-title">Completed Transactions</h2>
                <input type="search" id="reportSearch" class="form-control" placeholder="Filter by order ID or date..." aria-label="Filter transactions" style="max-width:260px;">
            </div>

            <?php if (count($completed_orders) > 0): ?>
                <div class="table-wrap">
                    <table id="salesTable" class="data-table data-table--responsive">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_orders as $order): ?>
                                <tr>
                                    <td data-label="Order"><strong>#<?php echo intval($order['id']); ?></strong></td>
                                    <td data-label="Date" class="tabular-nums"><?php echo date("d M Y, H:i", strtotime($order['created_at'])); ?></td>
                                    <td data-label="Total" class="text-right tabular-nums"><strong><?php echo format_idr($order['total_amount']); ?></strong></td>
                                    <td data-actions="true" class="text-right">
                                        <button type="button" class="btn btn-secondary btn-sm view-details" data-order-id="<?php echo intval($order['id']); ?>">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No completed sales yet</h3>
                    <p>Completed orders will appear in this report once customers confirm delivery.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Order Details Modal -->
    <div class="ui-modal" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailModalLabel">
        <div class="ui-modal-dialog" style="max-width:580px;">
            <div class="ui-modal-header">
                <h3 class="ui-modal-title" id="detailModalLabel">Order Details</h3>
                <button type="button" class="ui-modal-close" id="closeDetailModal" aria-label="Close">&times;</button>
            </div>
            <div class="ui-modal-body">
                <div class="order-meta-grid" style="grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));margin-bottom:20px;">
                    <div class="meta-tile">
                        <div class="meta-tile-label">Customer</div>
                        <div class="meta-tile-value" id="customerInfo">—</div>
                    </div>
                    <div class="meta-tile">
                        <div class="meta-tile-label">Payment</div>
                        <div class="meta-tile-value" id="paymentMethod">—</div>
                    </div>
                </div>
                <div class="meta-tile" style="margin-bottom:20px;">
                    <div class="meta-tile-label">Shipping Address</div>
                    <div class="meta-tile-value" id="shippingAddress">—</div>
                </div>

                <div class="table-wrap" style="border:1px solid var(--border-subtle);border-radius:var(--radius-sm);">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody id="orderDetails"></tbody>
                    </table>
                </div>
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
        (function () {
            const modal = document.getElementById('detailModal');
            const closeBtn = document.getElementById('closeDetailModal');
            const searchInput = document.getElementById('reportSearch');
            const table = document.getElementById('salesTable');

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', function () {
                    modal.classList.remove('active');
                });
            }

            if (searchInput && table) {
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                searchInput.addEventListener('input', function () {
                    const q = searchInput.value.trim().toLowerCase();
                    rows.forEach(function (tr) {
                        tr.style.display = !q || tr.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
            }

            document.querySelectorAll('.view-details').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const orderId = btn.getAttribute('data-order-id');
                    const formData = new URLSearchParams();
                    formData.append('order_id', orderId);

                    fetch('fetch_order_details.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    })
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        document.getElementById('customerInfo').textContent = data.customer_name || '—';
                        document.getElementById('shippingAddress').textContent = data.shipping_address || 'Not specified';
                        document.getElementById('paymentMethod').textContent = (data.payment_method || '—').replace(/_/g, ' ');
                        const tbody = document.getElementById('orderDetails');
                        tbody.innerHTML = '';
                        (data.order_items || []).forEach(function (item) {
                            const tr = document.createElement('tr');
                            const tdName = document.createElement('td');
                            const strong = document.createElement('strong');
                            strong.textContent = item.product_name;
                            tdName.appendChild(strong);

                            const tdQty = document.createElement('td');
                            tdQty.className = 'text-center tabular-nums';
                            tdQty.textContent = item.quantity;

                            const tdPrice = document.createElement('td');
                            tdPrice.className = 'text-right tabular-nums';
                            tdPrice.textContent = 'Rp ' + Number(item.price).toLocaleString('id-ID');

                            tr.appendChild(tdName);
                            tr.appendChild(tdQty);
                            tr.appendChild(tdPrice);
                            tbody.appendChild(tr);
                        });
                        modal.classList.add('active');
                    })
                    .catch(function () {
                        alert('Failed to fetch order details.');
                    });
                });
            });
        })();
    </script>
</body>

</html>
