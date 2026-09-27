<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/order-helpers.php';

require_auth();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$order = get_order($orderId);
if (!$order || !can_view_order($order)) {
    set_flash('error', 'That order could not be found.');
    redirect(is_admin() ? 'admin/orders.php' : 'user/orders.php');
}

$items = get_order_items($orderId);
$payment = get_order_payment($orderId);
$pageTitle = 'Order ' . $order['order_reference'];
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
$paid = strtoupper((string) $order['payment_status']) === 'PAID';
?>
<section class="section-space-sm">
    <div class="container">
        <div class="order-success-card">
            <div class="order-success-icon <?= $paid ? 'is-paid' : 'is-pending' ?>"><i class="bi <?= $paid ? 'bi-check-lg' : 'bi-truck' ?>" aria-hidden="true"></i></div>
            <h1 class="h3 mt-3"><?= $paid ? 'Thank you, your order is confirmed.' : 'Your order is recorded.' ?></h1>
            <p class="text-muted">We have sent the details to <?= e($order['billing_email']) ?>. Keep reference <strong><?= e($order['order_reference']) ?></strong> for your records.</p>
            <div class="order-success-badges"><?= status_badge((string) $order['status']) ?><?= payment_status_badge((string) $order['payment_status']) ?></div>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <a class="btn btn-primary" href="<?= e(app_url('order-details.php?id=' . $orderId)) ?>"><i class="bi bi-receipt me-1" aria-hidden="true"></i>View order details</a>
                <a class="btn btn-outline-primary" href="<?= e(app_url('invoice.php?id=' . $orderId)) ?>"><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Open invoice</a>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('shop.php')) ?>">Continue shopping</a>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <div class="dashboard-panel">
                    <h2 class="panel-title">Items in this order</h2>
                    <ul class="checkout-items">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span class="checkout-item-name"><?= e($item['product_name']) ?> <span class="text-muted">× <?= (int) $item['quantity'] ?></span></span>
                                <span><?= e(format_money((float) $item['line_total'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="cart-summary-list mt-3">
                        <div><dt>Subtotal</dt><dd><?= e(format_money((float) $order['subtotal'])) ?></dd></div>
                        <div><dt>Delivery</dt><dd><?= (float) $order['shipping'] > 0 ? e(format_money((float) $order['shipping'])) : '<span class="text-success">Free</span>' ?></dd></div>
                        <div class="cart-summary-total"><dt>Total</dt><dd><?= e(format_money((float) $order['total'])) ?></dd></div>
                    </dl>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="dashboard-panel">
                    <h2 class="panel-title">Payment</h2>
                    <?php if ($payment): ?>
                        <dl class="detail-list">
                            <div><dt>Method</dt><dd><?= e(payment_method_label((string) $payment['method'])) ?></dd></div>
                            <div><dt>Reference</dt><dd><?= e($payment['reference']) ?></dd></div>
                            <div><dt>Amount</dt><dd><?= e(format_money((float) $payment['amount'])) ?></dd></div>
                            <?php if (!empty($payment['card_last_four'])): ?>
                                <div><dt>Card</dt><dd><?= e((string) $payment['card_brand']) ?> ending <?= e((string) $payment['card_last_four']) ?></dd></div>
                            <?php endif; ?>
                            <div><dt>Status</dt><dd><?= payment_status_badge((string) $payment['status']) ?></dd></div>
                        </dl>
                    <?php else: ?>
                        <p class="text-muted mb-0">No payment has been recorded yet.</p>
                    <?php endif; ?>
                    <h2 class="panel-title mt-4">Delivery address</h2>
                    <p class="text-muted mb-0"><?= nl2br(e($order['shipping_address'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
