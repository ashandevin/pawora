<?php
declare(strict_types=1);

$values = $values ?? [];
$errors = $errors ?? [];
$categories = $categories ?? [];
$editing = $editing ?? false;
?>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Please review the product details.</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="product-name">Product name <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="product-name" name="name" value="<?= e($values['name'] ?? '') ?>" maxlength="160" required></div>
            <div class="col-md-3"><label class="form-label" for="product-brand">Brand <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="product-brand" name="brand" value="<?= e($values['brand'] ?? '') ?>" maxlength="80" list="product-brand-options" required><div class="form-hint">Type a brand or pick one already in the catalog.</div><datalist id="product-brand-options"><?php foreach (catalog_brands(true) as $brandOption): ?><option value="<?= e($brandOption) ?>"></option><?php endforeach; ?></datalist></div>
            <div class="col-md-3"><label class="form-label" for="product-category">Category <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="product-category" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>"<?= selected($values['category_id'] ?? '', $category['id']) ?>><?= e($category['name']) ?><?= $category['status'] === 'Inactive' ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label" for="product-description">Description <span class="required-mark" aria-hidden="true">*</span></label><textarea class="form-control" id="product-description" name="description" rows="5" maxlength="10000" required><?= e($values['description'] ?? '') ?></textarea><div class="form-hint">Write this in your own words. Only state facts you can verify from the product source.</div></div>
            <div class="col-md-3"><label class="form-label" for="product-price">Selling price <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="number" id="product-price" name="price" value="<?= e($values['price'] ?? '') ?>" min="0" max="99999999.99" step="0.01" required></div>
            <div class="col-md-3"><label class="form-label" for="product-currency">Currency <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control text-uppercase" id="product-currency" name="currency" value="<?= e($values['currency'] ?? 'LKR') ?>" maxlength="3" required></div>
            <div class="col-md-3"><label class="form-label" for="product-discount">Discount (%) <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="number" id="product-discount" name="discount" value="<?= e($values['discount'] ?? '0') ?>" min="0" max="100" step="0.01" required></div>
            <div class="col-md-3"><label class="form-label" for="product-sku">SKU <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control text-uppercase" id="product-sku" name="sku" value="<?= e($values['sku'] ?? '') ?>" maxlength="50" placeholder="e.g. PF011" required></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-panel h-100">
            <h2 class="h5">Inventory and visibility</h2>
            <div class="row g-3">
                <div class="col-6"><label class="form-label" for="product-stock">Stock quantity <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="number" id="product-stock" name="stock_quantity" value="<?= e($values['stock_quantity'] ?? '0') ?>" min="0" max="4294967295" step="1" required><div class="form-hint">Zero automatically shows out of stock.</div></div>
                <div class="col-6"><label class="form-label" for="product-threshold">Low-stock threshold <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="number" id="product-threshold" name="low_stock_threshold" value="<?= e($values['low_stock_threshold'] ?? '5') ?>" min="0" max="4294967295" step="1" required></div>
                <div class="col-6"><label class="form-label" for="product-stock-type">Stock figures <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="product-stock-type" name="stock_type"><option value="DEMO"<?= selected($values['stock_type'] ?? 'DEMO', 'DEMO') ?>>Demo data</option><option value="SOURCE"<?= selected($values['stock_type'] ?? 'DEMO', 'SOURCE') ?>>From a source</option></select></div>
                <div class="col-6"><label class="form-label" for="product-stock-source">Stock source</label><input class="form-control" id="product-stock-source" name="stock_source" value="<?= e($values['stock_source'] ?? '') ?>" maxlength="160" placeholder="e.g. retailer stock page"></div>
                <div class="col-12"><label class="form-label" for="product-status">Visibility <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="product-status" name="status" required><option value="Active"<?= selected($values['status'] ?? 'Active', 'Active') ?>>Active</option><option value="Inactive"<?= selected($values['status'] ?? 'Active', 'Inactive') ?>>Inactive</option></select><div class="form-hint">Inactive products stay hidden from the public shop.</div></div>
                <div class="col-6"><label class="form-label" for="product-rating">Rating</label><input class="form-control" type="number" id="product-rating" name="rating" value="<?= e($values['rating'] ?? '') ?>" min="0" max="5" step="0.1" placeholder="Leave empty if unknown"></div>
                <div class="col-6"><label class="form-label" for="product-review-count">Review count</label><input class="form-control" type="number" id="product-review-count" name="review_count" value="<?= e($values['review_count'] ?? '') ?>" min="0" max="999999999" step="1"></div>
                <div class="col-12"><label class="form-label" for="product-rating-type">Rating type</label><select class="form-select" id="product-rating-type" name="rating_type"><option value="DEMO"<?= selected($values['rating_type'] ?? 'DEMO', 'DEMO') ?>>Demo rating</option><option value="SOURCE"<?= selected($values['rating_type'] ?? 'DEMO', 'SOURCE') ?>>From a source</option></select><div class="form-hint">Only record a rating you can link to a source page.</div></div>
            </div>
        </div>
    </div>
</div>
<div class="dashboard-panel mt-4">
    <h2 class="h5">Price source</h2>
    <p class="text-muted small">Keep every price traceable. Mark a price as demo when no verified source price exists, and never present a demo price as a real quote.</p>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="product-price-type">Price type <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="product-price-type" name="price_type"><option value="DEMO"<?= selected($values['price_type'] ?? 'DEMO', 'DEMO') ?>>Demo price (no verified source)</option><option value="SOURCE"<?= selected($values['price_type'] ?? 'DEMO', 'SOURCE') ?>>Verified source price</option></select></div>
        <div class="col-md-8"><label class="form-label" for="product-price-source">Price source link or reference</label><input class="form-control" id="product-price-source" name="price_source" value="<?= e($values['price_source'] ?? '') ?>" maxlength="500" placeholder="https://example.com/product or 'Chewy, checked 2026-09-20'"></div>
        <div class="col-md-3"><label class="form-label" for="product-source-price">Source price</label><input class="form-control" type="number" id="product-source-price" name="source_price" value="<?= e($values['source_price'] ?? '') ?>" min="0" max="99999999.99" step="0.01"></div>
        <div class="col-md-3"><label class="form-label" for="product-source-currency">Source currency</label><input class="form-control text-uppercase" id="product-source-currency" name="source_currency" value="<?= e($values['source_currency'] ?? '') ?>" maxlength="3" placeholder="USD"></div>
        <div class="col-md-3"><label class="form-label" for="product-exchange-rate">Exchange rate (LKR per 1 <?= e($values['source_currency'] ?? 'source unit') ?>)</label><input class="form-control" type="number" id="product-exchange-rate" name="source_exchange_rate" value="<?= e($values['source_exchange_rate'] ?? '') ?>" min="0" max="99999999.9999" step="0.0001"></div>
        <div class="col-md-3"><label class="form-label" for="product-price-checked">Price checked on</label><input class="form-control" type="date" id="product-price-checked" name="price_checked_on" value="<?= e($values['price_checked_on'] ?? '') ?>"></div>
    </div>
</div>
<div class="dashboard-panel mt-4">
    <h2 class="h5">Source attribution</h2>
    <p class="text-muted small">Record where the product details and the image came from so the catalog stays honest and the image can be localised later.</p>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="product-source-url">Product page URL</label><input class="form-control" type="url" id="product-source-url" name="source_url" value="<?= e($values['source_url'] ?? '') ?>" maxlength="500" placeholder="https://www.brand.com/products/..."></div>
        <div class="col-md-6"><label class="form-label" for="product-image-source-name">Image source name</label><input class="form-control" id="product-image-source-name" name="image_source_name" value="<?= e($values['image_source_name'] ?? '') ?>" maxlength="120" placeholder="e.g. Royal Canin (official)"></div>
        <div class="col-md-6"><label class="form-label" for="product-image-source-url">Image source page URL</label><input class="form-control" type="url" id="product-image-source-url" name="image_source_url" value="<?= e($values['image_source_url'] ?? '') ?>" maxlength="500" placeholder="Page the image file was taken from"></div>
        <div class="col-md-6"><label class="form-label" for="product-image-alt">Image alt text</label><input class="form-control" id="product-image-alt" name="image_alt" value="<?= e($values['image_alt'] ?? '') ?>" maxlength="160" placeholder="Describe the product photo for screen readers"><div class="form-hint">Leave empty and PAWORA generates a short alt text from the brand and product name.</div></div>
    </div>
</div>
<div class="dashboard-panel mt-4">
    <h2 class="h5">Product image</h2>
    <p class="text-muted small">Upload a JPG, PNG or WEBP image up to 4 MB, or use an HTTP/HTTPS image URL. Leave both options empty to keep the current image.</p>
    <div class="row g-3 align-items-start">
        <div class="col-md-4">
            <?php if (!empty($values['image'])): ?><img class="product-form-preview" src="<?= e(product_image_url($values['image'])) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt="Current product image"><?php else: ?><div class="product-form-preview product-form-preview-empty"><i class="bi bi-image" aria-hidden="true"></i><span>No image selected</span></div><?php endif; ?>
        </div>
        <div class="col-md-8">
            <div class="mb-3">
                <label class="form-label" for="product-image">Upload image</label>
                <input class="form-control" type="file" id="product-image" name="image" accept="image/jpeg,image/png,image/webp" data-upload-input data-max-size="<?= (int) upload_limit_bytes() ?>" data-allowed-types="image/jpeg,image/png,image/webp">
                <div class="form-text">Choose a JPG, PNG or WEBP file (max <?= e(human_bytes(upload_limit_bytes())) ?><?= upload_limit_is_server_capped() ? ', set by the server' : '' ?>). The image is stored when you press the save button.</div>
                <div class="form-text" data-upload-preview="image"></div>
                <div class="invalid-feedback d-block" data-upload-feedback="image"></div>
            </div>
            <div class="mb-3"><label class="form-label" for="product-image-url">Or image URL</label><input class="form-control" type="url" id="product-image-url" name="image_url" value="<?= e($values['image_url'] ?? '') ?>" maxlength="500" placeholder="https://example.com/product.jpg"></div>
            <?php if (!empty($values['image'])): ?><div class="form-check"><input class="form-check-input" type="checkbox" id="product-remove-image" name="remove_image" value="1"<?= checked((int) ($values['remove_image'] ?? 0) === 1) ?>><label class="form-check-label" for="product-remove-image">Remove current image when saving</label></div><?php endif; ?>
        </div>
    </div>
</div>
