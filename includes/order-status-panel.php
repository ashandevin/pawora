<?php
declare(strict_types=1);

$order = $order ?? [];
$payment = $payment ?? null;
$statuses = $statuses ?? order_statuses();
$currentStatus = strtoupper((string) ($order['status'] ?? ''));
$isAdminViewer = $isAdminViewer ?? is_admin();
?>
<div class="dashboard-panel">
    <h2 class="panel-title">Status</h2>
    <div class="d-flex flex-wrap gap-2 mb-3"><?= status_badge((string) $order['status']) ?><?= payment_status_badge((string) $order['payment_status']) ?></div>
    <?php if ($payment): ?>
        <dl class="detail-list">
            <div><dt>Method</dt><dd><?= e(payment_method_label((string) $payment['method'])) ?></dd></div>
            <div><dt>Payment ref</dt><dd><?= e($payment['reference']) ?></dd></div>
            <div><dt>Amount</dt><dd><?= e(format_money((float) $payment['amount'])) ?></dd></div>
            <?php if (!empty($payment['card_last_four'])): ?><div><dt>Card</dt><dd><?= e((string) $payment['card_brand']) ?> ····<?= e((string) $payment['card_last_four']) ?></dd></div><?php endif; ?>
            <div><dt>Paid at</dt><dd><?= e(format_date($payment['paid_at'], 'M j, Y g:ia')) ?></dd></div>
        </dl>
    <?php else: ?>
        <p class="text-muted">No payment recorded yet.</p>
    <?php endif; ?>
    <p class="table-meta mb-0">Placed <?= e(format_date($order['created_at'], 'M j, Y g:ia')) ?> · Last updated <?= e(format_date($order['updated_at'] ?? null, 'M j, Y g:ia')) ?></p>
</div>

<?php if ($isAdminViewer): ?>
    <div class="dashboard-panel mt-4">
        <h2 class="panel-title">Update status</h2>
        <?php if ($currentStatus === 'CANCELLED'): ?>
            <p class="text-muted mb-0">This order is cancelled and its stock has been returned to inventory.</p>
        <?php else: ?>
            <form method="post" action="<?= e(app_url('order-details.php?id=' . (int) $order['id'])) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="order-status">Order status</label>
                    <select class="form-select" id="order-status" name="status">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($status, $currentStatus) ?>><?= e(status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($currentStatus === 'PENDING' || $currentStatus === 'PAID'): ?>
                    <p class="table-meta">Choosing <strong>Shipped</strong> requires a paid order. Set the order to <strong>Processing</strong> first if payment is still pending.</p>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save status</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
