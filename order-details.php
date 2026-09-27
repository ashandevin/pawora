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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_valid_or_fail();
    if (($_POST['form'] ?? '') === 'cancel') {
        $result = cancel_own_order($orderId, (int) $_SESSION['user_id']);
        if ($result['errors']) {
            set_flash('error', implode(' ', $result['errors']));
        } else {
            set_flash('success', 'Order ' . $order['order_reference'] . ' was cancelled and its items returned to stock.');
        }
        redirect('order-details.php?id=' . $orderId);
    }
    if (!is_admin()) {
        set_flash('error', 'You do not have permission to update orders.');
        redirect('order-details.php?id=' . $orderId);
    }
    require_admin();
    $result = update_order_status($orderId, (string) ($_POST['status'] ?? ''), (int) $_SESSION['user_id']);
    if ($result['errors']) {
        set_flash('error', implode(' ', $result['errors']));
    } else {
        set_flash('success', 'Order status updated to ' . status_label((string) $_POST['status']) . '.');
    }
    redirect('order-details.php?id=' . $orderId);
}

$items = get_order_items($orderId);
$payment = get_order_payment($orderId);
$statuses = order_statuses();
$currentStatus = strtoupper((string) $order['status']);
$isAdminViewer = is_admin();
$pageTitle = 'Order ' . $order['order_reference'];
$activePage = $isAdminViewer ? '' : 'account';
$bodyClass = $isAdminViewer ? 'dashboard-page admin-page' : 'dashboard-page';
require __DIR__ . '/includes/header.php';
?>
<?php if ($isAdminViewer): ?>
<div class="dashboard-wrap">
    <div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation">
        <div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div>
        <div class="offcanvas-body p-0"><?php require __DIR__ . '/includes/admin-sidebar.php'; ?></div>
    </div>
    <div class="dashboard-main">
        <?php show_database_notice(); ?>
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('admin/orders.php')) ?>">Orders</a></li><li class="breadcrumb-item active" aria-current="page"><?= e($order['order_reference']) ?></li></ol></nav>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><span class="eyebrow">Order detail</span><h1 class="dashboard-title mt-2 mb-1"><?= e($order['order_reference']) ?></h1><p class="text-muted mb-0">Placed <?= e(format_date($order['created_at'], 'M j, Y g:ia')) ?> by <?= e($order['user_name']) ?>.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="<?= e(app_url('invoice.php?id=' . $orderId)) ?>"><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Invoice</a>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/orders.php')) ?>">Back to orders</a>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php require __DIR__ . '/includes/order-items-panel.php'; ?>
            </div>
            <div class="col-lg-4">
                <?php require __DIR__ . '/includes/order-status-panel.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="dashboard-wrap">
    <div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="userSidebar" aria-label="Account navigation">
        <div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar" aria-label="Close"></button></div>
        <div class="offcanvas-body p-0"><?php require __DIR__ . '/includes/user-sidebar.php'; ?></div>
    </div>
    <div class="dashboard-main">
        <?php show_database_notice(); ?>
        <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('user/orders.php')) ?>">My orders</a></li><li class="breadcrumb-item active" aria-current="page"><?= e($order['order_reference']) ?></li></ol></nav>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><span class="eyebrow">Order detail</span><h1 class="dashboard-title mt-2 mb-1"><?= e($order['order_reference']) ?></h1><p class="text-muted mb-0">Placed on <?= e(format_date($order['created_at'], 'M j, Y g:ia')) ?>.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="<?= e(app_url('invoice.php?id=' . $orderId)) ?>"><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>View invoice</a>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('shop.php')) ?>">Shop again</a>
                <?php if (order_can_be_cancelled_by_user($order)): ?>
                    <form method="post" action="<?= e(app_url('order-details.php?id=' . $orderId)) ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form" value="cancel">
                        <button class="btn btn-outline-danger" type="submit" data-confirm="Cancel order <?= e($order['order_reference']) ?>? The items will be returned to our shelves and this cannot be undone." data-confirm-title="Cancel this order?"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel order</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <?php require __DIR__ . '/includes/order-items-panel.php'; ?>
            </div>
            <div class="col-lg-4">
                <?php require __DIR__ . '/includes/order-status-panel.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if (!$isAdminViewer) {
    $confirmModalTitle = 'Cancel this order?';
    $confirmModalCancel = 'Keep my order';
    $confirmModalSubmit = 'Cancel order';
    require __DIR__ . '/includes/confirm-modal.php';
} ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
