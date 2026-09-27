<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$pet = $petId > 0 ? db_one('SELECT * FROM pets WHERE id = ?', [$petId]) : null;
$waitingErrors = [];
$waitingSubmitted = false;

if ($pet && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'waiting_list') {
    if (!verify_csrf()) $waitingErrors[] = 'Your form session expired. Please try again.';
    $waitingErrors = array_merge($waitingErrors, length_errors($_POST, ['name' => ['Your name', 120], 'email' => ['Email address', 190], 'phone' => ['Phone number', 40]]));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    if ($name === '') $waitingErrors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $waitingErrors[] = 'Please enter a valid email address.';
    if (preg_match('/[^0-9+()\-\s]/', $phone) || strlen(preg_replace('/\D/', '', $phone) ?? '') < 7) $waitingErrors[] = 'Please enter a valid phone number.';
    if (!$waitingErrors) {
        $waitingSubmitted = true;
        try {
            $statement = db()->prepare('INSERT INTO waiting_list (user_id, pet_id, name, email, phone) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([is_logged_in() ? (int) $_SESSION['user_id'] : null, $pet['id'], $name, $email, $phone]);
            set_flash('success', "You've been added to {$pet['name']}'s waiting list.");
            redirect('pet-details.php?id=' . (int) $pet['id']);
        } catch (Throwable $exception) {
            $waitingErrors[] = 'We could not save your request right now. Please try again.';
        }
    }
}

$pageTitle = $pet ? $pet['name'] . ' · Pet profile' : 'Pet profile not found';
$activePage = 'pets';
require __DIR__ . '/includes/header.php';
if (!$pet):
?>
<section class="section-space"><div class="container"><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-compass" aria-hidden="true"></i></div><h1 class="h3">We couldn’t find that pet profile.</h1><p class="text-muted">The profile may have moved or is no longer available.</p><a class="btn btn-primary" href="<?= e(app_url('pets.php')) ?>">Browse all pets</a></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; exit; endif;
$isFavorite = is_favorite((int) $pet['id']);
$related = db_all('SELECT p.*, CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS is_favorite FROM pets p LEFT JOIN favorites f ON f.pet_id = p.id AND f.user_id = ? WHERE p.id <> ? AND (p.species = ? OR p.location = ?) ORDER BY p.status = \'Available\' DESC, p.updated_at DESC LIMIT 3', [is_logged_in() ? (int) $_SESSION['user_id'] : null, (int) $pet['id'], $pet['species'], $pet['location']]);
$canApply = $pet['status'] === 'Available';
$isUnavailable = in_array($pet['status'], ['Adopted', 'Reserved', 'Pending'], true);
$user = current_user();
?>

<section class="page-hero"><div class="container"><nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('pets.php')) ?>">Find a Pet</a></li><li class="breadcrumb-item active" aria-current="page"><?= e($pet['name']) ?></li></ol></nav></div></section>
<section class="section-space-sm"><div class="container"><div class="row g-4 g-xl-5 align-items-start"><div class="col-lg-6"><div class="profile-gallery"><img src="<?= e(pet_image_url($pet['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="<?= e($pet['name']) ?>, <?= e($pet['breed']) ?> profile photo" width="900" height="1100" fetchpriority="high"></div><div class="d-flex flex-wrap gap-2 mt-3"><span class="status-badge status-<?= e(strtolower($pet['status'])) ?>"><i class="bi bi-info-circle-fill" aria-hidden="true"></i><?= e(status_label($pet['status'])) ?></span><span class="status-badge"><i class="bi bi-shield-check" aria-hidden="true"></i>Profile managed by PAWORA</span></div></div><div class="col-lg-6"><div class="profile-card"><div class="d-flex justify-content-between align-items-start gap-3"><div><span class="eyebrow">Meet <?= e($pet['name']) ?></span><h1 class="profile-name mt-3 mb-1"><?= e($pet['name']) ?></h1><p class="text-muted mb-0"><?= e($pet['breed']) ?></p></div><?php if ($canApply): ?><form method="post" action="<?= e(app_url('favorite-action.php')) ?>" data-favorite-form><?= csrf_field() ?><input type="hidden" name="pet_id" value="<?= e((string) $pet['id']) ?>"><input type="hidden" name="return_to" value="pet-details.php?id=<?= e((string) $pet['id']) ?>"><button class="favorite-button position-static <?= $isFavorite ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= $isFavorite ? 'Remove from' : 'Add to' ?> favorites"><i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i></button></form><?php endif; ?></div><div class="profile-facts"><div class="profile-fact"><i class="bi bi-clock" aria-hidden="true"></i><small>Age</small><strong><?= e(pet_age((int) $pet['age_months'])) ?></strong></div><div class="profile-fact"><i class="bi bi-gender-<?= strtolower($pet['gender']) === 'female' ? 'female' : 'male' ?>" aria-hidden="true"></i><small>Gender</small><strong><?= e($pet['gender']) ?></strong></div><div class="profile-fact"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i><small>Size</small><strong><?= e($pet['size']) ?></strong></div></div><div class="d-flex flex-wrap gap-2 mb-4"><?php if ($canApply): ?><a class="btn btn-primary btn-lg" href="<?= e(app_url('adoption-application.php?pet_id=' . (int) $pet['id'])) ?>">Apply to Adopt <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a><?php elseif ($pet['status'] === 'Adopted'): ?><div class="alert alert-secondary mb-0 flex-grow-1"><i class="bi bi-heart-fill me-2" aria-hidden="true"></i><strong>Already Adopted</strong><br><small>There may still be a way to stay close to this story.</small></div><?php else: ?><div class="alert alert-warning mb-0 flex-grow-1"><i class="bi bi-hourglass-split me-2" aria-hidden="true"></i><strong><?= e(status_label($pet['status'])) ?></strong><br><small>This pet’s adoption plan is currently in progress.</small></div><?php endif; ?><a class="btn btn-outline-primary btn-lg" href="<?= e(app_url('contact.php?pet_id=' . (int) $pet['id'])) ?>">Ask about <?= e($pet['name']) ?></a></div><?php if ($isUnavailable): ?><div class="waiting-list-box p-3 rounded-3"><div class="d-flex gap-2 align-items-start"><i class="bi bi-bell text-secondary fs-4" aria-hidden="true"></i><div><strong>Not ready to wait alone?</strong><p class="small text-muted mb-2">Join the waiting list and we’ll keep your contact details with <?= e($pet['name']) ?>’s record.</p><button class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#waitingListForm" aria-expanded="false" aria-controls="waitingListForm">Join Waiting List</button></div></div></div><?php endif; ?><?php if ($waitingErrors): ?><div class="alert alert-danger mb-3" role="alert"><?= e(implode(' ', $waitingErrors)) ?></div><?php endif; ?><?php if ($waitingSubmitted): ?><div class="alert alert-success mb-3">You’re on the list.</div><?php endif; ?><div class="collapse mt-3" id="waitingListForm"><form class="p-3 border rounded-3" method="post" action="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>" data-validate><input type="hidden" name="form" value="waiting_list"><?= csrf_field() ?><h2 class="h6">Join <?= e($pet['name']) ?>’s waiting list</h2><div class="mb-2"><label class="form-label" for="waiting-name">Full name</label><input class="form-control" id="waiting-name" name="name" value="<?= e($user['name'] ?? '') ?>" required></div><div class="mb-2"><label class="form-label" for="waiting-email">Email</label><input class="form-control" type="email" id="waiting-email" name="email" value="<?= e($user['email'] ?? '') ?>" required></div><div class="mb-3"><label class="form-label" for="waiting-phone">Phone</label><input class="form-control" id="waiting-phone" name="phone" value="<?= e($user['phone'] ?? '') ?>" required></div><button class="btn btn-primary w-100" type="submit">Add me to the list</button></form></div></div></div></div></div></section>

<section class="section-space pt-0"><div class="container"><div class="row g-4"><div class="col-lg-7"><div class="info-panel h-100"><span class="eyebrow">A little about <?= e($pet['name']) ?></span><h2 class="mt-3 mb-3">A companion with room to grow.</h2><p class="mb-4"><?= nl2br(e($pet['description'])) ?></p><h3 class="h5 mt-4">Personality</h3><p class="mb-0"><?= nl2br(e($pet['personality'] ?: 'We are still learning more about this personality.')) ?></p></div></div><div class="col-lg-5"><div class="info-panel h-100"><h2 class="mb-3">Health & care</h2><ul class="info-list"><li><i class="bi bi-heart-pulse" aria-hidden="true"></i><span><strong>Health status</strong><br><?= e($pet['health_status'] ?: 'Please ask our team for current health information.') ?></span></li><li><i class="bi bi-shield-plus" aria-hidden="true"></i><span><strong>Vaccination</strong><br><?= $pet['vaccinated'] ? 'Vaccinations are recorded as complete.' : 'Vaccination details are being confirmed.' ?></span></li><li><i class="bi bi-geo-alt" aria-hidden="true"></i><span><strong>Current location</strong><br><?= e($pet['location']) ?></span></li><li><i class="bi bi-house-heart" aria-hidden="true"></i><span><strong>Adoption approach</strong><br>We prioritise safe, stable homes over speed.</span></li></ul></div></div></div></div></section>

<section class="section-space pt-0"><div class="container"><div class="section-heading-row"><div><span class="eyebrow">Keep exploring</span><h2 class="section-title mt-3 mb-2">You may also meet…</h2></div><a class="btn btn-outline-primary" href="<?= e(app_url('pets.php')) ?>">See all pets</a></div><div class="row g-4"><?php foreach ($related as $relatedPet): ?><div class="col-12 col-sm-6 col-lg-4"><?php $pet = $relatedPet; $isFavorite = (bool) ($relatedPet['is_favorite'] ?? false); require __DIR__ . '/includes/pet-card.php'; ?></div><?php endforeach; ?></div></div></section>

<?php require __DIR__ . '/includes/footer.php'; ?>
