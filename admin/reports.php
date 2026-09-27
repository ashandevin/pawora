<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();
$stats = [
    'pets' => (int) (db_value('SELECT COUNT(*) FROM pets') ?? 0),
    'available' => (int) (db_value("SELECT COUNT(*) FROM pets WHERE status = 'Available'") ?? 0),
    'applications' => (int) (db_value('SELECT COUNT(*) FROM adoption_applications') ?? 0),
    'completed' => (int) (db_value("SELECT COUNT(*) FROM adoption_applications WHERE status = 'ADOPTION COMPLETED'") ?? 0),
    'feedback' => (int) (db_value('SELECT COUNT(*) FROM feedback') ?? 0),
    'messages' => (int) (db_value('SELECT COUNT(*) FROM contact_messages') ?? 0),
    'waiting' => (int) (db_value('SELECT COUNT(*) FROM waiting_list') ?? 0),
];
$applicationBreakdown = db_all('SELECT status, COUNT(*) AS total FROM adoption_applications GROUP BY status');
$adminSection = 'reports';
$pageTitle = 'Reports';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Reports</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">A simple operational view</span><h1 class="dashboard-title mt-2 mb-1">Reports</h1><p class="text-muted mb-0">Use these live database counts to support your viva demonstration.</p></div><div class="row g-3 mb-4"><div class="col-6 col-xl-3"><div class="stat-card"><small>Total pets</small><h3><?= e((string) $stats['pets']) ?></h3></div></div><div class="col-6 col-xl-3"><div class="stat-card"><small>Available</small><h3><?= e((string) $stats['available']) ?></h3></div></div><div class="col-6 col-xl-3"><div class="stat-card"><small>Applications</small><h3><?= e((string) $stats['applications']) ?></h3></div></div><div class="col-6 col-xl-3"><div class="stat-card"><small>Completed</small><h3><?= e((string) $stats['completed']) ?></h3></div></div></div><div class="row g-4"><div class="col-lg-7"><section class="dashboard-panel h-100"><span class="eyebrow">Workflow</span><h2 class="mt-2 mb-4">Application breakdown</h2><div class="chart-bar"><?php foreach ($applicationBreakdown as $row): ?><div class="chart-column"><div class="chart-column-bar" style="height: <?= e((string) min(100, ((int) $row['total'] / max(1, $stats['applications'])) * 150)) ?>%" title="<?= e(status_label((string) $row['status']) . ': ' . $row['total']) ?>"></div><small><?= e(status_label((string) $row['status'])) ?></small></div><?php endforeach; ?><?php if (!$applicationBreakdown): ?><p class="text-muted small m-auto">No application records yet.</p><?php endif; ?></div></section></div><div class="col-lg-5"><section class="dashboard-panel h-100"><span class="eyebrow">Platform signals</span><h2 class="mt-2 mb-4">Support & trust</h2><div class="d-flex flex-column gap-3"><div class="d-flex justify-content-between"><span>Feedback submissions</span><strong><?= e((string) $stats['feedback']) ?></strong></div><div class="d-flex justify-content-between"><span>Contact messages</span><strong><?= e((string) $stats['messages']) ?></strong></div><div class="d-flex justify-content-between"><span>Waiting-list entries</span><strong><?= e((string) $stats['waiting']) ?></strong></div><hr class="my-1"><small class="text-muted">These figures are generated directly from MySQL records and are suitable for coursework reporting.</small></div></section></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
