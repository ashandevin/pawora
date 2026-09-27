<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order-helpers.php';

require_auth();

$orders = user_orders((int) $_SESSION['user_id']);
$userSection = 'orders';
$pageTitle = 'My orders';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap">
    <div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="userSidebar" aria-label="Account navigation">
        <div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar" aria-label="Close"></button></div>
        <div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/user-sidebar.php'; ?></div>
    </div>
    <div class="dashboard-main">
        <?php show_database_notice(); ?>
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">My PAWORA</span><h1 class="dashboard-title mb-0">My orders</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSidebar" aria-controls="userSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open account navigation</span></button></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><span class="eyebrow">Order history</span><h1 class="dashboard-title mt-2 mb-1">My orders</h1><p class="text-muted mb-0">Every PAWORA shop order, its payment status and invoice in one place.</p></div>
            <a class="btn btn-primary" href="<?= e(app_url('shop.php')) ?>"><i class="bi bi-bag-plus me-1" aria-hidden="true"></i>Shop products</a>
        </div>

        <?php if (!$orders): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-bag" aria-hidden="true"></i></div>
                <h2 class="h5">No orders yet</h2>
                <p class="text-muted">When you order pet care products, they will appear here with tracking and invoice details.</p>
                <a class="btn btn-primary" href="<?= e(app_url('shop.php')) ?>">Browse the shop</a>
            </div>
        <?php else: ?>
            <div class="dashboard-panel">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col" class="text-center">Lines</th><th scope="col">Status</th><th scope="col">Payment</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?= e($order['order_reference']) ?></strong></td>
                                    <td><?= e(format_date($order['created_at'])) ?></td>
                                    <td class="text-center"><?= (int) $order['item_lines'] ?></td>
                                    <td><?= status_badge((string) $order['status']) ?></td>
                                    <td><?= payment_status_badge((string) $order['payment_status']) ?></td>
                                    <td class="text-end"><?= e(format_money((float) $order['total'])) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('order-details.php?id=' . (int) $order['id'])) ?>">Details</a>
                                        <a class="btn btn-sm btn-link text-decoration-none" href="<?= e(app_url('invoice.php?id=' . (int) $order['id'])) ?>">Invoice</a>
                                        <?php if (order_can_be_cancelled_by_user($order)): ?>
                                            <form method="post" action="<?= e(app_url('order-details.php?id=' . (int) $order['id'])) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="form" value="cancel">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Cancel order <?= e($order['order_reference']) ?>? The items will be returned to our shelves and this cannot be undone."><i class="bi bi-x-circle" aria-hidden="true"></i><span class="visually-hidden">Cancel order <?= e($order['order_reference']) ?></span></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$confirmModalTitle = 'Cancel this order?';
$confirmModalCancel = 'Keep my order';
$confirmModalSubmit = 'Cancel order';
require __DIR__ . '/../includes/confirm-modal.php';
require __DIR__ . '/../includes/footer.php'; ?>
