<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_auth();

$user = current_user();
$errors = [];
$name = trim((string) ($_POST['name'] ?? $user['name']));
$email = strtolower(trim((string) ($_POST['email'] ?? $user['email'])));
$phone = trim((string) ($_POST['phone'] ?? $user['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? $user['address'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, ['name' => ['Full name', 120], 'email' => ['Email address', 190], 'phone' => ['Phone number', 40], 'address' => ['Address', 2000]]));
    if ($name === '' || text_length($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone !== '' && (preg_match('/[^0-9+()\-\s]/', $phone) || strlen(preg_replace('/\D/', '', $phone) ?? '') < 7)) $errors[] = 'Please enter a valid phone number.';
    if (!$errors) {
        try {
            $statement = db()->prepare('UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
            $statement->execute([$name, $email, $phone ?: null, $address ?: null, (int) $user['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;
            set_flash('success', 'Your profile has been updated.');
            redirect('user/profile.php');
        } catch (Throwable $exception) {
            $errors[] = 'We could not update your profile right now.';
        }
    }
}

$userSection = 'profile';
$pageTitle = 'My profile';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrap"><div class="offcanvas-lg offcanvas-start dashboard-offcanvas" tabindex="-1" id="userSidebar" aria-label="Account navigation"><div class="offcanvas-header"><button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar" aria-label="Close"></button></div><div class="offcanvas-body p-0"><?php require __DIR__ . '/../includes/user-sidebar.php'; ?></div></div><div class="dashboard-main"><div class="dashboard-mobile-header d-lg-none align-items-center justify-content-between mb-4"><div><span class="eyebrow">My PAWORA</span><h1 class="dashboard-title mb-0">Profile</h1></div><button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSidebar" aria-controls="userSidebar"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open account navigation</span></button></div><div class="mb-4"><span class="eyebrow">Your account</span><h1 class="dashboard-title mt-2 mb-1">My profile</h1><p class="text-muted">Keep your contact details current so the PAWORA team can reach you.</p></div><div class="row g-4"><div class="col-lg-7"><section class="dashboard-panel"><h2 class="mb-4">Personal details</h2><?php if ($errors): ?><div class="alert alert-danger" role="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?><form method="post" action="<?= e(app_url('user/profile.php')) ?>" data-validate><?= csrf_field() ?><div class="row g-3"><div class="col-12"><label class="form-label" for="profile-name">Full name</label><input class="form-control" id="profile-name" name="name" value="<?= e($name) ?>" required></div><div class="col-md-6"><label class="form-label" for="profile-email">Email</label><input class="form-control" type="email" id="profile-email" name="email" value="<?= e($email) ?>" required></div><div class="col-md-6"><label class="form-label" for="profile-phone">Phone</label><input class="form-control" id="profile-phone" name="phone" value="<?= e($phone) ?>"></div><div class="col-12"><label class="form-label" for="profile-address">Address</label><textarea class="form-control" id="profile-address" name="address" rows="3"><?= e($address) ?></textarea></div></div><button class="btn btn-primary mt-4" type="submit">Save changes <i class="bi bi-check2 ms-1" aria-hidden="true"></i></button></form></section></div><div class="col-lg-5"><section class="dashboard-panel"><div class="d-flex align-items-center gap-3 mb-4"><div class="stat-icon"><i class="bi bi-person-heart" aria-hidden="true"></i></div><div><h2 class="h5 mb-1">Account safety</h2><p class="small text-muted mb-0">Your password is securely hashed and never displayed.</p></div></div><div class="contact-method"><i class="bi bi-shield-lock" aria-hidden="true"></i><div><strong>Secure session</strong><small>Sign out after using a shared device.</small></div></div><div class="contact-method"><i class="bi bi-envelope-check" aria-hidden="true"></i><div><strong>Notifications</strong><small>Application updates appear in your workspace.</small></div></div><div class="contact-method"><i class="bi bi-heart" aria-hidden="true"></i><div><strong>Favorites</strong><small>Save pets without rushing a decision.</small></div></div></section></div></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
