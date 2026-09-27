<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product-admin-helpers.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = $id > 0 ? get_product_admin($id) : null;
if (!$product) {
    set_flash('error', 'That product could not be found.');
    redirect('admin/products.php');
}
$publicProductAvailable = $product['status'] !== 'Inactive' && $product['category_status'] === 'Active';
$errors = [];
$values = product_admin_form_values($product);
$categories = db_all('SELECT * FROM categories ORDER BY status = \'Inactive\', name');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $result = save_product($id);
        $errors = $result['errors'];
        $values = $result['values'] ?? product_admin_form_values($product);
        if (!$errors) {
            set_flash('success', $values['name'] . ' was updated successfully.');
            redirect('admin/products.php');
        }
    }
}
$editing = true;
$adminSection = 'products';
$pageTitle = 'Edit ' . $product['name'];
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
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Edit product</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('admin/products.php')) ?>">Manage products</a></li><li class="breadcrumb-item active" aria-current="page">Edit product</li></ol></nav>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Update catalog item</span><h1 class="dashboard-title mt-2 mb-1">Edit <?= e($product['name']) ?>.</h1><p class="text-muted mb-0">Changes are reflected on the public shop after saving.</p></div><div class="d-flex flex-wrap gap-2"><?php if ($publicProductAvailable): ?><a class="btn btn-outline-primary" href="<?= e(app_url('product-details.php?id=' . $id)) ?>">View public product <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a><?php else: ?><span class="text-muted small align-self-center">Not visible in the public shop</span><?php endif; ?><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/inventory.php#product-' . $id)) ?>">Adjust stock</a></div></div>
        <form method="post" action="<?= e(app_url('admin/edit-product.php?id=' . $id)) ?>" enctype="multipart/form-data" data-loading data-validate>
            <?= csrf_field() ?>
            <div class="dashboard-panel mb-4"><?php require __DIR__ . '/../includes/product-form-fields.php'; ?></div>
            <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/products.php')) ?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save product</button></div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
