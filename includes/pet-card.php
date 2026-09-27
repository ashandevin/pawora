<?php
declare(strict_types=1);

$pet = $pet ?? [];
$favorite = !empty($pet['is_favorite']);
$returnTo = 'pet-details.php?id=' . (int) ($pet['id'] ?? 0);
?>
<article class="pet-card">
    <div class="pet-card-image-wrap">
        <a href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>" aria-label="View <?= e($pet['name']) ?>'s profile">
            <img class="pet-card-image" src="<?= e(pet_image_url($pet['image'] ?? null)) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="<?= e($pet['name']) ?>, <?= e($pet['breed'] ?? 'pet') ?>" loading="lazy">
        </a>
        <form method="post" action="<?= e(app_url('favorite-action.php')) ?>" data-favorite-form>
            <?= csrf_field() ?>
            <input type="hidden" name="pet_id" value="<?= e((string) $pet['id']) ?>">
            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
            <button class="favorite-button <?= $favorite ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= $favorite ? 'Remove' : 'Add' ?> <?= e($pet['name']) ?> <?= $favorite ? 'from' : 'to' ?> favorites" title="<?= $favorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                <i class="bi <?= $favorite ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
            </button>
        </form>
        <div class="image-status"><?= status_badge((string) $pet['status']) ?></div>
    </div>
    <div class="pet-card-body">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
                <h2 class="pet-card-title mb-1"><a class="text-decoration-none" href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>"><?= e($pet['name']) ?></a></h2>
                <p class="text-muted mb-0 small"><?= e($pet['breed'] ?? 'Mixed breed') ?></p>
            </div>
            <span class="text-muted small"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= e($pet['location'] ?? '—') ?></span>
        </div>
        <div class="pet-meta mt-3">
            <span><i class="bi bi-clock" aria-hidden="true"></i><?= e(pet_age(isset($pet['age_months']) ? (int) $pet['age_months'] : null)) ?></span>
            <span><i class="bi bi-gender-<?= strtolower((string) ($pet['gender'] ?? 'male')) === 'female' ? 'female' : 'male' ?>" aria-hidden="true"></i><?= e($pet['gender'] ?? '—') ?></span>
        </div>
        <div class="pet-card-footer">
            <?= status_badge((string) $pet['status']) ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>">View profile <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
        </div>
    </div>
</article>
