<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_auth();
$userId = (int) $_SESSION['user_id'];
$favorites = db_all('SELECT p.*, f.created_at AS saved_at, 1 AS is_favorite FROM favorites f JOIN pets p ON p.id = f.pet_id WHERE f.user_id = ? ORDER BY f.created_at DESC', [$userId]);
$userSection = 'favorites';
$pageTitle = 'Favorite pets';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="userSidebar" aria-label="Account navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/user-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">My PAWORA</span><h1 class="dashboard-title mb-0">Favorites</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSidebar" aria-controls="userSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open account navigation</span></button></div><div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Saved with care</span><h1 class="dashboard-title mt-2 mb-1">Favorite pets</h1><p class="text-muted mb-0">Keep a short list of profiles you would like to revisit.</p></div><a class="btn btn-primary" href="<?= e(app_url('pets.php')) ?>">Find more pets</a></div><?php if ($favorites): ?><div class="row g-4"><?php foreach ($favorites as $pet): ?><div class="col-12 col-sm-6 col-xl-4"><?php require __DIR__ . '/../includes/pet-card.php'; ?></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-heart" aria-hidden="true"></i></div><h2 class="h4">No favorite pets yet.</h2><p class="text-muted">When a profile feels promising, tap the heart to save it here.</p><a class="btn btn-primary" href="<?= e(app_url('pets.php')) ?>">Discover pets</a></div><?php endif; ?></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
