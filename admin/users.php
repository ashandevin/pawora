<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_admin();
$query = trim((string) ($_GET['q'] ?? ''));
$role = trim((string) ($_GET['role'] ?? ''));
$where = [];
$params = [];
if ($query !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $like = '%' . $query . '%'; array_push($params, $like, $like); }
if (in_array($role, ['USER', 'ADMIN'], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$users = db_all('SELECT u.*, (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorite_count, (SELECT COUNT(*) FROM adoption_applications a WHERE a.user_id = u.id) AS application_count FROM users u' . $whereSql . ' ORDER BY u.created_at DESC', $params);
$adminSection = 'users';
$pageTitle = 'Users';
$bodyClass = 'dashboard-page admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="adminSidebar" aria-label="Administration navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/admin-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">PAWORA Admin</span><h1 class="dashboard-title mb-0">Users</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open administration navigation</span></button></div><div class="mb-4"><span class="eyebrow">People in the community</span><h1 class="dashboard-title mt-2 mb-1">Users</h1><p class="text-muted mb-0">Review registered adopters and account activity.</p></div><section class="dashboard-panel"><form class="row g-2 align-items-end mb-4" method="get" action="<?= e(app_url('admin/users.php')) ?>"><div class="col-md-5"><label class="form-label" for="admin-user-search">Search users</label><input class="form-control" id="admin-user-search" name="q" value="<?= e($query) ?>" placeholder="Name or email"></div><div class="col-md-3"><label class="form-label" for="admin-user-role">Role</label><select class="form-select" id="admin-user-role" name="role"><option value="">All roles</option><option value="USER"<?= selected($role, 'USER') ?>>Adopter</option><option value="ADMIN"<?= selected($role, 'ADMIN') ?>>Admin</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filter</button></div><div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="<?= e(app_url('admin/users.php')) ?>">Clear</a></div></form><div class="table-responsive"><table class="table align-middle"><thead><tr><th>User</th><th>Role</th><th>Favorites</th><th>Applications</th><th>Joined</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><strong class="d-block"><?= e($user['name']) ?></strong><small class="text-muted"><?= e($user['email']) ?></small></td><td><span class="admin-badge"><?= e($user['role']) ?></span></td><td><?= e((string) $user['favorite_count']) ?></td><td><?= e((string) $user['application_count']) ?></td><td><small class="text-muted"><?= e(format_date($user['created_at'])) ?></small></td></tr><?php endforeach; ?></tbody></table></div></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
