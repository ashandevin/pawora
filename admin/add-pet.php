<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-helpers.php';
require_admin();

$errors = [];
$editing = false;
$values = pet_form_values();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    if (!$errors) {
        $result = save_pet(null);
        $errors = $result['errors'];
        $values = $result['values'] ?? pet_form_values();
        if (!$errors) { set_flash('success', 'New pet profile created successfully.'); redirect('admin/pets.php'); }
    }
}
$adminSection = 'pets';
$pageTitle = 'Add pet';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Add pet</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('admin/pets.php')) ?>">Manage pets</a></li><li class="breadcrumb-item active" aria-current="page">Add pet</li></ol></nav><div class="mb-4"><span class="eyebrow">Create profile</span><h1 class="dashboard-title mt-2 mb-1">Add a new pet.</h1><p class="text-muted mb-0">The information you save will be available on the public discovery page.</p></div><form method="post" action="<?= e(app_url('admin/add-pet.php')) ?>" enctype="multipart/form-data" data-loading data-validate><?= csrf_field() ?><div class="dashboard-panel mb-4"><?php require __DIR__ . '/../includes/pet-form-fields.php'; ?></div><div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/pets.php')) ?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create pet profile</button></div></form></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
