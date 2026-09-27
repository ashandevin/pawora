<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/admin-helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pet = get_pet($id);
            if ($pet) {
                $applicationCount = (int) (db_one('SELECT COUNT(*) AS total FROM adoption_applications WHERE pet_id = ?', [$id])['total'] ?? 0);
                if ($applicationCount > 0) {
                    set_flash('error', $pet['name'] . ' has ' . $applicationCount . ' adoption application' . ($applicationCount === 1 ? '' : 's') . '. Update the pet status instead of deleting the profile.');
                } else {
                    $statement = db()->prepare('DELETE FROM pets WHERE id = ?');
                    $statement->execute([$id]);
                    remove_local_pet_image((string) ($pet['image'] ?? ''));
                    add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted pet profile: ' . $pet['name']);
                    set_flash('success', $pet['name'] . ' was removed from the collection.');
                }
            } else {
                set_flash('error', 'That pet profile no longer exists.');
            }
        } catch (Throwable $exception) {
            set_flash('error', 'We could not remove that pet profile right now.');
        }
    }
    redirect('admin/pets.php');
}

$query = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$species = trim((string) ($_GET['species'] ?? ''));
$where = [];
$params = [];
if ($query !== '') { $where[] = '(p.name LIKE ? OR p.breed LIKE ? OR p.location LIKE ?)'; $like = '%' . $query . '%'; array_push($params, $like, $like, $like); }
if ($status !== '') { $where[] = 'p.status = ?'; $params[] = $status; }
if ($species !== '') { $where[] = 'p.species = ?'; $params[] = $species; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$pets = db_all('SELECT p.*, (SELECT COUNT(*) FROM adoption_applications a WHERE a.pet_id = p.id) AS application_count, (SELECT COUNT(*) FROM waiting_list w WHERE w.pet_id = p.id) AS waiting_count FROM pets p' . $whereSql . ' ORDER BY p.updated_at DESC', $params);
$adminSection = 'pets';
$pageTitle = 'Manage pets';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Manage pets</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Inventory & profiles</span><h1 class="dashboard-title mt-2 mb-1">Manage pets</h1><p class="text-muted mb-0">Create, review and keep every public profile accurate.</p></div><a class="btn btn-primary" href="<?= e(app_url('admin/add-pet.php')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add pet</a></div><section class="dashboard-panel"><form class="row g-2 align-items-end mb-4" method="get" action="<?= e(app_url('admin/pets.php')) ?>"><div class="col-md-5"><label class="form-label" for="admin-pet-search">Search inventory</label><input class="form-control" id="admin-pet-search" name="q" value="<?= e($query) ?>" placeholder="Name, breed or location"></div><div class="col-3 col-md-2"><label class="form-label" for="admin-pet-status">Status</label><select class="form-select" id="admin-pet-status" name="status"><option value="">All statuses</option><?php foreach (['Available', 'Pending', 'Reserved', 'Adopted'] as $option): ?><option value="<?= e($option) ?>"<?= selected($status, $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-3 col-md-2"><label class="form-label" for="admin-pet-species">Species</label><select class="form-select" id="admin-pet-species" name="species"><option value="">All species</option><?php foreach (['Dog', 'Cat', 'Rabbit', 'Bird', 'Other'] as $option): ?><option value="<?= e($option) ?>"<?= selected($species, $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Filter</button><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/pets.php')) ?>" aria-label="Clear inventory filters"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></a></div></form><?php if ($pets): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pet</th><th>Status</th><th>Applications</th><th>Waiting</th><th>Updated</th><th class="text-end">Actions</th></tr></thead><tbody><?php foreach ($pets as $pet): ?><tr><td><div class="d-flex align-items-center gap-2"><img class="pet-table-image" src="<?= e(pet_image_url($pet['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="" width="48" height="48"><div><strong class="d-block"><?= e($pet['name']) ?></strong><small class="text-muted"><?= e($pet['species']) ?> · <?= e($pet['breed']) ?></small></div></div></td><td><?= status_badge((string) $pet['status']) ?></td><td><?= e((string) $pet['application_count']) ?></td><td><?= e((string) $pet['waiting_count']) ?></td><td><small class="text-muted"><?= e(format_date($pet['updated_at'])) ?></small></td><td><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>" aria-label="View <?= e($pet['name']) ?>"><i class="bi bi-eye" aria-hidden="true"></i></a><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/edit-pet.php?id=' . (int) $pet['id'])) ?>" aria-label="Edit <?= e($pet['name']) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a><form method="post" action="<?= e(app_url('admin/pets.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= e((string) $pet['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete <?= e($pet['name']) ?> from the public collection?" data-confirm-title="Delete this pet?" aria-label="Delete <?= e($pet['name']) ?>"><i class="bi bi-trash" aria-hidden="true"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-heart" aria-hidden="true"></i></div><h2 class="h4">No pet profiles found.</h2><p class="text-muted">Try clearing a filter or add a new pet to the collection.</p><a class="btn btn-primary" href="<?= e(app_url('admin/add-pet.php')) ?>">Add a pet</a></div><?php endif; ?></section></div></div>
<?php $confirmModalTitle = 'Delete this pet?'; $confirmModalCancel = 'Keep pet'; $confirmModalSubmit = 'Delete pet'; require __DIR__ . '/../includes/confirm-modal.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
