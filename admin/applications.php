<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'status') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, application_statuses(), true)) set_flash('error', 'Choose a valid application status.');
        else {
            $application = db_one('SELECT a.*, p.name AS pet_name FROM adoption_applications a JOIN pets p ON p.id = a.pet_id WHERE a.id = ?', [$id]);
            if ($application) {
                try {
                    $pdo = db();
                    $pdo->beginTransaction();
                    $lockedApplication = $pdo->prepare('SELECT a.*, p.name AS pet_name, p.status AS pet_status FROM adoption_applications a JOIN pets p ON p.id = a.pet_id WHERE a.id = ? FOR UPDATE');
                    $lockedApplication->execute([$id]);
                    $locked = $lockedApplication->fetch();
                    if (!$locked) {
                        $pdo->rollBack();
                        set_flash('error', 'Application not found.');
                    } elseif ($locked['status'] === 'ADOPTION COMPLETED' && $status !== 'ADOPTION COMPLETED') {
                        $pdo->rollBack();
                        set_flash('error', 'Completed applications cannot be reopened.');
                    } else {
                        $statement = $pdo->prepare('UPDATE adoption_applications SET status = ? WHERE id = ?');
                        $statement->execute([$status, $id]);
                        if ($status === 'ADOPTION COMPLETED') {
                            $petUpdate = $pdo->prepare('UPDATE pets SET status = ? WHERE id = ?');
                            $petUpdate->execute(['Adopted', (int) $locked['pet_id']]);
                        } elseif ($status === 'REJECTED') {
                            $activeApplications = $pdo->prepare('SELECT COUNT(*) FROM adoption_applications WHERE pet_id = ? AND status NOT IN (?, ?)');
                            $activeApplications->execute([(int) $locked['pet_id'], 'REJECTED', 'ADOPTION COMPLETED']);
                            if ((int) $activeApplications->fetchColumn() === 0) {
                                $petUpdate = $pdo->prepare('UPDATE pets SET status = ? WHERE id = ? AND status = ?');
                                $petUpdate->execute(['Available', (int) $locked['pet_id'], 'Pending']);
                            }
                        } else {
                            $petUpdate = $pdo->prepare('UPDATE pets SET status = ? WHERE id = ? AND status = ?');
                            $petUpdate->execute(['Pending', (int) $locked['pet_id'], 'Available']);
                        }
                        notify((int) $locked['user_id'], 'Your application status has been updated', 'Your application for ' . $locked['pet_name'] . ' is now ' . status_label($status) . '.');
                        add_admin_log((int) $_SESSION['user_id'], 'APPLICATION_STATUS', 'Updated application ' . $locked['application_reference'] . ' to ' . $status);
                        $pdo->commit();
                        set_flash('success', 'Application status updated.');
                    }
                } catch (Throwable $exception) {
                    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    set_flash('error', 'We could not update the application status right now.');
                }
            } else set_flash('error', 'Application not found.');
        }
    }
    redirect('admin/applications.php');
}
$statusFilter = (string) ($_GET['status'] ?? '');
$where = $statusFilter !== '' ? ' WHERE a.status = ?' : '';
$params = $statusFilter !== '' ? [$statusFilter] : [];
$applications = db_all('SELECT a.*, p.name AS pet_name, p.breed, p.image, u.name AS applicant, u.email FROM adoption_applications a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = a.user_id' . $where . ' ORDER BY a.updated_at DESC', $params);
$adminSection = 'applications';
$pageTitle = 'Adoption applications';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Applications</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">Adoption workflow</span><h1 class="dashboard-title mt-2 mb-1">Adoption applications</h1><p class="text-muted mb-0">Review applications, communicate next steps and keep pet availability accurate.</p></div><section class="dashboard-panel"><div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div class="d-flex gap-2 flex-wrap"><?php foreach (['' => 'All', 'SUBMITTED' => 'Submitted', 'UNDER REVIEW' => 'Under review', 'INTERVIEW' => 'Interview', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'ADOPTION COMPLETED' => 'Completed'] as $value => $label): ?><a class="btn btn-sm <?= $statusFilter === $value ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(app_url('admin/applications.php' . ($value ? '?status=' . rawurlencode($value) : ''))) ?>"><?= e($label) ?></a><?php endforeach; ?></div><span class="small text-muted"><?= e((string) count($applications)) ?> records</span></div><?php if ($applications): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Applicant / pet</th><th>Reference</th><th>Submitted</th><th>Status</th><th>Update status</th></tr></thead><tbody><?php foreach ($applications as $application): ?><tr id="application-<?= e((string) $application['id']) ?>"><td><div class="d-flex align-items-center gap-2"><img class="pet-table-image" src="<?= e(pet_image_url($application['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="" width="48" height="48"><div><strong class="d-block"><?= e($application['applicant']) ?></strong><small class="text-muted"><?= e($application['pet_name']) ?> · <?= e($application['email']) ?></small></div></div></td><td><small><?= e($application['application_reference']) ?></small></td><td><small><?= e(format_date($application['created_at'])) ?></small></td><td><?= status_badge((string) $application['status']) ?></td><td><form class="d-flex gap-2" method="post" action="<?= e(app_url('admin/applications.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="status"><input type="hidden" name="id" value="<?= e((string) $application['id']) ?>"><label class="visually-hidden" for="status-<?= e((string) $application['id']) ?>">Status for <?= e($application['application_reference']) ?></label><select class="form-select form-select-sm" id="status-<?= e((string) $application['id']) ?>" name="status"><?php foreach (application_statuses() as $status): ?><option value="<?= e($status) ?>"<?= selected($application['status'], $status) ?>><?= e(status_label($status)) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary" type="submit">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div><h2 class="h4">No applications found.</h2><p class="text-muted mb-0">New applications will appear here for review.</p></div><?php endif; ?></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
