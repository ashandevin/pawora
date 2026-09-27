<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/order-helpers.php';

require_auth();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$order = get_order($orderId);
if (!$order || !can_view_order($order)) {
    set_flash('error', 'That invoice could not be found.');
    redirect(is_admin() ? 'admin/orders.php' : 'user/orders.php');
}

$items = get_order_items($orderId);
$payment = get_order_payment($orderId);
$pageTitle = 'Invoice ' . $order['order_reference'];
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<section class="section-space-sm">
    <div class="container">
        <div class="invoice-toolbar d-print-none">
            <div>
                <span class="eyebrow">Invoice</span>
                <h1 class="h4 mb-0 mt-2"><?= e($order['order_reference']) ?></h1>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="button" data-print-page><i class="bi bi-printer me-1" aria-hidden="true"></i>Print / save as PDF</button>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('order-details.php?id=' . $orderId)) ?>">Back to order</a>
            </div>
        </div>

        <div class="invoice-sheet">
            <header class="invoice-header">
                <div>
                    <span class="brand-word">PAWORA</span>
                    <p class="invoice-muted mb-0">Everything They Need. A Home They Deserve.<br>hello@pawora.test · +94 11 234 5678</p>
                </div>
                <div class="text-lg-end">
                    <h2 class="invoice-title">Payment invoice</h2>
                    <p class="invoice-muted mb-0">Invoice number: <?= e($order['order_reference']) ?><br>Issued: <?= e(format_date($order['created_at'], 'M j, Y')) ?><br>Status: <?= e(status_label((string) $order['status'])) ?> · <?= e(status_label((string) $order['payment_status'])) ?></p>
                </div>
            </header>

            <div class="row g-4 invoice-parties">
                <div class="col-md-6">
                    <h3 class="invoice-label">Billed to</h3>
                    <p class="mb-0"><strong><?= e($order['billing_name']) ?></strong><br><?= e($order['billing_email']) ?><?php if (!empty($order['billing_phone'])): ?><br><?= e($order['billing_phone']) ?><?php endif; ?><br><?= nl2br(e($order['shipping_address'])) ?></p>
                </div>
                <div class="col-md-6">
                    <h3 class="invoice-label">Payment method</h3>
                    <p class="mb-0">
                        <?php if ($payment): ?>
                            <?= e(payment_method_label((string) $payment['method'])) ?><br>
                            Payment reference: <?= e($payment['reference']) ?><br>
                            <?php if (!empty($payment['card_last_four'])): ?><?= e((string) $payment['card_brand']) ?> ending <?= e((string) $payment['card_last_four']) ?><br><?php endif; ?>
                            Paid at: <?= e(format_date($payment['paid_at'], 'M j, Y')) ?>
                        <?php else: ?>
                            Payment pending
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <table class="table invoice-table">
                <thead><tr><th scope="col">Item</th><th scope="col">SKU</th><th scope="col" class="text-center">Qty</th><th scope="col" class="text-end">Unit price</th><th scope="col" class="text-end">Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td><?= e($item['sku']) ?></td>
                            <td class="text-center"><?= (int) $item['quantity'] ?></td>
                            <td class="text-end"><?= e(format_money((float) $item['unit_price'])) ?></td>
                            <td class="text-end"><?= e(format_money((float) $item['line_total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th scope="row" colspan="4" class="text-end">Subtotal</th><td class="text-end"><?= e(format_money((float) $order['subtotal'])) ?></td></tr>
                    <tr><th scope="row" colspan="4" class="text-end">Delivery</th><td class="text-end"><?= (float) $order['shipping'] > 0 ? e(format_money((float) $order['shipping'])) : 'Free' ?></td></tr>
                    <tr class="invoice-total"><th scope="row" colspan="4" class="text-end">Total</th><td class="text-end"><?= e(format_money((float) $order['total'])) ?></td></tr>
                </tfoot>
            </table>

            <footer class="invoice-footer">
                <p class="mb-1"><strong>Thank you for supporting PAWORA.</strong> This invoice was generated automatically for order <?= e($order['order_reference']) ?>.</p>
                <p class="invoice-muted mb-0">Questions about this order? Email hello@pawora.test with your order reference and we will help.</p>
            </footer>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
