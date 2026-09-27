<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if (is_logged_in()) {
    redirect('user/dashboard.php');
}

$errors = [];
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, ['email' => ['Email address', 190]]));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($password === '') $errors[] = 'Please enter your password.';
    if (!$errors && !attempt_login($email, $password)) $errors[] = 'The email or password did not match our records.';
    if (!$errors) {
        $returnTo = $_SESSION['return_to'] ?? null;
        unset($_SESSION['return_to']);
        set_flash('success', 'Welcome back, ' . (current_user()['name'] ?? 'adopter') . '.');
        if (is_string($returnTo) && preg_match('/^[a-z0-9\/-]+\.php(?:\?.*)?$/i', $returnTo)) redirect($returnTo);
        redirect((current_user()['role'] ?? '') === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php');
    }
}

$pageTitle = 'Sign in';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout"><div class="container"><div class="row g-4 align-items-stretch justify-content-center"><div class="col-lg-5 order-lg-2"><div class="auth-card"><span class="eyebrow">Welcome back</span><h1 class="section-title mt-3 mb-2">Sign in to PAWORA.</h1><p class="text-muted mb-4">Continue your adoption journey or manage a request.</p><?php if ($errors): ?><div class="alert alert-danger" role="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?><form method="post" action="<?= e(app_url('login.php')) ?>" data-validate novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="login-email">Email <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="email" id="login-email" name="email" value="<?= e($email) ?>" autocomplete="email" required><div class="invalid-feedback">Please enter a valid email.</div></div><div class="mb-4"><label class="form-label" for="login-password">Password <span class="required-mark" aria-hidden="true">*</span></label><div class="input-group"><input class="form-control" type="password" id="login-password" name="password" autocomplete="current-password" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="login-password" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div><button class="btn btn-primary btn-lg w-100" type="submit">Sign in <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></button></form><p class="small text-muted text-center mt-4 mb-0">New to PAWORA? <a href="<?= e(app_url('register.php')) ?>">Create an account</a></p></div></div><div class="col-lg-6 order-lg-1"><div class="auth-art"><img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1000&q=85" data-fallback="<?= e(pet_image_fallback()) ?>" alt="A happy dog outdoors" loading="lazy"><div class="auth-art-copy"><span class="eyebrow text-secondary">Keep the journey together</span><h2 class="mt-3">Save a little more hope.</h2><p>Your favorites, applications and next steps stay together so you can focus on the right pet.</p></div></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
