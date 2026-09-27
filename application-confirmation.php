<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';
require_auth();

$reference = trim((string) ($_GET['reference'] ?? $_SESSION['application_confirmation'] ?? ''));
$application = $reference !== '' ? db_one('SELECT a.*, p.name AS pet_name, p.breed, p.image FROM adoption_applications a JOIN pets p ON p.id = a.pet_id WHERE a.application_reference = ? AND a.user_id = ?', [$reference, (int) $_SESSION['user_id']]) : null;
if ($application) unset($_SESSION['application_confirmation']);
$pageTitle = 'Application submitted';
$activePage = 'adoption';
require __DIR__ . '/includes/header.php';
if (!$application):
?>
<section class="section-space"><div class="container"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-file-earmark-x" aria-hidden="true"></i></div><h1 class="h3">We couldn’t find that application.</h1><p class="text-muted">The reference may be invalid or belong to another account.</p><a class="btn btn-primary" href="<?= e(app_url('user/applications.php')) ?>">View my applications</a></div></div></section>
<?php else: ?>
<section class="section-space"><div class="container"><div class="application-shell"><div class="confirmation-card"><div class="confirmation-icon" aria-hidden="true"><i class="bi bi-check2"></i></div><span class="eyebrow">Application received</span><h1 class="section-title mt-3 mb-2">Application submitted successfully!</h1><p class="section-lead mx-auto">Thank you for taking the time to share your home with us. We have sent a confirmation to your account.</p><div class="my-4"><span class="reference-box"><?= e($application['application_reference']) ?></span><br><button class="btn btn-link btn-sm text-muted mt-2" type="button" data-copy-reference="<?= e($application['application_reference']) ?>"><i class="bi bi-copy" aria-hidden="true"></i> Copy reference</button></div><div class="row g-3 text-start justify-content-center mb-4"><div class="col-sm-5"><div class="p-3 bg-light rounded-3 h-100"><small class="text-muted d-block mb-1">Pet</small><strong><?= e($application['pet_name']) ?></strong><small class="d-block text-muted mt-1"><?= e($application['breed']) ?></small></div></div><div class="col-sm-5"><div class="p-3 bg-light rounded-3 h-100"><small class="text-muted d-block mb-1">Current status</small><?= status_badge((string) $application['status']) ?></div></div></div><div class="d-flex flex-wrap justify-content-center gap-2"><a class="btn btn-primary" href="<?= e(app_url('user/application-details.php?id=' . (int) $application['id'])) ?>">Track Application <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a><a class="btn btn-outline-primary" href="<?= e(app_url('pets.php')) ?>">Keep exploring</a></div></div></div></div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
