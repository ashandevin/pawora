<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product-admin-helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'adjust_stock') {
    if (!verify_csrf()) {
        set_flash('error', 'Your form session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $rawDelta = trim((string) ($_POST['delta'] ?? ''));
        if ($id < 1 || !preg_match('/^[+-]?\d+$/', $rawDelta) || (int) $rawDelta < -4294967295 || (int) $rawDelta > 4294967295) {
            set_flash('error', 'Enter a valid non-zero stock adjustment.');
        } else {
            $result = adjust_product_stock($id, (int) $rawDelta);
            if ($result['errors']) set_flash('error', $result['errors'][0]);
            else set_flash('success', 'Stock updated from ' . $result['old_stock'] . ' to ' . $result['new_stock'] . '.');
        }
    }
    redirect('admin/inventory.php');
}

$query = trim((string) ($_GET['q'] ?? ''));
$stockFilter = trim((string) ($_GET['stock'] ?? ''));
$where = [];
$params = [];
if ($query !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $query . '%';
    array_push($params, $like, $like);
}
if ($stockFilter === 'low') {
    $where[] = "p.status <> 'Inactive' AND p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold";
} elseif ($stockFilter === 'out') {
    $where[] = "p.status <> 'Inactive' AND p.stock_quantity = 0";
} elseif ($stockFilter === 'inactive') {
    $where[] = "p.status = 'Inactive'";
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$products = db_all('SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id' . $whereSql . ' ORDER BY (p.stock_quantity = 0) DESC, (p.stock_quantity <= p.low_stock_threshold) DESC, p.name', $params);
$statsRow = db_one("SELECT COUNT(*) AS total, SUM(status <> 'Inactive' AND stock_quantity > low_stock_threshold) AS available, SUM(status <> 'Inactive' AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold) AS low_stock, SUM(status <> 'Inactive' AND stock_quantity = 0) AS out_of_stock FROM products");
$stats = [
    'total' => (int) ($statsRow['total'] ?? 0),
    'available' => (int) ($statsRow['available'] ?? 0),
    'low_stock' => (int) ($statsRow['low_stock'] ?? 0),
    'out_of_stock' => (int) ($statsRow['out_of_stock'] ?? 0),
];
$adminSection = 'inventory';
$pageTitle = 'Inventory';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap">
    <div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation">
        <div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div>
        <div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div>
    </div>
    <div class="dashboard-main">
        <?php show_database_notice(); ?>
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Inventory</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Commerce operations</span><h1 class="dashboard-title mt-2 mb-1">Inventory adjustments</h1><p class="text-muted mb-0">Record stock changes without losing the audit trail. Use a positive number to receive stock and a negative number to remove it.</p></div><a class="btn btn-outline-primary" href="<?= e(app_url('admin/products.php')) ?>"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Manage products</a></div>
        <div class="row g-3 mb-4"><div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><h3><?= e((string) $stats['total']) ?></h3><small>Total products</small></div></div><div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div><h3><?= e((string) $stats['available']) ?></h3><small>In stock</small></div></div><div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div><h3><?= e((string) $stats['low_stock']) ?></h3><small>Low stock</small></div></div><div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-x-octagon" aria-hidden="true"></i></div><h3><?= e((string) $stats['out_of_stock']) ?></h3><small>Out of stock</small></div></div></div>
        <section class="dashboard-panel">
            <form class="row g-2 align-items-end mb-4" method="get" action="<?= e(app_url('admin/inventory.php')) ?>"><div class="col-md-6"><label class="form-label" for="inventory-search">Search inventory</label><input class="form-control" id="inventory-search" name="q" value="<?= e($query) ?>" placeholder="Product name or SKU"></div><div class="col-sm-5 col-md-3"><label class="form-label" for="inventory-filter">Stock state</label><select class="form-select" id="inventory-filter" name="stock"><option value="">All products</option><option value="low"<?= selected($stockFilter, 'low') ?>>Low stock</option><option value="out"<?= selected($stockFilter, 'out') ?>>Out of stock</option><option value="inactive"<?= selected($stockFilter, 'inactive') ?>>Inactive</option></select></div><div class="col-12 col-sm-1 col-md-3 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">Apply filters</span></button></div></form>
            <div class="alert alert-info d-flex gap-2 align-items-start" role="status"><i class="bi bi-info-circle-fill mt-1" aria-hidden="true"></i><span>Every adjustment is checked against the current quantity, stored transactionally and recorded in admin activity.</span></div>
            <?php if ($products): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th scope="col">Product</th><th scope="col">Current stock</th><th scope="col">Status</th><th scope="col">Adjustment</th><th scope="col" class="text-end">Action</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr id="product-<?= e((string) $product['id']) ?>"><td><div class="d-flex align-items-center gap-2"><img class="admin-product-image" src="<?= e(product_image_url($product['image'])) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt=""><div><strong class="d-block"><?= e($product['name']) ?></strong><small class="text-muted"><?= e($product['sku']) ?> · <?= e($product['category_name']) ?></small></div></div></td><td><strong class="fs-5"><?= e((string) $product['stock_quantity']) ?></strong><br><small class="text-muted">Low-stock alert at <?= e((string) $product['low_stock_threshold']) ?></small></td><td><?= product_status_badge($product) ?></td><td><form method="post" action="<?= e(app_url('admin/inventory.php')) ?>" class="inventory-adjust-form" data-loading><input type="hidden" name="form" value="adjust_stock"><input type="hidden" name="id" value="<?= e((string) $product['id']) ?>"><?= csrf_field() ?><div class="input-group input-group-sm"><label class="visually-hidden" for="delta-<?= e((string) $product['id']) ?>">Stock adjustment for <?= e($product['name']) ?></label><input class="form-control" type="number" id="delta-<?= e((string) $product['id']) ?>" name="delta" value="1" min="-4294967295" max="4294967295" step="1" required><button class="btn btn-outline-primary" type="submit">Apply</button></div></form></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('admin/edit-product.php?id=' . (int) $product['id'])) ?>"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit <?= e($product['name']) ?></span></a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><h2 class="h5">No inventory records found</h2><p class="text-muted mb-0">Try a different filter or add a product first.</p></div><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
