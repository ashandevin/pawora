<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete_waiting') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $statement = db()->prepare('DELETE FROM waiting_list WHERE id = ?');
            $statement->execute([$id]);
            if ($statement->rowCount() < 1) {
                set_flash('error', 'That waiting-list entry no longer exists.');
            } else {
                add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted waiting-list entry #' . $id);
                set_flash('success', 'Waiting-list entry removed.');
            }
        } catch (Throwable $exception) {
            set_flash('error', 'We could not remove that waiting-list entry right now.');
        }
    }
    redirect('admin/waiting-list.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'product_waiting') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $statement = db()->prepare('DELETE FROM product_waiting_list WHERE id = ?');
            $statement->execute([$id]);
            if ($statement->rowCount() < 1) {
                set_flash('error', 'That product notification no longer exists.');
            } else {
                add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted product notification #' . $id);
                set_flash('success', 'Product notification removed.');
            }
        } catch (Throwable $exception) {
            set_flash('error', 'We could not remove that product notification right now.');
        }
    }
    redirect('admin/waiting-list.php');
}
$entries = db_all('SELECT w.*, p.name AS pet_name, p.species, p.image FROM waiting_list w JOIN pets p ON p.id = w.pet_id ORDER BY w.created_at DESC');
$productEntries = db_all('SELECT n.*, p.name AS product_name, p.sku, p.stock_quantity, p.status AS product_status FROM product_waiting_list n JOIN products p ON p.id = n.product_id ORDER BY n.created_at DESC');
$productNotifyStats = db_one("SELECT COUNT(*) AS total, COUNT(DISTINCT product_id) AS products FROM product_waiting_list") ?? ['total' => 0, 'products' => 0];
$adminSection = 'waiting';
$pageTitle = 'Waiting list';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Waiting list</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">Unavailable pet interest</span><h1 class="dashboard-title mt-2 mb-1">Waiting list</h1><p class="text-muted mb-0">People who asked to be considered when a pet’s adoption plan changes.</p></div><section class="dashboard-panel"><?php if ($entries): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Person</th><th>Pet</th><th>Contact</th><th>Joined</th><th></th></tr></thead><tbody><?php foreach ($entries as $entry): ?><tr><td><strong class="d-block"><?= e($entry['name']) ?></strong><small class="text-muted"><?= e($entry['email']) ?></small></td><td><div class="d-flex align-items-center gap-2"><img class="pet-table-image" src="<?= e(pet_image_url($entry['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="" width="48" height="48"><span><?= e($entry['pet_name']) ?><small class="d-block text-muted"><?= e($entry['species']) ?></small></span></div></td><td><?= e($entry['phone']) ?></td><td><small class="text-muted"><?= e(format_date($entry['created_at'])) ?></small></td><td><form method="post" action="<?= e(app_url('admin/waiting-list.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="delete_waiting"><input type="hidden" name="id" value="<?= e((string) $entry['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Remove this waiting-list entry?"><i class="bi bi-trash" aria-hidden="true"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><h2 class="h4">No waiting-list entries.</h2><p class="text-muted mb-0">Entries from unavailable pet profiles will appear here.</p></div><?php endif; ?></section></div></div>
<section class="dashboard-panel mt-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div><h2 class="h5 mb-1">Product restock notifications</h2><p class="text-muted small mb-0">Customers who asked to be told when an out-of-shop product returns.</p></div>
        <span class="small text-muted"><?= e((string) $productNotifyStats['total']) ?> entries across <?= e((string) $productNotifyStats['products']) ?> products</span>
    </div>
    <?php if ($productEntries): ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Person</th><th>Product</th><th>Contact</th><th>Stock now</th><th>Joined</th><th></th></tr></thead><tbody>
        <?php foreach ($productEntries as $entry): ?>
            <tr>
                <td><strong class="d-block"><?= e($entry['name']) ?></strong><small class="text-muted"><?= e($entry['email']) ?></small></td>
                <td><strong class="d-block"><?= e($entry['product_name']) ?></strong><small class="text-muted"><?= e($entry['sku']) ?></small></td>
                <td><?= e($entry['phone']) ?></td>
                <td><?= (int) $entry['stock_quantity'] > 0 ? '<span class="status-in-stock">In stock</span>' : '<span class="status-out-of-stock">Out of stock</span>' ?></td>
                <td><small class="text-muted"><?= e(format_date($entry['created_at'])) ?></small></td>
                <td class="text-end"><form method="post" action="<?= e(app_url('admin/waiting-list.php')) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="form" value="product_waiting"><input type="hidden" name="id" value="<?= e((string) $entry['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" title="Remove notification"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Remove notification for <?= e($entry['product_name']) ?></span></button></form></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?>
        <p class="text-muted mb-0">No product notifications yet. Out-of-stock product pages show a Join waiting list form for customers.</p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
