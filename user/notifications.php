<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_auth();
$userId = (int) $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'read_all') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        try {
            $statement = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
            $statement->execute([$userId]);
            set_flash('success', 'All notifications marked as read.');
        } catch (Throwable $exception) {
            set_flash('error', 'We could not update your notifications right now.');
        }
    }
    redirect('user/notifications.php');
}
$notifications = db_all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
$userSection = 'notifications';
$pageTitle = 'Notifications';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="userSidebar" aria-label="Account navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/user-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">My PAWORA</span><h1 class="dashboard-title mb-0">Notifications</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSidebar" aria-controls="userSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open account navigation</span></button></div><div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">Stay informed</span><h1 class="dashboard-title mt-2 mb-1">Notifications</h1><p class="text-muted mb-0">Application updates and gentle reminders from PAWORA.</p></div><?php if (unread_notification_count() > 0): ?><form method="post" action="<?= e(app_url('user/notifications.php')) ?>"><input type="hidden" name="form" value="read_all"><?= csrf_field() ?><button class="btn btn-outline-primary" type="submit">Mark all as read</button></form><?php endif; ?></div><section class="dashboard-panel"><?php if ($notifications): ?><div class="d-flex flex-column"><?php foreach ($notifications as $notification): ?><article class="d-flex gap-3 py-3 <?= $notification['is_read'] ? '' : 'border-bottom' ?>"><span class="stat-icon flex-shrink-0" style="width:38px;height:38px"><i class="bi <?= $notification['is_read'] ? 'check-circle' : 'bell-fill' ?>" aria-hidden="true"></i></span><div class="flex-grow-1"><div class="d-flex flex-wrap justify-content-between gap-2"><h2 class="h6 mb-1"><?= e($notification['title']) ?></h2><small class="text-muted"><?= e(format_date($notification['created_at'], 'M j, Y · g:i A')) ?></small></div><p class="small text-muted mb-0"><?= e($notification['message']) ?></p><?php if (!$notification['is_read']): ?><span class="status-badge mt-2"><i class="bi bi-circle-fill" style="font-size:.35rem" aria-hidden="true"></i>New</span><?php endif; ?></div></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><div class="empty-state-icon"><i class="bi bi-bell-slash" aria-hidden="true"></i></div><h2 class="h4">You are all caught up.</h2><p class="text-muted mb-0">New PAWORA updates will appear here.</p></div><?php endif; ?></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
