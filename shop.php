<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$query = trim((string) ($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$brand = trim((string) ($_GET['brand'] ?? ''));
$availability = trim((string) ($_GET['availability'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'featured');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$where = ["p.status <> 'Inactive'", "c.status = 'Active'"];
$params = [];
if ($query !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR p.brand LIKE ?)';
    $like = '%' . $query . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($brand !== '') {
    $brandLength = function_exists('mb_strlen') ? mb_strlen($brand) : strlen(utf8_decode($brand) ?: $brand);
    if ($brandLength > 80) {
        $brand = '';
    }
}
if ($brand !== '') {
    $where[] = 'p.brand = ?';
    $params[] = $brand;
}
if ($availability === 'in_stock') {
    $where[] = 'p.stock_quantity > 0';
} elseif ($availability === 'low_stock') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold';
} elseif ($availability === 'out_of_stock') {
    $where[] = 'p.stock_quantity = 0';
}

$whereSql = ' WHERE ' . implode(' AND ', $where);
$sortSql = match ($sort) {
    'price_low' => '(p.price * (1 - (p.discount / 100))) ASC, p.name ASC',
    'price_high' => '(p.price * (1 - (p.discount / 100))) DESC, p.name ASC',
    'name' => 'p.name ASC',
    'newest' => 'p.created_at DESC',
    default => 'p.stock_quantity = 0 ASC, p.updated_at DESC, p.name ASC',
};

$count = (int) (db_value('SELECT COUNT(*) FROM products p INNER JOIN categories c ON c.id = p.category_id' . $whereSql, $params) ?? 0);
$totalPages = max(1, (int) ceil($count / $perPage));
$page = min($page, $totalPages);
$resultLabel = $count === 1 ? 'product' : 'products';
$offset = ($page - 1) * $perPage;
$products = db_all(
    'SELECT p.*, c.name AS category_name, c.slug AS category_slug, CASE WHEN p.stock_quantity = 0 THEN \'Out of Stock\' WHEN p.status = \'Inactive\' THEN \'Inactive\' ELSE \'Active\' END AS effective_status FROM products p INNER JOIN categories c ON c.id = p.category_id' . $whereSql . ' ORDER BY ' . $sortSql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
    $params
);
$categories = db_all("SELECT id, name, slug FROM categories WHERE status = 'Active' ORDER BY name ASC");
$brands = catalog_brands();

$pageTitle = 'Pet care shop';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
$queryString = $_GET;
$buildUrl = static function (array $changes = []) use ($queryString): string {
    $queryString = array_merge($queryString, $changes);
    foreach ($queryString as $key => $value) {
        if ($value === '' || $value === null) unset($queryString[$key]);
    }
    return app_url('shop.php') . ($queryString ? '?' . http_build_query($queryString) : '');
};
?>

<section class="page-hero shop-hero">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="eyebrow">Care, thoughtfully chosen</span>
                <h1 class="display-6 mt-3 mb-3">Everything they need, in one calm place.</h1>
                <p class="section-lead mb-0">Explore everyday food, play, grooming and wellbeing essentials selected to support happy, healthy days.</p>
            </div>
            <div class="col-lg-4 text-lg-end"><a class="btn btn-primary" href="<?= e(app_url('contact.php')) ?>">Ask a question <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a></div>
        </div>
    </div>
</section>

<section class="section-space-sm" id="shop-search">
    <div class="container">
        <?php show_database_notice(); ?>
        <form class="shop-search-panel mb-4" method="get" action="<?= e(app_url('shop.php')) ?>" role="search" data-validate>
            <div class="row g-3 align-items-end">
                <div class="col-md-7 col-lg-6">
                    <label class="form-label" for="shop-search">Search the shop</label>
                    <div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search" aria-hidden="true"></i></span><input class="form-control border-start-0" id="shop-search" name="q" value="<?= e($query) ?>" maxlength="120" placeholder="Try food, treats or PF001"></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label" for="shop-category">Category</label>
                    <select class="form-select" id="shop-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>"<?= selected($categoryId, $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label" for="shop-brand">Brand</label>
                    <select class="form-select" id="shop-brand" name="brand"><option value="">All brands</option><?php foreach ($brands as $brandOption): ?><option value="<?= e($brandOption) ?>"<?= selected($brand, $brandOption) ?>><?= e($brandOption) ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label" for="shop-availability">Availability</label>
                    <select class="form-select" id="shop-availability" name="availability"><option value="">Any status</option><option value="in_stock"<?= selected($availability, 'in_stock') ?>>In stock</option><option value="low_stock"<?= selected($availability, 'low_stock') ?>>Low stock</option><option value="out_of_stock"<?= selected($availability, 'out_of_stock') ?>>Out of stock</option></select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Search</button><a class="btn btn-outline-secondary" href="<?= e(app_url('shop.php')) ?>" aria-label="Clear shop filters"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></a></div>
            </div>
        </form>

        <div class="shop-layout">
            <aside class="shop-filter-panel" aria-label="Filter products">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">Refine products</h2><span class="small text-muted"><?= e((string) $count) ?> items</span></div>
                <form method="get" action="<?= e(app_url('shop.php')) ?>">
                    <?php if ($query !== ''): ?><input type="hidden" name="q" value="<?= e($query) ?>"><?php endif; ?>
                    <div class="mb-3"><label class="form-label" for="shop-filter-category">Category</label><select class="form-select" id="shop-filter-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>"<?= selected($categoryId, $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label" for="shop-filter-brand">Brand</label><select class="form-select" id="shop-filter-brand" name="brand"><option value="">All brands</option><?php foreach ($brands as $brandOption): ?><option value="<?= e($brandOption) ?>"<?= selected($brand, $brandOption) ?>><?= e($brandOption) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label" for="shop-filter-availability">Availability</label><select class="form-select" id="shop-filter-availability" name="availability"><option value="">Any status</option><option value="in_stock"<?= selected($availability, 'in_stock') ?>>In stock</option><option value="low_stock"<?= selected($availability, 'low_stock') ?>>Low stock</option><option value="out_of_stock"<?= selected($availability, 'out_of_stock') ?>>Out of stock</option></select></div>
                    <div class="mb-3"><label class="form-label" for="shop-filter-sort">Sort products</label><select class="form-select" id="shop-filter-sort" name="sort"><option value="featured"<?= selected($sort, 'featured') ?>>Featured</option><option value="newest"<?= selected($sort, 'newest') ?>>Newest</option><option value="price_low"<?= selected($sort, 'price_low') ?>>Price: low to high</option><option value="price_high"<?= selected($sort, 'price_high') ?>>Price: high to low</option><option value="name"<?= selected($sort, 'name') ?>>Name A–Z</option></select></div>
                    <div class="d-grid gap-2"><button class="btn btn-primary btn-sm" type="submit">Apply filters</button><a class="btn btn-link btn-sm text-muted" href="<?= e(app_url('shop.php')) ?>">Clear all filters</a></div>
                </form>
                <div class="shop-filter-note mt-4"><i class="bi bi-heart-pulse" aria-hidden="true"></i><p class="mb-0">Every product page shows live stock status from the PAWORA inventory.</p></div>
            </aside>
            <div>
                <div class="results-toolbar"><p class="results-count mb-0"><strong><?= e((string) $count) ?></strong> <?= e($resultLabel) ?><?= $query ? ' for “' . e($query) . '”' : '' ?></p><form method="get" action="<?= e(app_url('shop.php')) ?>"><?php foreach (['q' => $query, 'category' => $categoryId ?: '', 'brand' => $brand, 'availability' => $availability] as $key => $value): ?><?php if ($value !== '' && $value !== 0): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>"><?php endif; ?><?php endforeach; ?><label class="visually-hidden" for="shop-sort">Sort products</label><select class="form-select" id="shop-sort" name="sort" data-auto-submit><option value="featured"<?= selected($sort, 'featured') ?>>Featured</option><option value="newest"<?= selected($sort, 'newest') ?>>Newest</option><option value="price_low"<?= selected($sort, 'price_low') ?>>Price: low to high</option><option value="price_high"<?= selected($sort, 'price_high') ?>>Price: high to low</option><option value="name"<?= selected($sort, 'name') ?>>Name A–Z</option></select></form></div>
                <?php if ($products): ?>
                    <div class="row g-4"><?php foreach ($products as $product): ?><div class="col-12 col-sm-6 col-xl-4"><?php require __DIR__ . '/includes/product-card.php'; ?></div><?php endforeach; ?></div>
                <?php else: ?>
                    <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-bag-heart" aria-hidden="true"></i></div><h2 class="h4">No products match those filters.</h2><p class="text-muted">Try another search or clear a filter to see more of the PAWORA collection.</p><a class="btn btn-primary" href="<?= e(app_url('shop.php')) ?>">Clear filters</a></div>
                <?php endif; ?>
                <?php if ($totalPages > 1): ?><nav class="mt-5" aria-label="Product results pages"><ul class="pagination justify-content-center"><?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= e($buildUrl(['page' => $page - 1])) ?>" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a></li><?php endif; ?><?php for ($number = 1; $number <= $totalPages; $number++): ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($buildUrl(['page' => $number])) ?>" aria-label="Page <?= e((string) $number) ?>"><?= e((string) $number) ?></a></li><?php endfor; ?><?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="<?= e($buildUrl(['page' => $page + 1])) ?>" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li><?php endif; ?></ul></nav><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
