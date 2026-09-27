<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'feedback') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        if (in_array($action, ['APPROVED', 'HIDDEN', 'DELETE'], true)) {
            try {
                if ($action === 'DELETE') {
                    $statement = db()->prepare('DELETE FROM feedback WHERE id = ?');
                    $statement->execute([$id]);
                    if ($statement->rowCount() < 1) {
                        set_flash('error', 'That feedback entry no longer exists.');
                    } else {
                        add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted feedback #' . $id);
                        set_flash('success', 'Feedback deleted.');
                    }
                } else {
                    $statement = db()->prepare('UPDATE feedback SET status = ? WHERE id = ?');
                    $statement->execute([$action, $id]);
                    if ($statement->rowCount() < 1) {
                        set_flash('error', 'That feedback entry no longer exists.');
                    } else {
                        add_admin_log((int) $_SESSION['user_id'], 'UPDATE', 'Changed feedback #' . $id . ' to ' . $action);
                        set_flash('success', 'Feedback updated.');
                    }
                }
            } catch (Throwable $exception) {
                set_flash('error', 'We could not update that feedback right now.');
            }
        } else {
            set_flash('error', 'Choose a valid feedback action.');
        }
    }
    redirect('admin/feedback.php');
}
$feedback = db_all('SELECT f.*, u.name AS user_name FROM feedback f LEFT JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC');
$adminSection = 'feedback';
$pageTitle = 'Feedback';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Feedback</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">Community voice</span><h1 class="dashboard-title mt-2 mb-1">Feedback</h1><p class="text-muted mb-0">Approve useful comments for the public website or keep the queue tidy.</p></div><section class="dashboard-panel"><?php if ($feedback): ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Rating</th><th>Comment</th><th>From</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($feedback as $item): ?><tr><td><span class="feedback-stars" aria-label="<?= e((string) $item['rating']) ?> out of 5 stars"><?php for ($star = 1; $star <= 5; $star++): ?><i class="bi <?= $star <= $item['rating'] ? 'bi-star-fill' : 'bi-star empty' ?>" aria-hidden="true"></i><?php endfor; ?></span></td><td><span class="d-block" style="max-width:320px"><?= e($item['comment']) ?></span><small class="text-muted"><?= e(format_date($item['created_at'])) ?></small></td><td><?= e($item['user_name'] ?? 'Guest') ?></td><td><?= status_badge((string) $item['status']) ?></td><td><div class="d-flex gap-1"><form method="post" action="<?= e(app_url('admin/feedback.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="feedback"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>"><input type="hidden" name="action" value="APPROVED"><button class="btn btn-sm btn-outline-primary" type="submit">Approve</button></form><form method="post" action="<?= e(app_url('admin/feedback.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="feedback"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>"><input type="hidden" name="action" value="HIDDEN"><button class="btn btn-sm btn-outline-secondary" type="submit">Hide</button></form><form method="post" action="<?= e(app_url('admin/feedback.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="feedback"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>"><input type="hidden" name="action" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete this feedback?"><i class="bi bi-trash" aria-hidden="true"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><h2 class="h4">No feedback yet.</h2><p class="text-muted mb-0">New comments will appear here.</p></div><?php endif; ?></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
