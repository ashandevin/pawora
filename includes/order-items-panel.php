<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $items ?? [];
$payment = $payment ?? null;
$statuses = $statuses ?? order_statuses();
$currentStatus = strtoupper((string) ($order['status'] ?? ''));
$isAdminViewer = $isAdminViewer ?? is_admin();
?>
<div class="dashboard-panel">
    <h2 class="panel-title">Items</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Product</th><th scope="col" class="text-center">Qty</th><th scope="col" class="text-end">Unit price</th><th scope="col" class="text-end">Line total</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?= e($item['product_name']) ?></strong>
                            <div class="table-meta">SKU: <?= e($item['sku']) ?><?php if ((int) $item['product_id'] > 0): ?> · <a href="<?= e(app_url('product-details.php?id=' . (int) $item['product_id'])) ?>">View product</a><?php endif; ?></div>
                        </td>
                        <td class="text-center"><?= (int) $item['quantity'] ?></td>
                        <td class="text-end"><?= e(format_money((float) $item['unit_price'])) ?></td>
                        <td class="text-end"><?= e(format_money((float) $item['line_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th scope="row" colspan="3" class="text-end">Subtotal</th><td class="text-end"><?= e(format_money((float) $order['subtotal'])) ?></td></tr>
                <tr><th scope="row" colspan="3" class="text-end">Delivery</th><td class="text-end"><?= (float) $order['shipping'] > 0 ? e(format_money((float) $order['shipping'])) : 'Free' ?></td></tr>
                <tr class="fw-bold"><th scope="row" colspan="3" class="text-end">Total</th><td class="text-end"><?= e(format_money((float) $order['total'])) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="dashboard-panel mt-4">
    <h2 class="panel-title">Delivery details</h2>
    <dl class="detail-list">
        <div><dt>Billed to</dt><dd><?= e($order['billing_name']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($order['billing_email']) ?></dd></div>
        <?php if (!empty($order['billing_phone'])): ?><div><dt>Phone</dt><dd><?= e($order['billing_phone']) ?></dd></div><?php endif; ?>
        <div><dt>Address</dt><dd><?= nl2br(e($order['shipping_address'])) ?></dd></div>
        <?php if (!empty($order['notes'])): ?><div><dt>Notes</dt><dd><?= nl2br(e($order['notes'])) ?></dd></div><?php endif; ?>
        <div><dt>Customer</dt><dd><?= e($order['user_name'] ?? '') ?> (<?= e($order['user_email'] ?? '') ?>)</dd></div>
    </dl>
</div>
