<?php
declare(strict_types=1);

$product = $product ?? [];
$productId = (int) ($product['id'] ?? 0);
$productName = (string) ($product['name'] ?? 'Pet care product');
$productBrand = product_brand($product);
$productCurrency = product_currency($product);
$productCategory = (string) ($product['category_name'] ?? 'Pet care');
$productDescription = (string) ($product['description'] ?? '');
$productStock = max(0, (int) ($product['stock_quantity'] ?? 0));
$productThreshold = max(0, (int) ($product['low_stock_threshold'] ?? 0));
$productDiscount = product_discount_percent($product);
$productSalePrice = product_price($product);
$productOriginalPrice = max(0.0, (float) ($product['price'] ?? 0));
$productIsLow = $productStock > 0 && $productStock <= $productThreshold;
$productStockClass = $productStock === 0 ? 'is-out' : ($productIsLow ? 'is-low' : '');
$productStockIcon = $productStock === 0 ? 'bi-x-circle' : 'bi-check2-circle';
$productRating = product_rating($product);
$productRatingSummary = product_rating_summary($product);
$productStars = product_rating_stars($productRating);
?>
<article class="product-card">
    <div class="product-card-image-wrap">
        <a href="<?= e(app_url('product-details.php?id=' . $productId)) ?>" aria-label="View <?= e($productName) ?>">
            <img class="product-card-image" src="<?= e(product_display_image($product)) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt="<?= e(product_image_alt($product)) ?>" loading="lazy">
        </a>
        <div class="product-card-badges">
            <?php if ($productDiscount > 0): ?><span class="product-discount-badge">Save <?= e(number_format($productDiscount, 0)) ?>%</span><?php endif; ?>
            <?= product_status_badge($product) ?>
        </div>
    </div>
    <div class="product-card-body">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="product-category"><?= e($productCategory) ?></span>
            <span class="product-card-brand"><?= e($productBrand) ?></span>
        </div>
        <h2 class="product-card-title"><a class="text-decoration-none" href="<?= e(app_url('product-details.php?id=' . $productId)) ?>"><?= e($productName) ?></a></h2>
        <?php if ($productStars !== ''): ?>
            <div class="product-card-rating" title="<?= e($productRatingSummary . ' · demo rating') ?>">
                <span class="product-card-stars" aria-hidden="true"><?= $productStars ?></span>
                <span class="product-card-rating-text"><?= e($productRatingSummary) ?></span>
                <span class="visually-hidden">Demo rating, not real customer reviews</span>
            </div>
        <?php endif; ?>
        <?php if ($productDescription !== ''): ?><p class="product-card-description"><?= e($productDescription) ?></p><?php endif; ?>
        <div class="product-card-footer">
            <div>
                <div class="product-price"><?= e(format_money($productSalePrice, $productCurrency)) ?></div>
                <?php if ($productDiscount > 0): ?><del class="product-original-price"><?= e(format_money($productOriginalPrice, $productCurrency)) ?></del><?php endif; ?>
                <?php if (product_is_demo_price($product)): ?><span class="product-price-flag" title="Prototype price for demonstration only">Demo price</span><?php endif; ?>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('product-details.php?id=' . $productId)) ?>">View details <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
        </div>
        <div class="product-stock-line <?= e($productStockClass) ?>">
            <i class="bi <?= e($productStockIcon) ?>" aria-hidden="true"></i><?= e(product_stock_label($product)) ?>
        </div>
        <?php if ($productStock > 0 && strtoupper(trim((string) ($product['status'] ?? 'Active'))) === 'ACTIVE'): ?>
            <form class="product-card-cart-form" method="post" action="<?= e(app_url('cart-action.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $productId ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="return_to" value="shop.php">
                <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-bag-plus me-1" aria-hidden="true"></i>Add to cart</button>
            </form>
        <?php elseif ($productStock === 0 && strtoupper(trim((string) ($product['status'] ?? 'Active'))) === 'ACTIVE'): ?>
            <a class="btn btn-sm btn-outline-primary w-100" href="<?= e(app_url('product-details.php?id=' . $productId)) ?>#notify-me"><i class="bi bi-bell me-1" aria-hidden="true"></i>Notify me</a>
        <?php endif; ?>
    </div>
</article>
