<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'message_status') {
    if (!verify_csrf()) set_flash('error', 'Your form session expired. Please try again.');
    else {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (in_array($status, ['NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], true)) {
            try {
                $statement = db()->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
                $statement->execute([$status, $id]);
                add_admin_log((int) $_SESSION['user_id'], 'UPDATE', 'Updated contact message #' . $id);
                set_flash('success', 'Message status updated.');
            } catch (Throwable $exception) {
                set_flash('error', 'We could not update that message right now.');
            }
        } else {
            set_flash('error', 'Choose a valid message status.');
        }
    }
    redirect('admin/messages.php');
}
$messages = db_all('SELECT * FROM contact_messages ORDER BY created_at DESC');
$adminSection = 'messages';
$pageTitle = 'Contact messages';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Messages</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">Support inbox</span><h1 class="dashboard-title mt-2 mb-1">Contact messages</h1><p class="text-muted mb-0">Keep questions, complaints and adoption issues moving toward resolution.</p></div><section class="dashboard-panel"><?php if ($messages): ?><div class="accordion" id="messageAccordion"><?php foreach ($messages as $index => $message): ?><div class="accordion-item mb-2"><h3 class="accordion-header"><button class="accordion-button <?= $index ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#message-<?= e((string) $message['id']) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="message-<?= e((string) $message['id']) ?>"><span><strong><?= e($message['subject']) ?></strong><small class="d-block text-muted"><?= e($message['name']) ?> · <?= e(format_date($message['created_at'])) ?></small></span><?= status_badge((string) $message['status']) ?></button></h3><div id="message-<?= e((string) $message['id']) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#messageAccordion"><div class="accordion-body"><div class="row g-3"><div class="col-md-4"><small class="text-muted d-block">Category</small><strong><?= e($message['category']) ?></strong></div><div class="col-md-4"><small class="text-muted d-block">Email</small><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></div><div class="col-md-4"><small class="text-muted d-block">Reference</small><strong><?= e($message['reference_number'] ?: '—') ?></strong></div><div class="col-12"><p class="mb-3"><?= nl2br(e($message['message'])) ?></p><form class="d-flex flex-wrap gap-2 align-items-center" method="post" action="<?= e(app_url('admin/messages.php')) ?>"><?= csrf_field() ?><input type="hidden" name="form" value="message_status"><input type="hidden" name="id" value="<?= e((string) $message['id']) ?>"><label class="visually-hidden" for="message-status-<?= e((string) $message['id']) ?>">Update message status</label><select class="form-select form-select-sm" id="message-status-<?= e((string) $message['id']) ?>" name="status"><?php foreach (['NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'] as $status): ?><option value="<?= e($status) ?>"<?= selected($message['status'], $status) ?>><?= e(status_label($status)) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary" type="submit">Save status</button></form></div></div></div></div></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><h2 class="h4">No support messages.</h2><p class="text-muted mb-0">New contact requests will appear here.</p></div><?php endif; ?></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
