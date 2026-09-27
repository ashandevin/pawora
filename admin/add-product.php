<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product-admin-helpers.php';
require_admin();

$errors = [];
$values = product_admin_form_values();
$categories = db_all('SELECT * FROM categories ORDER BY status = \'Inactive\', name');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $result = save_product();
        $errors = $result['errors'];
        $values = $result['values'] ?? product_admin_form_values();
        if (!$errors) {
            set_flash('success', 'Product created successfully.');
            redirect('admin/products.php');
        }
    }
}
$editing = false;
$adminSection = 'products';
$pageTitle = 'Add product';
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
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Add product</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('admin/products.php')) ?>">Manage products</a></li><li class="breadcrumb-item active" aria-current="page">Add product</li></ol></nav>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Create catalog item</span><h1 class="dashboard-title mt-2 mb-1">Add a new product.</h1><p class="text-muted mb-0">The product will be available publicly when its visibility is active.</p></div><a class="btn btn-outline-primary" href="<?= e(app_url('admin/categories.php')) ?>">Manage categories</a></div>
        <?php if (!$categories): ?><div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Create an active product category before adding a product.</div><?php endif; ?>
        <form method="post" action="<?= e(app_url('admin/add-product.php')) ?>" enctype="multipart/form-data" data-loading data-validate>
            <?= csrf_field() ?>
            <div class="dashboard-panel mb-4"><?php require __DIR__ . '/../includes/product-form-fields.php'; ?></div>
            <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/products.php')) ?>">Cancel</a><button class="btn btn-primary" type="submit"<?= !$categories ? ' disabled' : '' ?>><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create product</button></div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
