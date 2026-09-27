<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/cart-helpers.php';

$items = cart_items();
$totals = cart_totals($items);
$foreignItems = cart_foreign_currency_items($items);
$ready = cart_is_checkout_ready($items) && $foreignItems === [];
$pageTitle = 'Your cart';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('index.php')) ?>">Home</a></li><li class="breadcrumb-item"><a href="<?= e(app_url('shop.php')) ?>">Shop</a></li><li class="breadcrumb-item active" aria-current="page">Cart</li></ol></nav>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div><span class="eyebrow">PAWORA shop</span><h1 class="page-title mt-3 mb-2">Your cart</h1><p class="text-muted mb-0">Review quantities before you continue to checkout.</p></div>
            <?php if ($items): ?><span class="cart-summary-pill"><i class="bi bi-bag-check" aria-hidden="true"></i><?= e((string) $totals['item_count']) ?> item<?= $totals['item_count'] === 1 ? '' : 's' ?></span><?php endif; ?>
        </div>
    </div>
</section>

<section class="section-space-sm">
    <div class="container">
        <?php show_database_notice(); ?>
        <?php if ($foreignItems): ?>
            <div class="alert alert-warning" role="alert"><strong>Some items are not sold in <?= e(store_currency()) ?>.</strong><ul class="mb-0 mt-2"><?php foreach ($foreignItems as $foreignItem): ?><li><?= e($foreignItem['name']) ?> is listed in <?= e(product_currency($foreignItem)) ?>. PAWORA can only complete orders in <?= e(store_currency()) ?>, so please remove this item for now.</li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if (!$items): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bag" aria-hidden="true"></i></div>
                <h2 class="h4">Your cart is empty</h2>
                <p class="text-muted">Add pet care essentials from the PAWORA shop and they will appear here.</p>
                <a class="btn btn-primary" href="<?= e(app_url('shop.php')) ?>"><i class="bi bi-bag-plus me-1" aria-hidden="true"></i>Browse the shop</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <?php if (!$ready): ?>
                        <div class="alert alert-warning border-0 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Some items are no longer available in the requested quantity. Update or remove them to continue.</div>
                    <?php endif; ?>
                    <div class="cart-items">
                        <?php foreach ($items as $item): ?>
                            <article class="cart-item <?= empty($item['available']) ? 'cart-item-unavailable' : '' ?>">
                                <a class="cart-item-image" href="<?= e(app_url('product-details.php?id=' . (int) $item['id'])) ?>">
                                    <img src="<?= e(product_image_url($item['image'])) ?>" data-fallback="<?= e(product_image_fallback()) ?>" alt="<?= e($item['name']) ?>" width="160" height="160" loading="lazy">
                                </a>
                                <div class="cart-item-body">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div>
                                            <h2 class="cart-item-title"><a class="text-decoration-none" href="<?= e(app_url('product-details.php?id=' . (int) $item['id'])) ?>"><?= e($item['name']) ?></a></h2>
                                            <p class="cart-item-meta">SKU: <?= e($item['sku']) ?> · <?= e(format_money(product_price($item), product_currency($item))) ?> each<?= product_brand($item) !== 'PAWORA' ? ' · ' . e(product_brand($item)) : '' ?></p>
                                        </div>
                                        <div class="cart-item-total"><?= e(format_money((float) $item['line_total'], product_currency($item))) ?></div>
                                    </div>
                                    <?php if (!empty($item['availability_message'])): ?>
                                        <p class="cart-item-warning"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><?= e((string) $item['availability_message']) ?></p>
                                    <?php else: ?>
                                        <p class="cart-item-meta"><?= e(product_stock_label($item)) ?></p>
                                    <?php endif; ?>
                                    <div class="cart-item-actions">
                                        <form method="post" action="<?= e(app_url('cart-action.php')) ?>" class="cart-quantity-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <label class="visually-hidden" for="cart-quantity-<?= (int) $item['id'] ?>">Quantity for <?= e($item['name']) ?></label>
                                            <input class="form-control form-control-sm" id="cart-quantity-<?= (int) $item['id'] ?>" type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="0" max="<?= max(0, (int) $item['stock_quantity']) ?>">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                                        </form>
                                        <form method="post" action="<?= e(app_url('cart-action.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <button class="btn btn-sm btn-link text-danger text-decoration-none" type="submit"><i class="bi bi-trash3 me-1" aria-hidden="true"></i>Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4">
                        <a class="btn btn-outline-secondary" href="<?= e(app_url('shop.php')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Continue shopping</a>
                        <form method="post" action="<?= e(app_url('cart-action.php')) ?>" data-confirm="Clear every item from your cart?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="clear">
                            <button class="btn btn-link text-danger text-decoration-none" type="submit">Clear cart</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4">
                    <aside class="cart-summary" aria-labelledby="cart-summary-title">
                        <h2 class="cart-summary-title" id="cart-summary-title">Order summary</h2>
                        <dl class="cart-summary-list">
                            <div><dt>Subtotal</dt><dd><?= e(format_money((float) $totals['subtotal'])) ?></dd></div>
                            <div><dt>Delivery</dt><dd><?= $totals['shipping'] > 0 ? e(format_money((float) $totals['shipping'])) : '<span class="text-success">Free</span>' ?></dd></div>
                            <div class="cart-summary-total"><dt>Total</dt><dd><?= e(format_money((float) $totals['total'])) ?></dd></div>
                        </dl>
                        <p class="cart-summary-note"><i class="bi bi-truck" aria-hidden="true"></i>Free delivery on orders over <?= e(format_money(cart_free_shipping_threshold())) ?>. Delivery is <?= e(format_money(cart_shipping_fee())) ?> otherwise.</p>
                        <?php if (is_logged_in()): ?>
                            <a class="btn btn-primary w-100" href="<?= e(app_url('checkout.php')) ?>" <?= $ready ? '' : 'aria-disabled="true" tabindex="-1"' ?>><i class="bi bi-credit-card me-1" aria-hidden="true"></i>Proceed to checkout</a>
                        <?php else: ?>
                            <a class="btn btn-primary w-100" href="<?= e(app_url('checkout.php')) ?>"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Sign in to checkout</a>
                            <p class="cart-summary-note text-center mb-0">Your cart stays with you after signing in.</p>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
