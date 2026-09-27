<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product-admin-helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Your form session expired. Please try again.');
    } else {
        $action = (string) ($_POST['form'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'delete' && $id > 0) {
            $result = delete_product($id);
            if ($result['errors']) set_flash('error', $result['errors'][0]);
            else set_flash('success', 'Product deleted successfully.');
        } elseif ($action === 'visibility' && $id > 0) {
            $status = (string) ($_POST['status'] ?? '');
            if (!in_array($status, ['Active', 'Inactive'], true)) {
                set_flash('error', 'Choose a valid product visibility.');
            } else {
                $result = set_product_visibility($id, $status);
                if ($result['errors']) set_flash('error', $result['errors'][0]);
                else set_flash('success', $status === 'Inactive' ? 'Product hidden from the public shop.' : 'Product is visible in the public shop.');
            }
        } else {
            set_flash('error', 'Choose a valid product action.');
        }
    }
    redirect('admin/products.php');
}

$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$brandFilter = trim((string) ($_GET['brand'] ?? ''));
if (text_length($brandFilter) > 80) {
    $brandFilter = '';
}
$where = [];
$params = [];
if ($query !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR p.brand LIKE ?)';
    $like = '%' . $query . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($statusFilter === 'Active') {
    $where[] = "p.status <> 'Inactive' AND p.stock_quantity > p.low_stock_threshold";
} elseif ($statusFilter === 'Out of Stock') {
    $where[] = "p.status <> 'Inactive' AND p.stock_quantity = 0";
} elseif ($statusFilter === 'Low Stock') {
    $where[] = "p.status <> 'Inactive' AND p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold";
} elseif ($statusFilter === 'Inactive') {
    $where[] = "p.status = 'Inactive'";
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($brandFilter !== '') {
    $where[] = 'p.brand = ?';
    $params[] = $brandFilter;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countRow = db_one('SELECT COUNT(*) AS total FROM products p INNER JOIN categories c ON c.id = p.category_id' . $whereSql, $params);
$count = (int) ($countRow['total'] ?? 0);
$perPage = 12;
$totalPages = max(1, (int) ceil($count / $perPage));
$page = max(1, (int) ($_GET['page'] ?? 1));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$products = db_all('SELECT p.*, c.name AS category_name, c.status AS category_status FROM products p INNER JOIN categories c ON c.id = p.category_id' . $whereSql . ' ORDER BY p.updated_at DESC, p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);
$categories = db_all('SELECT * FROM categories ORDER BY name');
$brands = catalog_brands(true);
$statsRow = db_one("SELECT COUNT(*) AS total, SUM(status <> 'Inactive' AND stock_quantity > low_stock_threshold) AS active, SUM(status <> 'Inactive' AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold) AS low_stock, SUM(status <> 'Inactive' AND stock_quantity = 0) AS out_of_stock, SUM(status = 'Inactive') AS inactive FROM products");
$stats = [
    'total' => (int) ($statsRow['total'] ?? 0),
    'active' => (int) ($statsRow['active'] ?? 0),
    'low_stock' => (int) ($statsRow['low_stock'] ?? 0),
    'out_of_stock' => (int) ($statsRow['out_of_stock'] ?? 0),
    'inactive' => (int) ($statsRow['inactive'] ?? 0),
];
$queryString = $_GET;
$buildUrl = static function (array $changes = []) use ($queryString): string {
    $queryString = array_merge($queryString, $changes);
    foreach ($queryString as $key => $value) {
        if ($value === '' || $value === null) unset($queryString[$key]);
    }
    return app_url('admin/products.php') . ($queryString ? '?' . http_build_query($queryString) : '');
};
$adminSection = 'products';
$pageTitle = 'Manage products';
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
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Products</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><span class="eyebrow">Commerce workspace</span><h1 class="dashboard-title mt-2 mb-1">Manage products</h1><p class="text-muted mb-0">Keep the public shop accurate, searchable and ready for customers.</p></div>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="<?= e(app_url('admin/categories.php')) ?>"><i class="bi bi-tags me-1" aria-hidden="true"></i>Categories</a><a class="btn btn-primary" href="<?= e(app_url('admin/add-product.php')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add product</a></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl"><div class="stat-card"><div class="stat-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><h3><?= e((string) $stats['total']) ?></h3><small>Total products</small></div></div>
            <div class="col-6 col-xl"><div class="stat-card"><div class="stat-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div><h3><?= e((string) $stats['active']) ?></h3><small>In stock</small></div></div>
            <div class="col-6 col-xl"><div class="stat-card"><div class="stat-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div><h3><?= e((string) $stats['low_stock']) ?></h3><small>Low stock</small></div></div>
            <div class="col-6 col-xl"><div class="stat-card"><div class="stat-icon"><i class="bi bi-x-octagon" aria-hidden="true"></i></div><h3><?= e((string) $stats['out_of_stock']) ?></h3><small>Out of stock</small></div></div>
            <div class="col-12 col-xl"><div class="stat-card"><div class="stat-icon"><i class="bi bi-eye-slash" aria-hidden="true"></i></div><h3><?= e((string) $stats['inactive']) ?></h3><small>Inactive</small></div></div>
        </div>
        <section class="dashboard-panel">
            <form class="row g-2 align-items-end mb-4" method="get" action="<?= e(app_url('admin/products.php')) ?>">
                <div class="col-md-5"><label class="form-label" for="admin-product-search">Search products</label><input class="form-control" id="admin-product-search" name="q" value="<?= e($query) ?>" placeholder="Name, SKU or category"></div>
                <div class="col-sm-4 col-md-3"><label class="form-label" for="admin-product-category">Category</label><select class="form-select" id="admin-product-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>"<?= selected($categoryId, $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-4 col-md-3"><label class="form-label" for="admin-product-brand">Brand</label><select class="form-select" id="admin-product-brand" name="brand"><option value="">All brands</option><?php foreach ($brands as $brandOption): ?><option value="<?= e($brandOption) ?>"<?= selected($brandFilter, $brandOption) ?>><?= e($brandOption) ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-4 col-md-3"><label class="form-label" for="admin-product-status">Availability</label><select class="form-select" id="admin-product-status" name="status"><option value="">All statuses</option><?php foreach (['Active', 'Low Stock', 'Out of Stock', 'Inactive'] as $option): ?><option value="<?= e($option) ?>"<?= selected($statusFilter, $option) ?>><?= e(status_label($option)) ?></option><?php endforeach; ?></select></div>
                <div class="col-12 col-md-1 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">Apply filters</span></button></div>
            </form>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><p class="text-muted small mb-0">Showing <?= e((string) count($products)) ?> of <?= e((string) $count) ?> products</p><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/inventory.php')) ?>"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Adjust inventory</a></div>
            <?php if ($products): ?>
                <div class="table-responsive">
                    <table class="table align-middle admin-product-table">
                        <thead><tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col">Price</th><th scope="col">Stock</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><div class="d-flex align-items-center gap-2"><img class="admin-product-image" src="<?= e(product_image_url($product['image'])) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt=""><div><strong class="d-block"><?= e($product['name']) ?></strong><small class="text-muted"><?= e($product['sku']) ?> &middot; <?= e(product_brand($product)) ?></small></div></div></td>
                                <td><?= e($product['category_name']) ?><?= $product['category_status'] === 'Inactive' ? '<br><small class="text-danger">Category inactive</small>' : '' ?></td>
                                <td><strong><?= e(format_money(product_price($product), product_currency($product))) ?></strong><?= product_discount_percent($product) > 0 ? '<br><small class="text-muted">' . e(format_money((float) $product['price'], product_currency($product))) . ' list price</small>' : '' ?><?= product_is_demo_price($product) ? '<br><small class="product-price-flag">Demo price</small>' : '' ?></td>
                                <?php $waitingCount = product_notify_count((int) $product['id']); ?>
                                <td><strong><?= e((string) $product['stock_quantity']) ?></strong><br><small class="text-muted">Alert at <?= e((string) $product['low_stock_threshold']) ?><?= $waitingCount > 0 ? ' &middot; ' . e((string) $waitingCount) . ' waiting' : '' ?></small></td>
                                <td><?= product_status_badge($product) ?></td>
                                <td><div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/edit-product.php?id=' . (int) $product['id'])) ?>"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit <?= e($product['name']) ?></span></a>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('admin/inventory.php#product-' . (int) $product['id'])) ?>"><i class="bi bi-box-seam" aria-hidden="true"></i><span class="visually-hidden">Adjust stock for <?= e($product['name']) ?></span></a>
                                    <form method="post" action="<?= e(app_url('admin/products.php')) ?>" class="d-inline"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form" value="visibility"><input type="hidden" name="id" value="<?= e((string) $product['id']) ?>"><input type="hidden" name="status" value="<?= $product['status'] === 'Inactive' ? 'Active' : 'Inactive' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit" title="<?= $product['status'] === 'Inactive' ? 'Activate product' : 'Deactivate product' ?>"><i class="bi <?= $product['status'] === 'Inactive' ? 'bi-eye' : 'bi-eye-slash' ?>" aria-hidden="true"></i><span class="visually-hidden"><?= $product['status'] === 'Inactive' ? 'Activate' : 'Deactivate' ?> <?= e($product['name']) ?></span></button></form>
                                    <form method="post" action="<?= e(app_url('admin/products.php')) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= e((string) $product['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete this product permanently?" data-confirm-title="Delete product?" title="Delete product"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Delete <?= e($product['name']) ?></span></button></form>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?><nav aria-label="Product pages"><ul class="pagination pagination-sm justify-content-end mb-0"><?php for ($number = 1; $number <= $totalPages; $number++): ?><li class="page-item<?= $number === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e($buildUrl(['page' => $number])) ?>"><?= e((string) $number) ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
            <?php else: ?>
                <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><h2 class="h5">No products found</h2><p class="text-muted mb-3">Try a different search or add the first product to this shop.</p><a class="btn btn-primary" href="<?= e(app_url('admin/add-product.php')) ?>">Add product</a></div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php $confirmModalTitle = 'Delete this product?'; $confirmModalCancel = 'Keep product'; $confirmModalSubmit = 'Delete product'; require __DIR__ . '/../includes/confirm-modal.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
