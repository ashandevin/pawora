<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$product = $productId > 0 ? db_one("SELECT p.*, c.name AS category_name, c.slug AS category_slug, CASE WHEN p.stock_quantity = 0 THEN 'Out of Stock' WHEN p.status = 'Inactive' THEN 'Inactive' ELSE 'Active' END AS effective_status FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status <> 'Inactive' AND c.status = 'Active'", [$productId]) : null;
$relatedProducts = [];
if ($product) {
    $relatedProducts = db_all("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.id <> ? AND p.category_id = ? AND p.status <> 'Inactive' AND c.status = 'Active' ORDER BY p.stock_quantity = 0 ASC, p.updated_at DESC LIMIT 3", [$productId, (int) $product['category_id']]);
}

$pageTitle = $product ? (string) $product['name'] . ' · Shop' : 'Product not found';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
if (!$product):
?>
<section class="section-space"><div class="container"><?php show_database_notice(); ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-bag-x" aria-hidden="true"></i></div><h1 class="h3">We couldn’t find that product.</h1><p class="text-muted">The product may have been removed or is no longer available in the shop.</p><a class="btn btn-primary" href="<?= e(app_url('shop.php')) ?>">Browse the shop</a></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; exit; endif;

$stock = max(0, (int) $product['stock_quantity']);
$threshold = max(0, (int) $product['low_stock_threshold']);
$discount = product_discount_percent($product);
$originalPrice = max(0.0, (float) $product['price']);
$salePrice = product_price($product);
$isLowStock = $stock > 0 && $stock <= $threshold;
?>

<section class="page-hero product-detail-hero">
    <div class="container">
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('shop.php')) ?>">Shop</a></li><li class="breadcrumb-item"><a href="<?= e(app_url('shop.php?category=' . (int) $product['category_id'])) ?>"><?= e($product['category_name']) ?></a></li><li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li></ol></nav>
    </div>
</section>

<section class="section-space-sm">
    <div class="container">
        <div class="row g-4 g-xl-5 align-items-start">
            <div class="col-lg-6">
                <div class="product-detail-image"><img src="<?= e(product_display_image($product)) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt="<?= e(product_image_alt($product)) ?>" width="900" height="900"></div>
                <?php $imageSourceName = product_image_source_name($product); $sourceUrl = product_source_url($product); if ($imageSourceName !== '' || $sourceUrl !== ''): ?>
                    <p class="product-detail-source">Image/source: <?php if ($sourceUrl !== ''): ?><a href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($imageSourceName !== '' ? $imageSourceName : $sourceUrl) ?></a><?php else: ?><?= e($imageSourceName) ?><?php endif; ?></p>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <div class="product-detail-copy">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="product-category"><?= e($product['category_name']) ?></span><?= product_status_badge($product) ?><span class="product-detail-brand"><?= e(product_brand($product)) ?></span></div>
                    <h1 class="product-detail-title"><?= e($product['name']) ?></h1>
                    <?php $stars = product_rating_stars(product_rating($product)); $ratingSummary = product_rating_summary($product); if ($stars !== ''): ?>
                        <div class="product-detail-rating"><span class="product-card-stars" aria-hidden="true"><?= $stars ?></span><span><?= e($ratingSummary) ?></span><span class="product-rating-flag">Sample rating for the prototype</span></div>
                    <?php endif; ?>
                    <p class="product-detail-sku">SKU: <?= e($product['sku']) ?></p>
                    <div class="product-detail-price"><?= e(format_money($salePrice, product_currency($product))) ?><?php if ($discount > 0): ?><del><?= e(format_money($originalPrice, product_currency($product))) ?></del><?php endif; ?></div>
                    <p class="product-detail-price-note"><?= e(product_price_note($product)) ?><?php $priceSource = product_price_source($product); if ($priceSource !== ''): ?> Source: <?= $priceSource !== '' && preg_match('#^https?://#i', $priceSource) ? '<a href="' . e($priceSource) . '" target="_blank" rel="noopener noreferrer nofollow">' . e($priceSource) . '</a>' : e($priceSource) ?>.<?php endif; ?></p>
                    <?php if (!product_is_demo_price($product) && ($product['source_price'] ?? null) !== null): ?>
                        <dl class="product-source-price">
                            <div><dt>Source price</dt><dd><?= e(format_money((float) $product['source_price'], (string) ($product['source_currency'] ?: $product['currency']))) ?><?= $product['price_checked_on'] ? ' <span class="text-muted">checked ' . e(format_date((string) $product['price_checked_on'])) . '</span>' : '' ?></dd></div>
                            <?php if (!empty($product['source_exchange_rate']) && strtoupper((string) $product['source_currency']) !== strtoupper((string) $product['currency'])): ?>
                                <div><dt>Conversion</dt><dd>1 <?= e((string) $product['source_currency']) ?> = <?= e(format_money((float) $product['source_exchange_rate'], (string) $product['currency'])) ?> to <?= e((string) $product['currency']) ?></dd></div>
                            <?php endif; ?>
                        </dl>
                    <?php endif; ?>
                    <p class="product-detail-description"><?= nl2br(e($product['description'])) ?></p>
                    <?php if ($stock === 0): ?>
                        <div class="stock-alert stock-alert-out" role="status"><i class="bi bi-x-octagon-fill" aria-hidden="true"></i><div><strong>Out of stock</strong><p class="mb-0">This product is currently unavailable. Join the waiting list and we will email you when it is back.</p></div></div>
                    <?php elseif ($isLowStock): ?>
                        <div class="stock-alert stock-alert-low" role="status"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><div><strong>Only <?= e((string) $stock) ?> units remain.</strong><p class="mb-0">This product is running low and may sell out soon.</p></div></div>
                    <?php else: ?>
                        <div class="stock-alert stock-alert-in" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><div><strong>In stock and ready.</strong><p class="mb-0">Live inventory is available for this product.</p></div></div>
                    <?php endif; ?>
                    <?php if ($stock > 0): ?>
                        <form class="product-detail-cart-form" method="post" action="<?= e(app_url('cart-action.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <input type="hidden" name="return_to" value="product-details.php?id=<?= (int) $product['id'] ?>">
                            <div class="input-group">
                                <label class="input-group-text" for="detail-quantity">Quantity</label>
                                <input class="form-control" id="detail-quantity" type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" required>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-bag-plus me-1" aria-hidden="true"></i>Add to cart</button>
                            </div>
                        </form>
                        <form class="product-detail-favorite-form" method="post" action="<?= e(app_url('product-favorite-action.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <input type="hidden" name="return_to" value="product-details.php?id=<?= (int) $product['id'] ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-heart<?= is_product_favorite((int) $product['id']) ? '-fill' : '' ?> me-1" aria-hidden="true"></i><?= e(product_favorite_button_label((int) $product['id'])) ?></button>
                        </form>
                    <?php else: ?>
                        <div class="dashboard-panel" id="notify-me">
                            <h2 class="section-title h5 mb-2">Notify me when available</h2>
                            <p class="text-muted small">Leave your details and the PAWORA team will email you when <?= e($product['name']) ?> is back in stock.</p>
                            <form method="post" action="<?= e(app_url('product-notify-action.php')) ?>" class="row g-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="return_to" value="product-details.php?id=<?= (int) $product['id'] ?>#notify-me">
                                <div class="col-12 col-sm-6"><label class="form-label" for="notify-name">Full name</label><input class="form-control" id="notify-name" name="name" value="<?= e((string) (current_user()['name'] ?? '')) ?>" maxlength="120" required></div>
                                <div class="col-12 col-sm-6"><label class="form-label" for="notify-email">Email address</label><input class="form-control" id="notify-email" type="email" name="email" value="<?= e((string) (current_user()['email'] ?? '')) ?>" maxlength="190" required></div>
                                <div class="col-12 col-sm-6"><label class="form-label" for="notify-phone">Phone <span class="text-muted">(optional)</span></label><input class="form-control" id="notify-phone" name="phone" value="<?= e((string) (current_user()['phone'] ?? '')) ?>" maxlength="40"></div>
                                <div class="col-12 col-sm-6 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-bell me-1" aria-hidden="true"></i>Join waiting list</button></div>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="product-detail-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Need help choosing? <a href="<?= e(app_url('contact.php')) ?>">Contact the PAWORA team</a> for thoughtful guidance.</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-space pt-0" aria-labelledby="related-products-title">
    <div class="container">
        <div class="section-heading-row"><div><span class="eyebrow">Keep exploring</span><h2 class="section-title mt-3 mb-2" id="related-products-title">More from <?= e($product['category_name']) ?>.</h2></div><a class="btn btn-outline-primary" href="<?= e(app_url('shop.php')) ?>">Back to shop</a></div>
        <?php if ($relatedProducts): ?><div class="row g-4"><?php foreach ($relatedProducts as $relatedProduct): ?><div class="col-12 col-sm-6 col-lg-4"><?php $product = $relatedProduct; require __DIR__ . '/includes/product-card.php'; ?></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><h3>More products are coming soon.</h3><p class="text-muted mb-0">This is the only product in this category for now.</p></div><?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
