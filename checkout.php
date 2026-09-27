<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/order-helpers.php';

if (!is_logged_in()) {
    $_SESSION['return_to'] = 'checkout.php';
    set_flash('warning', 'Please sign in to complete checkout.');
    redirect('login.php');
}

$user = current_user() ?? [];
$items = cart_items();
if ($items === []) {
    set_flash('info', 'Your cart is empty. Add something from the shop first.');
    redirect('shop.php');
}
if (cart_foreign_currency_items($items) !== []) {
    set_flash('warning', 'One or more items are not sold in ' . store_currency() . '. Please review your cart before checking out.');
    redirect('cart.php');
}
if (!cart_is_checkout_ready($items)) {
    set_flash('warning', 'Some cart items need attention before checkout.');
    redirect('cart.php');
}

$totals = cart_totals($items);
$values = order_checkout_values($user);
$pageTitle = 'Checkout';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('index.php')) ?>">Home</a></li><li class="breadcrumb-item"><a href="<?= e(app_url('cart.php')) ?>">Cart</a></li><li class="breadcrumb-item active" aria-current="page">Checkout</li></ol></nav>
        <span class="eyebrow">Secure checkout</span>
        <h1 class="page-title mt-3 mb-2">Delivery and payment</h1>
        <p class="text-muted mb-0">Confirm where your order should go and how you would like to pay.</p>
    </div>
</section>

<section class="section-space-sm">
    <div class="container">
        <form method="post" action="<?= e(app_url('checkout-process.php')) ?>" novalidate data-loading>
            <?= csrf_field() ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="dashboard-panel mb-4">
                        <h2 class="panel-title">Billing details</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="billing_name">Full name</label>
                                <input class="form-control" id="billing_name" name="billing_name" value="<?= e($values['billing_name']) ?>" maxlength="120" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_email">Email</label>
                                <input class="form-control" id="billing_email" name="billing_email" type="email" value="<?= e($values['billing_email']) ?>" maxlength="190" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="billing_phone">Phone <span class="text-muted">(optional)</span></label>
                                <input class="form-control" id="billing_phone" name="billing_phone" value="<?= e($values['billing_phone']) ?>" maxlength="40">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_address">Delivery address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" maxlength="1000" required><?= e($values['shipping_address']) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">Delivery notes <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="1000"><?= e($values['notes']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-panel">
                        <h2 class="panel-title">Payment method</h2>
                        <div class="payment-methods">
                            <label class="payment-method" for="payment_demo_card">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_demo_card" value="demo_card" data-payment-method <?= checked($values['payment_method'] === 'demo_card') ?>>
                                <span class="payment-method-body"><strong>Demo card</strong><small>Simulated card payment for this coursework build. No real money moves.</small></span>
                            </label>
                            <label class="payment-method" for="payment_cod">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_cod" value="cash_on_delivery" data-payment-method <?= checked($values['payment_method'] === 'cash_on_delivery') ?>>
                                <span class="payment-method-body"><strong>Cash on delivery</strong><small>Pay the courier when your order arrives.</small></span>
                            </label>
                        </div>

                        <div class="card-fields" data-card-fields>
                            <p class="demo-payment-note"><i class="bi bi-info-circle" aria-hidden="true"></i>Use test card <strong>4242 4242 4242 4242</strong>, any future expiry date and any 3 digit security code. Only the card brand and last four digits are stored.</p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="cardholder">Name on card</label>
                                    <input class="form-control" id="cardholder" name="cardholder" maxlength="120" autocomplete="off">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="card_number">Card number</label>
                                    <input class="form-control" id="card_number" name="card_number" inputmode="numeric" placeholder="4242 4242 4242 4242" maxlength="23" autocomplete="off" data-card-number>
                                </div>
                                <div class="col-md-5">
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="form-label" for="card_expiry">Expiry</label>
                                            <input class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" inputmode="numeric" autocomplete="off" data-card-expiry>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label" for="card_cvv">CVV</label>
                                            <input class="form-control" id="card_cvv" name="card_cvv" inputmode="numeric" maxlength="4" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <aside class="cart-summary" aria-labelledby="checkout-summary-title">
                        <h2 class="cart-summary-title" id="checkout-summary-title">Your order</h2>
                        <ul class="checkout-items">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <span class="checkout-item-name"><?= e($item['name']) ?> <span class="text-muted">× <?= (int) $item['quantity'] ?></span></span>
                                    <span><?= e(format_money((float) $item['line_total'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <dl class="cart-summary-list">
                            <div><dt>Subtotal</dt><dd><?= e(format_money((float) $totals['subtotal'])) ?></dd></div>
                            <div><dt>Delivery</dt><dd><?= $totals['shipping'] > 0 ? e(format_money((float) $totals['shipping'])) : '<span class="text-success">Free</span>' ?></dd></div>
                            <div class="cart-summary-total"><dt>Total due</dt><dd><?= e(format_money((float) $totals['total'])) ?></dd></div>
                        </dl>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Place order</button>
                        <a class="btn btn-link w-100" href="<?= e(app_url('cart.php')) ?>">Back to cart</a>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
