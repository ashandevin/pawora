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
        if ($action === 'save') {
            $result = save_category($id > 0 ? $id : null);
            if ($result['errors']) set_flash('error', $result['errors'][0]);
            else set_flash('success', $id > 0 ? 'Category updated successfully.' : 'Category created successfully.');
        } elseif ($action === 'delete' && $id > 0) {
            $result = delete_category($id);
            if ($result['errors']) set_flash('error', $result['errors'][0]);
            else set_flash('success', 'Category deleted successfully.');
        } else {
            set_flash('error', 'Choose a valid category action.');
        }
    }
    redirect('admin/categories.php');
}

$categories = db_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.name');
$adminSection = 'categories';
$pageTitle = 'Manage categories';
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
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Categories</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Shop organization</span><h1 class="dashboard-title mt-2 mb-1">Product categories</h1><p class="text-muted mb-0">Active categories are available in the public shop filters.</p></div><a class="btn btn-primary" href="<?= e(app_url('admin/add-product.php')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add product</a></div>
        <div class="row g-4">
            <div class="col-lg-4">
                <section class="dashboard-panel">
                    <h2 class="h5">Add a category</h2>
                    <p class="text-muted small">The URL slug is generated automatically from the name.</p>
                    <form method="post" action="<?= e(app_url('admin/categories.php')) ?>" data-loading>
                        <?= csrf_field() ?><input type="hidden" name="form" value="save">
                        <div class="mb-3"><label class="form-label" for="new-category-name">Category name <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="new-category-name" name="name" maxlength="100" required></div>
                        <div class="mb-3"><label class="form-label" for="new-category-status">Visibility <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="new-category-status" name="status" required><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create category</button>
                    </form>
                </section>
            </div>
            <div class="col-lg-8">
                <section class="dashboard-panel">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><div><h2 class="h5 mb-1">All categories</h2><p class="text-muted small mb-0">Deactivate a category to hide its products without deleting data.</p></div><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/products.php')) ?>">View products</a></div>
                    <?php if ($categories): ?>
                        <div class="table-responsive"><table class="table align-middle"><thead><tr><th scope="col">Name</th><th scope="col">Slug</th><th scope="col">Products</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead><tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td colspan="5" class="p-0">
                                    <form class="category-edit-row" method="post" action="<?= e(app_url('admin/categories.php')) ?>" data-loading><input type="hidden" name="form" value="save"><input type="hidden" name="id" value="<?= e((string) $category['id']) ?>"><?= csrf_field() ?><div class="row g-2 align-items-center"><div class="col-md-5"><label class="visually-hidden" for="category-name-<?= e((string) $category['id']) ?>">Category name</label><input class="form-control form-control-sm" id="category-name-<?= e((string) $category['id']) ?>" name="name" value="<?= e($category['name']) ?>" maxlength="100" required></div><div class="col-md-3"><code><?= e($category['slug']) ?></code></div><div class="col-md-2"><span class="small text-muted"><?= e((string) $category['product_count']) ?> products</span></div><div class="col-md-2"><label class="visually-hidden" for="category-status-<?= e((string) $category['id']) ?>">Category status</label><select class="form-select form-select-sm" id="category-status-<?= e((string) $category['id']) ?>" name="status"><option value="Active"<?= selected($category['status'], 'Active') ?>>Active</option><option value="Inactive"<?= selected($category['status'], 'Inactive') ?>>Inactive</option></select></div><div class="col-12 col-md-3 d-flex gap-1 justify-content-end"><button class="btn btn-sm btn-outline-primary" type="submit" title="Save category"><i class="bi bi-check2" aria-hidden="true"></i><span class="visually-hidden">Save <?= e($category['name']) ?></span></button><button class="btn btn-sm btn-outline-danger" type="submit" form="delete-category-<?= e((string) $category['id']) ?>" name="form" value="delete" data-confirm="Delete this empty category?" data-confirm-title="Delete category?" title="Delete category"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Delete <?= e($category['name']) ?></span></button></div></div></form><form id="delete-category-<?= e((string) $category['id']) ?>" method="post" action="<?= e(app_url('admin/categories.php')) ?>" class="d-none"><?= csrf_field() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= e((string) $category['id']) ?>"></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?>
                        <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-tags" aria-hidden="true"></i></div><h3 class="h5">No categories yet</h3><p class="text-muted mb-0">Create the first category to organize the shop.</p></div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</div>
<?php $confirmModalTitle = 'Delete category?'; $confirmModalCancel = 'Keep category'; $confirmModalSubmit = 'Delete category'; require __DIR__ . '/../includes/confirm-modal.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
