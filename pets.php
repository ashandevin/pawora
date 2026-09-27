<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$query = trim((string) ($_GET['q'] ?? ''));
$species = trim((string) ($_GET['species'] ?? ''));
$breed = trim((string) ($_GET['breed'] ?? ''));
$age = trim((string) ($_GET['age'] ?? ''));
$gender = trim((string) ($_GET['gender'] ?? ''));
$size = trim((string) ($_GET['size'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));
$vaccinated = trim((string) ($_GET['vaccinated'] ?? ''));
$availability = trim((string) ($_GET['availability'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

$where = [];
$params = [];
if ($query !== '') {
    $where[] = '(p.name LIKE ? OR p.breed LIKE ? OR p.location LIKE ?)';
    $like = '%' . $query . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($species, ['Dog', 'Cat', 'Rabbit', 'Bird', 'Other'], true)) {
    $where[] = 'p.species = ?';
    $params[] = $species;
}
if ($breed !== '') {
    $where[] = 'p.breed LIKE ?';
    $params[] = '%' . $breed . '%';
}
if ($gender !== '') {
    $where[] = 'p.gender = ?';
    $params[] = $gender;
}
if ($size !== '') {
    $where[] = 'p.size = ?';
    $params[] = $size;
}
if ($location !== '') {
    $where[] = 'p.location LIKE ?';
    $params[] = '%' . $location . '%';
}
if ($vaccinated === 'yes') {
    $where[] = 'p.vaccinated = 1';
} elseif ($vaccinated === 'no') {
    $where[] = 'p.vaccinated = 0';
}
if ($availability !== '') {
    $where[] = 'p.status = ?';
    $params[] = $availability;
}
if ($age === 'puppy') {
    $where[] = 'p.age_months < 12';
} elseif ($age === 'young') {
    $where[] = 'p.age_months >= 12 AND p.age_months < 48';
} elseif ($age === 'adult') {
    $where[] = 'p.age_months >= 48 AND p.age_months < 96';
} elseif ($age === 'senior') {
    $where[] = 'p.age_months >= 96';
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$sortSql = match ($sort) {
    'oldest' => 'p.created_at ASC',
    'name' => 'p.name ASC',
    'age' => 'p.age_months ASC, p.name ASC',
    'updated' => 'p.updated_at DESC',
    default => 'p.created_at DESC',
};
$count = (int) (db_value('SELECT COUNT(*) FROM pets p' . $whereSql, $params) ?? 0);
$totalPages = max(1, (int) ceil($count / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$petQuery = 'SELECT p.*, CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS is_favorite FROM pets p LEFT JOIN favorites f ON f.pet_id = p.id AND f.user_id = ?';
$petParams = is_logged_in() ? [(int) $_SESSION['user_id']] : [null];
$petQuery .= $whereSql . ' ORDER BY ' . $sortSql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
$pets = db_all($petQuery, array_merge($petParams, $params));

$pageTitle = 'Find a Pet';
$activePage = 'pets';
require __DIR__ . '/includes/header.php';
$queryString = $_GET;
$buildUrl = static function (array $changes = []) use ($queryString): string {
    $queryString = array_merge($queryString, $changes);
    foreach ($queryString as $key => $value) {
        if ($value === '' || $value === null) unset($queryString[$key]);
    }
    return app_url('pets.php') . ($queryString ? '?' . http_build_query($queryString) : '');
};
?>

<section class="page-hero">
    <div class="container"><div class="row align-items-end g-4"><div class="col-lg-8"><span class="eyebrow">Your next chapter</span><h1 class="display-6 mt-3 mb-3">Find a pet who feels like home.</h1><p class="section-lead mb-0">Search real profiles, filter by what matters to you, and get to know each animal before you decide.</p></div><div class="col-lg-4 text-lg-end"><a class="btn btn-primary" href="<?= e(app_url('adoption-process.php')) ?>">How adoption works <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div></div></div>
</section>

<section class="section-space-sm" id="pet-search">
    <div class="container">
        <?php show_database_notice(); ?>
        <form class="mb-4 p-3 p-md-4 bg-white border rounded-4 shadow-sm" method="get" action="<?= e(app_url('pets.php')) ?>" role="search" data-validate>
            <div class="row g-3 align-items-end"><div class="col-md-6 col-lg-4"><label class="form-label" for="listing-search">Search by name, breed or location</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search" aria-hidden="true"></i></span><input class="form-control border-start-0" id="listing-search" name="q" value="<?= e($query) ?>" placeholder="Try Luna or Golden Retriever"></div></div><div class="col-6 col-md-3 col-lg-2"><label class="form-label" for="listing-species">Species</label><select class="form-select" id="listing-species" name="species"><option value="">Any species</option><?php foreach (['Dog', 'Cat', 'Rabbit', 'Bird', 'Other'] as $option): ?><option value="<?= e($option) ?>"<?= selected($species, $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-6 col-md-3 col-lg-2"><label class="form-label" for="listing-availability">Availability</label><select class="form-select" id="listing-availability" name="availability"><option value="">Any status</option><?php foreach (['Available', 'Pending', 'Reserved', 'Adopted'] as $option): ?><option value="<?= e($option) ?>"<?= selected($availability, $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-6 col-md-6 col-lg-2"><label class="form-label" for="listing-gender">Gender</label><select class="form-select" id="listing-gender" name="gender"><option value="">Any gender</option><option value="Female"<?= selected($gender, 'Female') ?>>Female</option><option value="Male"<?= selected($gender, 'Male') ?>>Male</option></select></div><div class="col-6 col-md-6 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Search</button><a class="btn btn-outline-secondary" href="<?= e(app_url('pets.php')) ?>" aria-label="Clear search"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></a></div></div>
        </form>

        <div class="discovery-layout">
            <aside class="filter-panel" aria-label="Filter pets">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">Refine your search</h2><span class="text-muted small"><?= e((string) $count) ?> matches</span></div>
                <form method="get" action="<?= e(app_url('pets.php')) ?>">
                    <?php if ($query !== ''): ?><input type="hidden" name="q" value="<?= e($query) ?>"><?php endif; ?>
                    <div class="mb-3"><label class="form-label" for="filter-breed">Breed</label><input class="form-control" id="filter-breed" name="breed" value="<?= e($breed) ?>" placeholder="Any breed"></div>
                    <div class="mb-3"><label class="form-label" for="filter-age">Age</label><select class="form-select" id="filter-age" name="age"><option value="">Any age</option><option value="puppy"<?= selected($age, 'puppy') ?>>Under 1 year</option><option value="young"<?= selected($age, 'young') ?>>1–3 years</option><option value="adult"<?= selected($age, 'adult') ?>>4–7 years</option><option value="senior"<?= selected($age, 'senior') ?>>8+ years</option></select></div>
                    <div class="mb-3"><label class="form-label" for="filter-size">Size</label><select class="form-select" id="filter-size" name="size"><option value="">Any size</option><option value="Small"<?= selected($size, 'Small') ?>>Small</option><option value="Medium"<?= selected($size, 'Medium') ?>>Medium</option><option value="Large"<?= selected($size, 'Large') ?>>Large</option></select></div>
                    <div class="mb-3"><label class="form-label" for="filter-location">Location</label><input class="form-control" id="filter-location" name="location" value="<?= e($location) ?>" placeholder="Any location"></div>
                    <div class="mb-3"><label class="form-label" for="filter-vaccination">Vaccination</label><select class="form-select" id="filter-vaccination" name="vaccinated"><option value="">Any status</option><option value="yes"<?= selected($vaccinated, 'yes') ?>>Vaccinated</option><option value="no"<?= selected($vaccinated, 'no') ?>>Not recorded</option></select></div>
                    <input type="hidden" name="species" value="<?= e($species) ?>"><input type="hidden" name="gender" value="<?= e($gender) ?>"><input type="hidden" name="availability" value="<?= e($availability) ?>"><input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <div class="d-grid gap-2"><button class="btn btn-primary btn-sm" type="submit">Apply filters</button><a class="btn btn-link btn-sm text-muted" href="<?= e(app_url('pets.php')) ?>">Clear all filters</a></div>
                </form>
            </aside>
            <div>
                <div class="results-toolbar"><p class="results-count mb-0"><strong><?= e((string) $count) ?></strong> pets found <?= $query ? 'for “' . e($query) . '”' : '' ?></p><form method="get" action="<?= e(app_url('pets.php')) ?>"><?php foreach (['q' => $query, 'species' => $species, 'breed' => $breed, 'age' => $age, 'gender' => $gender, 'size' => $size, 'location' => $location, 'vaccinated' => $vaccinated, 'availability' => $availability] as $key => $value): ?><?php if ($value !== ''): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endif; ?><?php endforeach; ?><label class="visually-hidden" for="sort-pets">Sort pets</label><select class="form-select" id="sort-pets" name="sort" data-auto-submit><option value="newest"<?= selected($sort, 'newest') ?>>Newest first</option><option value="oldest"<?= selected($sort, 'oldest') ?>>Oldest first</option><option value="name"<?= selected($sort, 'name') ?>>Name A–Z</option><option value="age"<?= selected($sort, 'age') ?>>Age</option><option value="updated"<?= selected($sort, 'updated') ?>>Recently updated</option></select></form></div>
                <?php if ($pets): ?><div class="row g-4"><?php foreach ($pets as $pet): ?><div class="col-12 col-sm-6 col-xl-4"><?php require __DIR__ . '/includes/pet-card.php'; ?></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-search-heart" aria-hidden="true"></i></div><h2 class="h4">We couldn’t find a pet matching your search.</h2><p class="text-muted">Try a broader search or clear one of the filters. A new profile may be waiting soon.</p><a class="btn btn-primary" href="<?= e(app_url('pets.php')) ?>">Clear filters</a></div><?php endif; ?>
                <?php if ($totalPages > 1): ?><nav class="mt-5" aria-label="Pet results pages"><ul class="pagination justify-content-center"><?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= e($buildUrl(['page' => $page - 1])) ?>" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a></li><?php endif; ?><?php for ($number = 1; $number <= $totalPages; $number++): ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($buildUrl(['page' => $number])) ?>" aria-label="Page <?= e((string) $number) ?>"><?= e((string) $number) ?></a></li><?php endfor; ?><?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= e($buildUrl(['page' => $page + 1])) ?>" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li><?php endif; ?></ul></nav><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
