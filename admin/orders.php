<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order-helpers.php';

require_admin();

$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
if (!in_array($statusFilter, order_statuses(), true)) {
    $statusFilter = '';
}
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$count = admin_order_count(['status' => $statusFilter, 'search' => $search]);
$totalPages = max(1, (int) ceil($count / $perPage));
$page = min($page, $totalPages);
$orders = admin_orders(['status' => $statusFilter, 'search' => $search], $perPage, ($page - 1) * $perPage);
$counts = admin_order_counts();

$adminSection = 'orders';
$pageTitle = 'Orders';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap">
    <div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation">
        <div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div>
        <div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div>
    </div>
    <div class="dashboard-main">
        <?php show_database_notice(); ?>
        <div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Orders</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div><span class="eyebrow">Commerce</span><h1 class="dashboard-title mt-2 mb-1">Orders</h1><p class="text-muted mb-0">Track payments, move orders through fulfilment and cancel with automatic restocking.</p></div>
        </div>

        <div class="stat-grid mb-4">
            <?php foreach (order_statuses() as $status): ?>
                <a class="stat-card <?= $statusFilter === $status ? 'is-active' : '' ?>" href="<?= e(app_url('admin/orders.php?status=' . $status)) ?>">
                    <span class="stat-card-label"><?= e(status_label($status)) ?></span>
                    <strong class="stat-card-value"><?= e((string) ($counts[$status] ?? 0)) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-panel">
            <form class="row g-2 align-items-end mb-3" method="get" action="<?= e(app_url('admin/orders.php')) ?>">
                <div class="col-md-5">
                    <label class="form-label" for="order-search">Search</label>
                    <input class="form-control" id="order-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Reference, name or email">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="order-status-filter">Status</label>
                    <select class="form-select" id="order-status-filter" name="status" data-auto-submit>
                        <option value="">All statuses</option>
                        <?php foreach (order_statuses() as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($status, $statusFilter) ?>><?= e(status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply</button>
                    <?php if ($search !== '' || $statusFilter !== ''): ?><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/orders.php')) ?>">Clear</a><?php endif; ?>
                </div>
            </form>

            <?php if (!$orders): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div>
                    <h2 class="h5">No orders found</h2>
                    <p class="text-muted mb-0">Orders placed from the public shop will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col">Payment</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?= e($order['order_reference']) ?></strong><button class="btn btn-link btn-sm p-0 ms-2 text-decoration-none" type="button" data-copy-reference="<?= e($order['order_reference']) ?>" aria-label="Copy order reference"><i class="bi bi-clipboard" aria-hidden="true"></i></button></td>
                                    <td><?= e($order['user_name']) ?><div class="table-meta"><?= e($order['user_email']) ?></div></td>
                                    <td><?= e(format_date($order['created_at'])) ?></td>
                                    <td><?= status_badge((string) $order['status']) ?></td>
                                    <td><?= payment_status_badge((string) $order['payment_status']) ?></td>
                                    <td class="text-end"><?= e(format_money((float) $order['total'])) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('order-details.php?id=' . (int) $order['id'])) ?>">Manage</a>
                                        <a class="btn btn-sm btn-link text-decoration-none" href="<?= e(app_url('invoice.php?id=' . (int) $order['id'])) ?>">Invoice</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Order pages">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                                <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= e(app_url('admin/orders.php?page=' . $pageNumber . ($statusFilter !== '' ? '&status=' . $statusFilter : '') . ($search !== '' ? '&q=' . urlencode($search) : ''))) ?>"><?= $pageNumber ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
